<?php
/**
 * ==========================================
 * ORDER API - Secretul Pisicii
 * ULTRA-OPTIMIZAT - Raspuns in SUB 2 SECUNDE
 * Email-uri trimise complet in BACKGROUND
 * ==========================================
 */

// === PASUL 1: CURATARE COMPLETA BUFFERE EXISTENTE ===
// Acest pas este CRITIC - curatam orice buffer existent
while (ob_get_level() > 0) {
    ob_end_clean();
}

// === PASUL 2: CONFIGURARE PENTRU EXECUTIE IN FUNDAL ===
ignore_user_abort(true);
set_time_limit(0);

// Configurare erori (in fisier, nu pe ecran)
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/order-api-errors.log');

// Headers CORS și JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS (Raspuns instant)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// === PASUL 3: CONFIGURARE CONSTANTE ===
require_once __DIR__ . '/config.php';

// === PASUL 4: INCLUDE PHPMailer ===
require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// === PASUL 5: FUNCTII HELPER ===

/**
 * Conectare MySQL cu PDO
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("MySQL Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Salvează comanda în MySQL
 */
function saveOrderToDatabase($data) {
    $pdo = getDbConnection();
    if (!$pdo) return false;
    
    $productPrice = floatval($data['price']);
    $finalTotal = $productPrice + SHIPPING_COST;
    
    $sql = "INSERT INTO orders (
        full_name, 
        phone, 
        email, 
        county, 
        city, 
        address_line, 
        bundle, 
        total_price, 
        payment_method,
        status,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['fullName'],
            $data['phone'],
            $data['email'] ?: '',
            $data['address']['county'],
            $data['address']['city'],
            $data['address']['line'],
            $data['bundle'],
            $finalTotal,
            $data['paymentMethod']
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("MySQL Insert Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Configurare PHPMailer (Timeout redus la 5 secunde)
 */
function getMailer() {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 5; // Redus de la 10 la 5 secunde
        return $mail;
    } catch (Exception $e) {
        error_log("PHPMailer Setup Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Email către admin (HTML Original Complet)
 */
function sendAdminEmail($data, $orderId) {
    if (empty(SMTP_PASS)) return false;
    
    $mail = getMailer();
    if (!$mail) return false;
    
    $prodPrice = floatval($data['price']);
    $shipping = SHIPPING_COST;
    $total = $prodPrice + $shipping;
    
    try {
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addReplyTo($data['email'] ?: FROM_EMAIL, $data['fullName']);
        $mail->addAddress(ADMIN_EMAIL);
        
        $mail->Subject = "🛒 Comandă Nouă #{$orderId} - {$data['fullName']} - {$total} RON";
        
        $mail->isHTML(true);
        $mail->Body = "

<html>
<body style='font-family: Arial, sans-serif; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;'>
        
        <div style='background: #eb2571; color: white; padding: 20px; text-align: center;'>
            <h2>🛒 Comandă Nouă - Secretul Pisicii</h2>
        </div>
        
        <div style='background: white; padding: 20px; margin-top: 10px;'>
            
            
            <p><strong>ID Comandă:</strong> #{$orderId}</p>
            <p><strong>Client:</strong> {$data['fullName']}</p>
            <p><strong>Telefon:</strong> {$data['phone']}</p>
            <p><strong>Email:</strong> {$data['email']}</p>
            <hr>
            <p><strong>Județ:</strong> {$data['address']['county']}</p>
            <p><strong>Oraș:</strong> {$data['address']['city']}</p>
            <p><strong>Adresă:</strong> {$data['address']['line']}</p>
            <hr>
            
            <h3 style='margin-bottom:10px;'>Sumar Plată:</h3>
            <table style='width:100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding:5px 0;'>Pachet ({$data['bundle']} buc)</td>
                    <td style='text-align:right;'>{$prodPrice} RON</td>
                </tr>
                <tr>
                    <td style='padding:5px 0;'>Transport Curier GLS</td>
                    <td style='text-align:right;'>{$shipping} RON</td>
                </tr>
                <tr style='border-top:1px solid #ccc; font-weight:bold;'>
                    <td style='padding:10px 0;'>TOTAL</td>
                    <td style='text-align:right; color:#eb2571;'>{$total} RON</td>
                </tr>
            </table>
            <p style='margin-top:15px;'><strong>Metodă Plată:</strong> " . strtoupper($data['paymentMethod']) . "</p>
            
            <div style='text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;'>
                <a href='" . ADMIN_URL . "' style='background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>
                    Mergi la Admin Dashboard 🚀
                </a>
            </div>
        </div>
    </div>
</body>
</html>
";
        
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Admin Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Email confirmare client (HTML Original Complet)
 */
function sendClientEmail($data, $orderId) {
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    if (empty(SMTP_PASS)) return false;
    
    $mail = getMailer();
    if (!$mail) return false;
    
    $prodPrice = floatval($data['price']);
    $shipping = SHIPPING_COST;
    $total = $prodPrice + $shipping;
    
    try {
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($data['email'], $data['fullName']);
        
        $mail->Subject = "Confirmare Comanda #" . $orderId . " - Secretul Pisicii";
        
        $plainText = "Buna {$data['fullName']},
        Am primit comanda ta!
        Total de plata: {$total} RON (Pachet {$prodPrice} + Transport {$shipping}).
        Adresa: {$data['address']['county']}, {$data['address']['city']}, {$data['address']['line']}.
        Te vom contacta curand.";

        $mail->AltBody = $plainText;
        
        $mail->isHTML(true);
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #eb2571;'>Confirmare Comanda</h2>
                <p>Buna <strong>{$data['fullName']}</strong>,</p>
                <p>Am primit comanda ta cu succes! Mai jos ai detaliile.</p>
                
                <h3 style='color: #eb2571;'>Detalii Financiare:</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr style='border-bottom: 1px solid #eee;'>
                        <td style='padding: 8px 0;'><strong>Numar Comanda:</strong></td>
                        <td style='padding: 8px 0;'>#{$orderId}</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #eee;'>
                        <td style='padding: 8px 0;'><strong>Pachet:</strong></td>
                        <td style='padding: 8px 0;'>{$data['bundle']} bucăți ({$prodPrice} Lei)</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #eee;'>
                        <td style='padding: 8px 0;'><strong>Transport GLS:</strong></td>
                        <td style='padding: 8px 0;'>{$shipping} Lei</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #eee;'>
                        <td style='padding: 8px 0;'><strong>TOTAL DE PLATĂ:</strong></td>
                        <td style='padding: 8px 0;'><strong style='color: #eb2571;'>{$total} RON</strong></td>
                    </tr>
                </table>
                
                <h3 style='color: #eb2571;'>Adresa Livrare:</h3>
                <p>{$data['address']['county']}, {$data['address']['city']}<br>
                {$data['address']['line']}</p>
                
                <p><strong>Telefon:</strong> {$data['phone']}</p>
                
                <div style='margin-top: 30px; padding: 15px; background: #f9f9f9; border-left: 4px solid #eb2571;'>
                    <p style='margin: 0;'><strong>Te vom contacta in curand pentru confirmare!</strong></p>
                </div>
                
                <p style='text-align: center; color: #666; margin-top: 40px;'>
                    Multumim,<br>
                    <strong>Echipa Secretul Pisicii</strong>
                </p>
            </div>
        </body>
        </html>
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Client Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

// ==========================================
//   MAIN LOGIC - EXECUTIE OPTIMIZATA
// ==========================================

// 1. Verificare metodă HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodă HTTP invalidă.']);
    exit;
}

// 2. Citire și validare date JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Date invalide. Verifică formatul JSON.']);
    exit;
}

// 3. Validare câmpuri obligatorii
$required = ['fullName', 'phone', 'bundle', 'price', 'paymentMethod'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Câmpul '$field' este obligatoriu."]);
        exit;
    }
}

// 4. Validare adresă
if (empty($data['address']['county']) || empty($data['address']['city']) || empty($data['address']['line'])) {
    echo json_encode(['success' => false, 'message' => 'Adresa completă este obligatorie.']);
    exit;
}

// 5. Validare telefon
if (!preg_match('/^[0-9\s\+\-\(\)]{10,}$/', $data['phone'])) {
    echo json_encode(['success' => false, 'message' => 'Număr de telefon invalid.']);
    exit;
}

// 6. Validare email (daca e furnizat)
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Adresa de email este invalidă.']);
    exit;
}

// 7. Sanitizare date
$data['fullName'] = htmlspecialchars(trim($data['fullName']), ENT_QUOTES, 'UTF-8');
$data['phone'] = htmlspecialchars(trim($data['phone']), ENT_QUOTES, 'UTF-8');
$data['email'] = htmlspecialchars(trim($data['email']), ENT_QUOTES, 'UTF-8');
$data['address']['county'] = htmlspecialchars(trim($data['address']['county']), ENT_QUOTES, 'UTF-8');
$data['address']['city'] = htmlspecialchars(trim($data['address']['city']), ENT_QUOTES, 'UTF-8');
$data['address']['line'] = htmlspecialchars(trim($data['address']['line']), ENT_QUOTES, 'UTF-8');

// 8. SALVARE ÎN BAZA DE DATE
$orderId = saveOrderToDatabase($data);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Eroare la salvarea comenzii. Baza de date nu raspunde.']);
    exit;
}

// ==========================================
//  PUNCT CRITIC - TRIMITERE RASPUNS INSTANT
// ==========================================

// Construim raspunsul JSON
$response = [
    'success' => true,
    'message' => 'Comanda ta a fost înregistrată cu succes! Te vom contacta în curând.',
    'data' => ['orderId' => $orderId],
    'timestamp' => date('Y-m-d H:i:s')
];

$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
$responseLength = strlen($jsonResponse);

// Trimitem header-ele in ordinea EXACTA pentru inchidere rapida
header("Content-Length: {$responseLength}");
header("Connection: close");
header("Content-Encoding: none");

// Trimitem raspunsul
echo $jsonResponse;

// Fortam trimiterea catre client
if (function_exists('ob_flush')) {
    @ob_flush();
}
flush();

// ACTIVAM EXECUTIA IN BACKGROUND (Clientul primeste raspunsul AICI!)
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// ==========================================
//  BACKGROUND PROCESS - TRIMITERE EMAIL-URI
//  (Clientul NU mai asteapta asta!)
// ==========================================

// Trimitem email-urile in background fara sa afecteze viteza raspunsului
try {
    sendAdminEmail($data, $orderId);
} catch (Exception $e) {
    error_log("Background Admin Email Error: " . $e->getMessage());
}

try {
    sendClientEmail($data, $orderId);
} catch (Exception $e) {
    error_log("Background Client Email Error: " . $e->getMessage());
}

// Gata! Scriptul se termina aici.
// === INTEGRARE OBLIO (AUTO SEND) ===
// Doar daca a trecut de salvarea in DB
if ($orderId) {
    try {
        require_once __DIR__ . '/oblio_functions.php';
        
        // Verificam setarea din DB: Este Auto Send ON?
        $pdo = getDbConnection();
        $stmtS = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'auto_oblio'");
        $stmtS->execute();
        $setting = $stmtS->fetch();

        if ($setting && $setting['setting_value'] == '1') {
            // Reconstruim structura de date necesara functiei Oblio
            // (Atentie: $data deja exista in api, dar trebuie sa fim siguri ca are cheile corecte pentru DB mapping)
            // Cel mai sigur e sa luam datele "curate" sau sa folosim $data existent
            $orderDataForOblio = [
                'full_name' => $data['fullName'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address_line' => $data['address']['line'],
                'city' => $data['address']['city'],
                'county' => $data['address']['county'],
                'total_price' => floatval($data['price']) + 14, // Refacem totalul
                'payment_method' => $data['paymentMethod'],
                'bundle' => $data['bundle']
            ];

            sendOrderToOblio($orderDataForOblio, $orderId, $pdo);
            error_log("Oblio Auto-Send executat pentru comanda #$orderId");
        }
    } catch (Exception $e) {
        error_log("Oblio Auto-Send Error: " . $e->getMessage());
    }
}

exit; // Finalul scriptului

?>
