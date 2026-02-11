<?php
// ecolet_functions.php - Generare AWB (Versiune Securizata)

require_once __DIR__ . '/config.php';

// 1. Curatare nume pentru cautare
function ecoletCleanName($str) {
    if (!$str) return "";
    $str = mb_strtolower($str, 'UTF-8');
    $str = str_replace(['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'], ['a', 'a', 'i', 's', 't', 'a', 'a', 'i', 's', 't'], $str);
    $remove = ['judetul', 'comuna', 'sat', 'municipiul', 'orasul', 'oras', 'county', 'district'];
    foreach ($remove as $word) $str = str_replace($word, '', $str);
    return trim(preg_replace('/[^a-z0-9\s]/', '', $str));
}

// 2. Autentificare
function getEcoletToken() {
    // Verificam daca avem datele in config
    if (!defined('ECOLET_CLIENT_ID') || !ECOLET_CLIENT_ID) {
        error_log("Ecolet Error: Datele de conectare lipsesc din .env");
        return null;
    }

    $ch = curl_init('https://panel.ecolet.ro/api/v1/oauth/token');
    $postData = [
        'grant_type' => 'password',
        'client_id' => ECOLET_CLIENT_ID,
        'client_secret' => ECOLET_CLIENT_SECRET,
        'username' => ECOLET_USERNAME,
        'password' => ECOLET_PASSWORD
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($httpCode !== 200 || !isset($data['access_token'])) {
        error_log("Ecolet Auth Failed: " . $response);
        return null;
    }
    
    return $data['access_token'];
}

// 3. Cautare ID Localitate
function getEcoletLocalityId($token, $county, $city) {
    $simpleCity = ecoletCleanName($city);
    $simpleCounty = ecoletCleanName($county);
    $searchQuery = $simpleCity . ' ' . $simpleCounty;
    
    // Cautare 1: Oras + Judet
    $ch = curl_init('https://panel.ecolet.ro/api/v1/locations/ro/localities/' . urlencode($searchQuery));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $localities = $data['localities'] ?? ($data ?? []);
    if (!empty($localities)) {
        foreach ($localities as $loc) {
            $locCounty = ecoletCleanName($loc['county']['name'] ?? '');
            if (strpos($locCounty, $simpleCounty) !== false || strpos($simpleCounty, $locCounty) !== false) {
                return $loc['id'];
            }
        }
        return $localities[0]['id'];
    }
    
    // Cautare 2 (Fallback): Doar Oras
    $ch = curl_init('https://panel.ecolet.ro/api/v1/locations/ro/localities/' . urlencode($simpleCity));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    $dataRetry = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $locsRetry = $dataRetry['localities'] ?? ($dataRetry ?? []);
    foreach ($locsRetry as $loc) {
        $locCounty = ecoletCleanName($loc['county']['name'] ?? '');
        if (strpos($locCounty, $simpleCounty) !== false) return $loc['id'];
    }

    return 323; // Fallback Bucuresti
}

// 4. Generare AWB
function generateEcoletAWB($orderData, $orderId, $pdo) {
    $token = getEcoletToken();
    if (!$token) return ['success' => false, 'message' => 'Autentificare esuata'];
    
    $localityId = getEcoletLocalityId($token, $orderData['county'], $orderData['city']);
    
    $payload = [
        'sender' => [
            'name' => ECOLET_SENDER_NAME,
            'country' => 'ro',
            'county' => 'Bucuresti', 
            'locality_id' => 323,    
            'locality' => 'Bucuresti',
            'street_name' => 'Depozit',
            'street_number' => '1',
            'contact_person' => ECOLET_SENDER_NAME,
            'email' => ADMIN_EMAIL,
            'phone' => '0700000000'
        ],
        'receiver' => [
            'name' => $orderData['full_name'],
            'country' => 'ro',
            'county' => $orderData['county'],
            'locality_id' => $localityId,
            'locality' => $orderData['city'],
            'street_name' => $orderData['address_line'],
            'street_number' => '1',
            'contact_person' => $orderData['full_name'],
            'email' => $orderData['email'],
            'phone' => preg_replace('/[^0-9]/', '', $orderData['phone'])
        ],
        'parcel' => [
            'type' => 'package',
            'weight' => 1,
            'content' => 'Produse',
            'observations' => "Comanda #$orderId - " . $orderData['bundle'],
            'amount' => 1
        ],
        'additional_services' => [
            'cod' => [
                'status' => true,
                'amount' => (float)$orderData['total_price']
            ]
        ],
        'courier' => [
            'service' => 'dpd_standard', // Sau 'sameday_courier', verifica in cont!
            'contract_id' => 4 
        ]
    ];

    $ch = curl_init('https://panel.ecolet.ro/api/v1/add-parcel/save-order-to-send');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if ($httpCode == 200 || $httpCode == 201) {
        $shipmentId = $response['order_to_send_id'] ?? ($response['id'] ?? null);
        
        // Salvam AWB in DB (incercam, daca exista coloana)
        try {
            $stmt = $pdo->prepare("UPDATE orders SET awb_number = ? WHERE id = ?");
            $stmt->execute([$shipmentId, $orderId]);
        } catch (Exception $e) {}
        
        return ['success' => true, 'message' => 'AWB Creat: ' . $shipmentId];
    } else {
        return ['success' => false, 'message' => 'Eroare: ' . ($response['message'] ?? $result)];
    }
}
?>