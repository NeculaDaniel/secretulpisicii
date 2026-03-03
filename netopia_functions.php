<?php
/**
 * netopia_functions.php
 * Traducere PHP exacta a api/services/netopia.js din oclar.ro
 */
require_once __DIR__ . '/config.php';

function createPaymentSession($paymentData) {
    $apiKey       = NETOPIA_API_KEY;
    $apiUrl       = NETOPIA_URL;
    $posSignature = NETOPIA_SIGNATURE_KEY;

    if (!$apiKey || !$posSignature)
        throw new Exception("Lipsesc NETOPIA_API_KEY sau NETOPIA_SIGNATURE_KEY din .env");

    $dateTime  = gmdate('Y-m-d\TH:i:s\Z');
    $orderId   = $paymentData['orderId'];
    $amount    = floatval($paymentData['amount']);
    $email     = $paymentData['email']     ?? 'client@fara-email.com';
    $phone     = $paymentData['phone']     ?? '0700000000';
    $firstName = $paymentData['firstName'] ?? 'Client';
    $lastName  = $paymentData['lastName']  ?? 'Test';
    $address   = $paymentData['address']   ?? [];

    $payload = [
        'config' => [
            'notifyUrl'   => rtrim(SITE_URL, '/') . '/payment-webhook.php',
            'redirectUrl' => rtrim(SITE_URL, '/') . '/payment-return.php?orderId=' . $orderId,
            'language'    => 'ro',
        ],
        'payment' => [
            'options'    => ['installments' => 0, 'bonus' => 0],
            'instrument' => ['type' => 'card', 'account' => '', 'expMonth' => 1, 'expYear' => 2030, 'secretCode' => ''],
        ],
        'order' => [
            'posSignature' => $posSignature,
            'dateTime'     => $dateTime,
            'description'  => 'Comanda ' . $orderId,
            'orderID'      => (string)$orderId,
            'amount'       => $amount,
            'currency'     => 'RON',
            'billing' => [
                'email' => $email, 'phone' => $phone,
                'firstName' => $firstName, 'lastName' => $lastName,
                'city' => $address['city'] ?? 'Bucuresti', 'country' => 642,
                'countryName' => 'Romania', 'state' => $address['county'] ?? 'Bucuresti',
                'postalCode' => '000000', 'details' => $address['line'] ?? 'Adresa completa',
            ],
            'shipping' => [
                'email' => $email, 'phone' => $phone,
                'firstName' => $firstName, 'lastName' => $lastName,
                'city' => $address['city'] ?? 'Bucuresti', 'country' => 642,
                'state' => $address['county'] ?? 'Bucuresti',
                'postalCode' => '000000', 'details' => $address['line'] ?? 'Adresa completa',
            ],
        ],
    ];

    systemLog(LOG_ORDERS, "[Netopia REST] Initiere plata comanda #$orderId...");

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: ' . $apiKey],
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 30,
    ]);
    $result  = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($result === false) throw new Exception("Netopia CURL error: $curlErr");
    $response = json_decode($result, true);
    systemLog(LOG_ORDERS, "NETOPIA RAW RESPONSE: " . substr($result, 0, 500));

    if (isset($response['payment']['paymentURL'])) {
        $url = $response['payment']['paymentURL'];
        systemLog(LOG_ORDERS, "[Netopia REST] Success! URL: $url");
        return ['success' => true, 'paymentUrl' => $url];
    }
    if (isset($response['error']))
        throw new Exception("Netopia Error: " . ($response['error']['message'] ?? 'Unknown') . " (Code: " . ($response['error']['code'] ?? 0) . ")");

    throw new Exception("Raspuns invalid Netopia: " . substr($result, 0, 300));
}

function validatePaymentNotification($reqBody) {
    if (!$reqBody || !isset($reqBody['payment']) || !isset($reqBody['order']))
        throw new Exception("Invalid IPN format");

    $status  = $reqBody['payment']['status'] ?? 0;
    $orderId = $reqBody['order']['orderID']  ?? 0;
    $ntpId   = $reqBody['payment']['ntpID']  ?? '';

    $isSuccess = false; $message = 'Pending';
    if ($status === 3 || $status === '3')       { $isSuccess = true;  $message = 'PAID (In asteptare confirmare)'; }
    elseif ($status === 5 || $status === '5')   { $isSuccess = true;  $message = 'CONFIRMED (Banii sunt la tine)'; }
    elseif ($status === 12 || $status === '12') { $isSuccess = false; $message = 'REJECTED (Plata respinsa)'; }

    return ['success' => $isSuccess, 'orderId' => intval($orderId), 'transactionId' => $ntpId, 'status' => $status, 'message' => $message];
}
?>
