<?php
/**
 * NETOPIA PAYMENT GATEWAY
 * Integrare pentru plăți cu card - Secretul Pisicii
 * Version 1.0
 */

require_once __DIR__ . '/config.php';

class NetopiaPayment {
    
    private $merchantId;
    private $apiKey;
    private $signatureKey;
    private $apiUrl;
    
    public function __construct() {
        $this->merchantId = NETOPIA_MERCHANT_ID;
        $this->apiKey = NETOPIA_API_KEY;
        $this->signatureKey = NETOPIA_SIGNATURE_KEY;
        $this->apiUrl = NETOPIA_URL;
    }
    
    /**
     * Crează o plată și returnează URL-ul de redirect
     */
    public function createPayment($orderId, $amount, $currency = 'RON', $email = '', $phone = '') {
        
        if (!$this->merchantId || !$this->apiKey) {
            return [
                'success' => false,
                'error' => 'Netopia nu este configurat. Contactează administratorul.'
            ];
        }
        
        // Datele plății
        $paymentData = [
            'orderRef' => 'ORD_' . $orderId . '_' . time(),
            'signature' => '',
            'amount' => intval($amount * 100), // Netopia folosește cenți
            'currency' => $currency,
            'orderTimeout' => 3600,
            'billingDetails' => [
                'firstName' => explode(' ', trim($email))[0] ?? 'Customer',
                'lastName' => '',
                'email' => $email,
                'phone' => $phone,
                'country' => 'RO'
            ],
            'shippingDetails' => [
                'firstName' => explode(' ', trim($email))[0] ?? 'Customer',
                'lastName' => '',
                'country' => 'RO'
            ],
            'items' => [
                [
                    'name' => 'Secretul Pisicii - Perie Nano-Steam',
                    'code' => 'SP001',
                    'category' => 'Electronics',
                    'amount' => intval($amount * 100),
                    'quantity' => 1
                ]
            ],
            'cancelUrl' => SITE_URL . '/payment-cancel.php?orderId=' . $orderId,
            'confirmUrl' => SITE_URL . '/payment-confirm.php?orderId=' . $orderId,
            'returnUrl' => SITE_URL . '/payment-return.php?orderId=' . $orderId,
            'notifyUrl' => SITE_URL . '/payment-webhook.php'
        ];
        
        // Calculăm signatura
        $paymentData['signature'] = $this->generateSignature($paymentData);
        
        // Trimitem la Netopia
        $response = $this->sendRequest('/api/checkout', $paymentData);
        
        if (!$response) {
            return [
                'success' => false,
                'error' => 'Eroare conexiune la gateway-ul de plată'
            ];
        }
        
        // Analizez răspunsul
        if (isset($response['status']) && $response['status'] === 'ok') {
            logEvent(LOG_PAYMENTS, "Plată creată: orderId=$orderId, refId={$paymentData['orderRef']}, amount=$amount RON");
            
            return [
                'success' => true,
                'paymentUrl' => $response['paymentUrl'],
                'orderRef' => $paymentData['orderRef']
            ];
        } else {
            $error = $response['message'] ?? 'Eroare necunoscută Netopia';
            logEvent(LOG_PAYMENTS, "EROARE plată: orderId=$orderId, error=$error");
            
            return [
                'success' => false,
                'error' => $error
            ];
        }
    }
    
    /**
     * Generează signatura pentru request
     */
    private function generateSignature($data) {
        // Netopia utilizează HMAC-SHA512
        $toSign = json_encode($data);
        return hash_hmac('sha512', $toSign, $this->signatureKey);
    }
    
    /**
     * Trimit request la Netopia API
     */
    private function sendRequest($endpoint, $data) {
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => !NETOPIA_SANDBOX,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'X-Merchant-Id: ' . $this->merchantId
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            logEvent(LOG_ERRORS, "Netopia cURL Error: $curlError");
            return null;
        }
        
        if ($httpCode !== 200) {
            logEvent(LOG_ERRORS, "Netopia HTTP Error $httpCode: $result");
            return null;
        }
        
        return json_decode($result, true);
    }
    
    /**
     * Verific o plată completată
     */
    public function verifyPayment($paymentRef) {
        
        $response = $this->sendRequest('/api/payment/verify', [
            'paymentRef' => $paymentRef
        ]);
        
        if ($response && isset($response['status']) && $response['status'] === 'paid') {
            return [
                'success' => true,
                'amount' => $response['amount'] / 100, // Convertim din cenți
                'status' => 'completed'
            ];
        }
        
        return [
            'success' => false,
            'status' => 'pending'
        ];
    }
}

// ============================================
// ENDPOINT: Crează o plată nouă
// POST /netopia-api.php
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
    
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['orderId']) || !isset($input['amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Date incomplete']);
        exit;
    }
    
    $netopia = new NetopiaPayment();
    $result = $netopia->createPayment(
        $input['orderId'],
        $input['amount'],
        'RON',
        $input['email'] ?? '',
        $input['phone'] ?? ''
    );
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// WEBHOOK: Primire confirmare de la Netopia
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'webhook') {
    
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = isset($input['orderRef']) ? intval(explode('_', $input['orderRef'])[1] ?? 0) : 0;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false]);
        exit;
    }
    
    // Conectez la DB
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        
        if ($input['status'] === 'paid') {
            // Marchez comanda ca plătită
            $stmt = $pdo->prepare("UPDATE orders SET payment_status='paid', payment_method='card' WHERE id=?");
            $stmt->execute([$orderId]);
            
            logEvent(LOG_PAYMENTS, "Webhook: Plată confirmată pentru comanda #$orderId");
        } else {
            logEvent(LOG_PAYMENTS, "Webhook: Plată eșuată pentru comanda #$orderId");
        }
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        logEvent(LOG_ERRORS, "Webhook error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false]);
    }
    
    exit;
}

// Default - returnez eroare
http_response_code(400);
echo json_encode(['error' => 'Action not found']);
?>
