<?php
/**
 * oblio_functions.php - rescris dupa logica oclar.ro
 * Endpoint-uri corecte: /api/authorize/token si /api/docs/invoice
 */

function getOblioToken() {
    $cacheDir  = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $tokenFile = $cacheDir . '/oblio_token.json';

    // Verificam cache
    if (file_exists($tokenFile)) {
        $cached = json_decode(file_get_contents($tokenFile), true);
        if ($cached && isset($cached['access_token']) && isset($cached['expires_at'])) {
            if (time() < $cached['expires_at'] - 60) {
                return $cached['access_token'];
            }
        }
    }

    // Cerem token nou — endpoint fara /v1/ (ca la oclar)
    $ch = curl_init('https://www.oblio.eu/api/authorize/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'client_id'     => trim(OBLIO_EMAIL),
            'client_secret' => trim(OBLIO_API_SECRET),
            'grant_type'    => 'client_credentials',
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $result    = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($result === false) {
        throw new Exception("Conexiune Oblio esuata: " . $curlError);
    }

    $data = json_decode($result, true);

    if ($httpCode !== 200 || !isset($data['access_token'])) {
        $msg = $data['statusMessage'] ?? $data['error_description'] ?? $result;
        throw new Exception("Eroare Token Oblio ($httpCode): " . $msg);
    }

    // Salvam in cache
    @file_put_contents($tokenFile, json_encode([
        'access_token' => $data['access_token'],
        'expires_at'   => time() + ($data['expires_in'] ?? 3600),
    ]));

    return $data['access_token'];
}


function sendToOblio($orderId) {
    global $pdo;
    if (!isset($pdo)) $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) throw new Exception("Comanda #$orderId nu exista.");

    $shippingCost = ($order['shipping_method'] === 'easybox')
        ? floatval(SHIPPING_COST_EASYBOX)
        : floatval(SHIPPING_COST_GLS);

    $totalOrder   = floatval($order['total_price']);
    $productTotal = $totalOrder - $shippingCost;
    $qty          = max(1, (int)$order['bundle']);

    // Oclar trimite preturile CU TVA; Oblio le imparte dupa vatPercentage
    $unitPrice   = round($productTotal / $qty, 4);
    $shippingNet = round($shippingCost, 4);

    // Construim produsele — camp names dupa documentatia Oblio (verificate cu oclar)
    $products = [
        [
            'name'            => 'Perie Nano-Steam (Pachet ' . $qty . ' buc)',
            'code'            => 'SP-001',
            'description'     => 'Dispozitiv ingrijire animale de companie',
            'price'           => $unitPrice,
            'currency'        => 'RON',
            'vatName'         => 'Normala',
            'vatPercentage'   => 19,
            'quantity'        => $qty,
            'measurementUnit' => 'buc',
            'productType'     => 'Marfa',
        ],
    ];

    if ($shippingNet > 0) {
        $products[] = [
            'name'            => 'Transport',
            'code'            => 'TRANSPORT',
            'description'     => 'Cost transport',
            'price'           => $shippingNet,
            'currency'        => 'RON',
            'vatName'         => 'Normala',
            'vatPercentage'   => 19,
            'quantity'        => 1,
            'measurementUnit' => 'buc',
            'productType'     => 'Serviciu',
        ];
    }

    $invoiceData = [
        'cif'          => trim(OBLIO_CUI_FIRMA),
        'client'       => [
            'name'    => $order['full_name'],
            'email'   => $order['email'] ?? '',
            'phone'   => $order['phone'] ?? '',
            'address' => $order['address_line'] ?? '',
            'city'    => $order['city'] ?? '',
            'county'  => $order['county'] ?? '',
            'country' => 'Romania',
            'save'    => false,
        ],
        'seriesName'   => trim(OBLIO_SERIE),
        'issueDate'    => date('Y-m-d'),
        'dueDate'      => date('Y-m-d', strtotime('+14 days')),
        'deliveryDate' => date('Y-m-d'),
        'currency'     => 'RON',
        'language'     => 'RO',
        'precision'    => 2,
        'useStock'     => false,
        'mentions'     => 'Comanda #' . $orderId,
        'products'     => $products,
    ];

    $token = getOblioToken();

    // Endpoint fara /v1/ — /api/docs/invoice (ca la oclar)
    $ch = curl_init('https://www.oblio.eu/api/docs/invoice');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($invoiceData),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($result, true);

    if ($httpCode >= 400) {
        $errMsg = $response['statusMessage'] ?? $response['message'] ?? substr($result, 0, 200);
        if (!empty($response['data'])) {
            $errMsg .= ' | ' . json_encode($response['data']);
        }
        throw new Exception("Oblio invoice error ($httpCode): " . $errMsg);
    }

    $link = $response['data']['link'] ?? null;
    if (!$link) {
        throw new Exception("Oblio: factura creata dar link lipsa. Raspuns: " . substr($result, 0, 300));
    }

    $stmtU = $pdo->prepare("UPDATE orders SET oblio_status = 1, oblio_link = ? WHERE id = ?");
    $stmtU->execute([$link, $orderId]);

    return "Factura emisa cu succes! Link: $link";
}