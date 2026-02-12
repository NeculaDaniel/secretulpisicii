<?php
// oblio_functions.php
// Datele sunt preluate din config.php

function sendOrderToOblio($orderData, $orderId, $pdo) {
    if (!defined('OBLIO_API_SECRET') || !OBLIO_API_SECRET) {
        return ['success' => false, 'message' => 'Configuratia Oblio lipseste'];
    }
    
    // 1. Verificăm cURL
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'cURL nu este activat pe server!'];
    }

    // 2. Calcule (Simplificate pentru neplătitori)
    // La neplătitori, Prețul este final, nu se mai extrage TVA din el.
    $shippingCost = 14.00;
    $totalPrice = floatval($orderData['total_price']);
    $productPrice = $totalPrice - $shippingCost;

    // 3. JSON Oblio (Configurat pentru NEPLĂTITOR TVA)
    $invoiceData = [
        'cif' => OBLIO_CUI_FIRMA,
        'client' => [
            'name' => $orderData['full_name'],
            'email' => $orderData['email'],
            'phone' => $orderData['phone'],
            'address' => $orderData['address_line'],
            'city' => $orderData['city'],
            'state' => $orderData['county'],
            'country' => 'RO',
            'save' => true
        ],
        'seriesName' => OBLIO_SERIE,
        'collect' => [
            'payType' => 'Ramburs', 
            'value'   => $totalPrice
        ],
        'products' => [
            [
                'name' => "Pachet " . $orderData['bundle'], 
                'measuringUnitName' => 'buc',
                'currency' => 'RON',
                'quantity' => 1,
                'price' => $productPrice, 
                // IMPORTANT: La neplătitori, TVA este de obicei gol, 'Scutit' sau '0%'
                // Verifică în Oblio -> Nomenclatoare -> Cote TVA cum se numește exact la tine.
                // De obicei, dacă nu pui nimic, Oblio știe automat că firma e neplătitoare.
                'vatName' => '', 
                'vatPercentage' => 0, 
            ],
            [
                'name' => "Servicii Transport",
                'measuringUnitName' => 'serv',
                'currency' => 'RON',
                'quantity' => 1,
                'price' => $shippingCost,
                'vatName' => '', 
                'vatPercentage' => 0,
            ]
        ],
    ];

    // 4. Trimitere Request (Codul rămâne la fel aici)
    $ch = curl_init('https://www.oblio.eu/api/v1/invoice');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invoiceData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OBLIO_EMAIL . ':' . OBLIO_API_SECRET
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) return ['success' => false, 'message' => 'Eroare Conexiune: ' . $curlError];

    $response = json_decode($result, true);

    if ($httpCode == 201 || $httpCode == 200) {
        $link = $response['data']['link'];
        $series = $response['data']['seriesName'];
        $number = $response['data']['number'];

        $stmt = $pdo->prepare("UPDATE orders SET oblio_series=?, oblio_number=?, oblio_link=?, oblio_status=1 WHERE id=?");
        $stmt->execute([$series, $number, $link, $orderId]);
        
        return ['success' => true, 'message' => 'Emisa: ' . $series . ' ' . $number];
    } else {
        $msg = isset($response['status']['message']) ? json_encode($response['status']['message']) : $result;
        return ['success' => false, 'message' => "Eroare Oblio ($httpCode): " . $msg];
    }

}


