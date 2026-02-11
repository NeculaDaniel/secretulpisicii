<?php
/**
 * ==========================================
 *   ORDER API - Secretul Pisicii
 *   Versiune Optimizată - v2.0
 *   MySQL + PHPMailer (Gmail SMTP)
 * ==========================================
 */

// Configurare erori
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/order-api-errors.log');

// Headers CORS și JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ==========================================
//   CONFIGURARE MySQL
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'alvoro_r1_admin');
define('DB_USER', 'alvoro_r1_user');
define('DB_PASS', 'Parola2020@');

// ==========================================
//   CONFIGURARE EMAIL (Gmail SMTP)
//   IMPORTANT: Generează App Password nou din:
//   https://myaccount.google.com/apppasswords
// ==========================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'alvoro.enterprise@gmail.com');
define('SMTP_PASS', 'muxtrnkxpnxqqfjq'); // ÎNLOCUIEȘTE CU APP PASSWORD NOU!
define('ADMIN_EMAIL', 'alvoro.enterprise@gmail.com');
define('FROM_EMAIL', 'alvoro.enterprise@gmail.com');
define('FROM_NAME', 'Secretul Pisicii');

// ==========================================
//   INCLUDE PHPMailer
// ==========================================
require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Trimite răspuns JSON
 */
function sendJsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

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
        sendJsonResponse(false, 'Eroare la conectarea cu baza de date. Contactează administratorul.');
    }
}

/**
 * Salvează comanda în MySQL
 */
function saveOrderToDatabase($data) {
    $pdo = getDbConnection();
    
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
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
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
            $data['price'],
            $data['paymentMethod']
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("MySQL Insert Error: " . $e->getMessage());
        sendJsonResponse(false, 'Eroare la salvarea comenzii. Te rugăm să încerci din nou.');
    }
}

/**
 * Configurare PHPMailer
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
        
        return $mail;
        
    } catch (Exception $e) {
        error_log("PHPMailer Setup Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Email către admin
 */
function sendAdminEmail($data, $orderId) {
    // Verificare SMTP_PASS
    if (empty(SMTP_PASS)) {
        error_log("SMTP_PASS is empty! Cannot send emails.");
        return false;
    }
    
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addReplyTo($data['email'] ?: FROM_EMAIL, $data['fullName']);
        $mail->addAddress(ADMIN_EMAIL);
        
        $mail->Subject = "🛒 Comandă Nouă #{$orderId} - {$data['fullName']}";
        
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
                    <p><strong>Pachet:</strong> {$data['bundle']} bucăți</p>
                    <p><strong>Preț Total:</strong> <span style='color: #eb2571; font-size: 18px;'>{$data['price']} RON</span></p>
                    <p><strong>Metodă Plată:</strong> " . strtoupper($data['paymentMethod']) . "</p>
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
 * Email confirmare client
 */
function sendClientEmail($data, $orderId) {
    // Verificare email valid
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Verificare SMTP_PASS
    if (empty(SMTP_PASS)) {
        error_log("SMTP_PASS is empty! Cannot send emails.");
        return false;
    }
    
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($data['email'], $data['fullName']);
        
        $mail->Subject = "Confirmare Comanda #" . $orderId . " - Secretul Pisicii";
        
        // Plain text version
        $plainText = "Buna {$data['fullName']},

Am primit comanda ta cu succes!

DETALII COMANDA:
Numar Comanda: #{$orderId}
Pachet: {$data['bundle']} bucăți
Pret Total: {$data['price']} RON
Metoda Plata: " . strtoupper($data['paymentMethod']) . "

ADRESA LIVRARE:
{$data['address']['county']}, {$data['address']['city']}
{$data['address']['line']}

Telefon Contact: {$data['phone']}

Te vom contacta in curand pentru confirmare.

Multumim,
Echipa Secretul Pisicii";

        $mail->AltBody = $plainText;
        
        // HTML version
        $mail->isHTML(true);
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #eb2571;'>Confirmare Comanda</h2>
                <p>Buna <strong>{$data['fullName']}</strong>,</p>
                <p>Am primit comanda ta cu succes!</p>
                
                <h3 style='color: #eb2571;'>Detalii Comanda:</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr style='border-bottom: 1px solid #eee;'>
                        <td style='padding: 8px 0;'><strong>Numar Comanda:</strong></td>
                        <td style='padding: 8px 0;'>#{$orderId}</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #eee;'>
                        <td style='padding: 8px 0;'><strong>Pachet:</strong></td>
                        <td style='padding: 8px 0;'>{$data['bundle']} bucăți</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #eee;'>
                        <td style='padding: 8px 0;'><strong>Pret Total:</strong></td>
                        <td style='padding: 8px 0;'><strong style='color: #eb2571;'>{$data['price']} RON</strong></td>
                    </tr>
                </table>
                
                <h3 style='color: #eb2571;'>Adresa Livrare:</h3>
                <p>{$data['address']['county']}, {$data['address']['city']}<br>
                {$data['address']['line']}</p>
                
                <p><strong>Telefon:</strong> {$data['phone']}</p>
                
                <div style='margin-top: 30px; padding: 15px; background: #f9f9f9; border-left: 4px solid #eb2571;'>
                    <p style='margin: 0;'><strong>Te vom contacta in curand!</strong></p>
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
//   MAIN LOGIC
// ==========================================

// Verificare metodă
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Metodă HTTP invalidă.');
}

// Citire date JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    sendJsonResponse(false, 'Date invalide. Verifică formatul JSON.');
}

// Validare câmpuri
$required = ['fullName', 'phone', 'bundle', 'price', 'paymentMethod'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        sendJsonResponse(false, "Câmpul '$field' este obligatoriu.");
    }
}

// Validare adresă
if (empty($data['address']['county']) || empty($data['address']['city']) || empty($data['address']['line'])) {
    sendJsonResponse(false, 'Adresa completă este obligatorie.');
}

// Validare telefon
if (!preg_match('/^[0-9\s\+\-\(\)]{10,}$/', $data['phone'])) {
    sendJsonResponse(false, 'Număr de telefon invalid.');
}

// Validare email (opțional)
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Adresa de email este invalidă.');
}

// Sanitizare
$data['fullName'] = htmlspecialchars(trim($data['fullName']), ENT_QUOTES, 'UTF-8');
$data['phone'] = htmlspecialchars(trim($data['phone']), ENT_QUOTES, 'UTF-8');
$data['email'] = htmlspecialchars(trim($data['email']), ENT_QUOTES, 'UTF-8');
$data['bundle'] = htmlspecialchars(trim($data['bundle']), ENT_QUOTES, 'UTF-8');
$data['price'] = htmlspecialchars(trim($data['price']), ENT_QUOTES, 'UTF-8');
$data['paymentMethod'] = htmlspecialchars(trim($data['paymentMethod']), ENT_QUOTES, 'UTF-8');
$data['address']['county'] = htmlspecialchars(trim($data['address']['county']), ENT_QUOTES, 'UTF-8');
$data['address']['city'] = htmlspecialchars(trim($data['address']['city']), ENT_QUOTES, 'UTF-8');
$data['address']['line'] = htmlspecialchars(trim($data['address']['line']), ENT_QUOTES, 'UTF-8');

// Salvare în baza de date
$orderId = saveOrderToDatabase($data);

if (!$orderId) {
    sendJsonResponse(false, 'Eroare la salvarea comenzii.');
}

// Trimitere emailuri (opțional - nu oprește fluxul dacă eșuează)
$adminEmailSent = sendAdminEmail($data, $orderId);
$clientEmailSent = sendClientEmail($data, $orderId);

// Log pentru debugging
if (!$adminEmailSent) {
    error_log("ATENȚIE: Email admin NU trimis pentru comanda #{$orderId}");
}
if (!$clientEmailSent) {
    error_log("ATENȚIE: Email client NU trimis pentru comanda #{$orderId}");
}

// Răspuns final
sendJsonResponse(true, 'Comanda ta a fost înregistrată cu succes! Te vom contacta în curând.', [
    'orderId' => $orderId
]);