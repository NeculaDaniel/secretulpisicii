<?php
/**
 * order-api.php — Secretul Pisicii
 *
 * FIX: locker_details salvat corect în DB (era omis din INSERT)
 *
 * FLOW:
 *   RAMBURS → save DB → return orderId → background: email + runAutomations(ramburs_create)
 *   CARD    → save DB → Netopia init → return paymentUrl — STOP
 *             email + runAutomations(netopia_confirm) vin DOAR din payment-webhook.php
 */

if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) ob_end_clean();
ignore_user_abort(true);
set_time_limit(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/automation.php';

if (file_exists(__DIR__ . '/PHPMailer-master/src/PHPMailer.php')) {
    require __DIR__ . '/PHPMailer-master/src/Exception.php';
    require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer-master/src/SMTP.php';
}
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('logEvent')) {
    function logEvent($type, $msg) {
        $f = defined('LOG_ORDERS') ? LOG_ORDERS : __DIR__.'/logs/orders.log';
        @mkdir(dirname($f), 0755, true);
        file_put_contents($f, '['.date('Y-m-d H:i:s').'] ['.$type.'] '.$msg.PHP_EOL, FILE_APPEND);
    }
}

function saveOrderToDatabase($data) {
    $pdo = getDbConnection();
    if (!$pdo) return false;

    $shipping   = ($data['shippingMethod'] === 'easybox') ? floatval(SHIPPING_COST_EASYBOX) : floatval(SHIPPING_COST_GLS);
    $finalTotal = floatval($data['price']) + $shipping;
    $lockerId      = $data['lockerId']      ?? null;
    $postalCode    = $data['address']['postal_code'] ?? '';

    // ============================================================
    // FIX: locker_details salvat complet (format: OPERATOR|NUME|ADRESA)
    // Înainte era omis din INSERT → rămânea NULL → Ecolet nu putea
    // detecta operatorul (Sameday/FAN/Cargus/DPD)
    // ============================================================
    $lockerDetails = $data['lockerDetails'] ?? null;

    // address_line: pentru EasyBox = numele curat al lockerului (fără prefix operator)
    // pentru curier = adresa normală
    $addressLine = $data['address']['line'] ?? '';
    if ($data['shippingMethod'] === 'easybox' && $lockerDetails) {
        $parts = explode('|', $lockerDetails);
        // format: "OPERATOR|NUME_LOCKER|ADRESA_FIZICA" — luăm NUME_LOCKER (index 1)
        $addressLine = $parts[1] ?? $parts[0] ?? $addressLine;
    }

    try {
        // ============================================================
        // FIX: adăugat locker_details în INSERT (lipsea complet)
        // ============================================================
        $s = $pdo->prepare("
            INSERT INTO orders
                (full_name, phone, email, county, city, address_line, postal_code,
                 bundle, total_price, payment_method, shipping_method,
                 easybox_locker_id, locker_details, status, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?,
                 ?, ?, ?, ?,
                 ?, ?, 'pending', NOW())
        ");
        $s->execute([
            $data['fullName'],
            $data['phone'],
            $data['email']              ?? '',
            $data['address']['county']  ?? '',
            $data['address']['city']    ?? '',
            $addressLine,
            $postalCode,
            $data['bundle'],
            $finalTotal,
            $data['paymentMethod'],
            $data['shippingMethod']     ?? 'gls',
            $lockerId,
            $lockerDetails,             // ← FIX: era lipsă
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        logEvent("INSERT_ERROR", $e->getMessage());
        return false;
    }
}

function getMailer() {
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host       = SMTP_HOST;
    $m->SMTPAuth   = true;
    $m->Username   = SMTP_USER;
    $m->Password   = SMTP_PASS;
    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $m->Port       = intval(SMTP_PORT);
    $m->CharSet    = 'UTF-8';
    $m->Timeout    = 10;
    return $m;
}

function sendAdminEmail($data, $orderId, $shipping) {
    if (empty(SMTP_PASS)) return false;
    try {
        $m      = getMailer();
        $total  = floatval($data['price']) + $shipping;
        $method = ($data['shippingMethod'] === 'easybox') ? 'EasyBox Locker' : 'GLS Curier';
        $m->setFrom(FROM_EMAIL, 'Comenzi Secretul Pisicii');
        if (!empty($data['email'])) $m->addReplyTo($data['email'], $data['fullName']);
        $m->addAddress(ADMIN_EMAIL);
        $m->Subject = "Comanda Noua #{$orderId} - {$total} RON";
        $m->isHTML(true);
        $m->Body = "<div style='font-family:Arial;padding:20px;background:#f3f4f6;'>
            <div style='background:#fff;padding:20px;border-radius:8px;'>
            <h2 style='color:#eb2571;margin-top:0;'>Comanda Noua #{$orderId}</h2>
            <p><b>Client:</b> {$data['fullName']}</p>
            <p><b>Telefon:</b> <a href='tel:{$data['phone']}'>{$data['phone']}</a></p>
            <p><b>Email:</b> {$data['email']}</p><hr>
            <p><b>Livrare:</b> {$method}</p>"
            . ($data['lockerId'] ? "<p><b>Locker ID:</b> {$data['lockerId']}</p>" : "")
            . ($data['lockerDetails'] ? "<p><b>Locker:</b> {$data['lockerDetails']}</p>" : "")
            . "<p><b>Adresa:</b> {$data['address']['county']}, {$data['address']['city']}, {$data['address']['line']}</p><hr>
            <p>Pachet: {$data['bundle']} buc | Transport: {$shipping} Lei</p>
            <h3 style='color:#eb2571;'>TOTAL: {$total} Lei</h3>
            <p><b>Plata:</b> ".strtoupper($data['paymentMethod'])."</p>
            </div></div>";
        $m->send();
        return true;
    } catch (Throwable $e) {
        logEvent("EMAIL_ADMIN_FAIL", $e->getMessage());
        return false;
    }
}

function sendClientEmail($data, $orderId, $shipping) {
    if (empty($data['email']) || empty(SMTP_PASS)) return false;
    try {
        $m      = getMailer();
        $total  = floatval($data['price']) + $shipping;
        $method = ($data['shippingMethod'] === 'easybox') ? 'EasyBox Locker' : 'GLS Curier';
        $m->setFrom(FROM_EMAIL, 'Secretul Pisicii');
        $m->addAddress($data['email'], $data['fullName']);
        $m->Subject = "Confirmare Comanda #{$orderId} - Secretul Pisicii";
        $m->isHTML(true);
        $m->Body = "<div style='font-family:Arial;max-width:600px;margin:0 auto;'>
            <div style='background:#eb2571;padding:20px;text-align:center;'>
                <h1 style='color:#fff;margin:0;'>Multumim!</h1>
            </div>
            <div style='padding:20px;border:1px solid #eee;'>
                <p>Salut <b>{$data['fullName']}</b>,</p>
                <p>Comanda ta a fost inregistrata.</p>
                <div style='background:#f9fafb;padding:15px;border-radius:8px;margin:20px 0;'>
                    <h3 style='margin-top:0;'>Comanda #{$orderId}</h3>
                    <table style='width:100%;'>
                        <tr><td>Pachet:</td><td style='text-align:right;font-weight:bold;'>{$data['bundle']} buc</td></tr>
                        <tr><td>Produse:</td><td style='text-align:right;'>".floatval($data['price'])." Lei</td></tr>
                        <tr><td>Livrare ({$method}):</td><td style='text-align:right;'>{$shipping} Lei</td></tr>
                        <tr style='border-top:1px solid #ddd;'>
                            <td style='padding-top:10px;font-weight:bold;'>TOTAL:</td>
                            <td style='text-align:right;font-weight:bold;color:#eb2571;font-size:18px;'>{$total} Lei</td>
                        </tr>
                    </table>
                </div>
                <p><b>Adresa:</b> {$data['address']['line']}, {$data['address']['city']}, {$data['address']['county']}</p>
                <p style='font-size:14px;color:#666;'>Vei primi SMS de la curier in ziua livrarii.</p>
            </div></div>";
        $m->send();
        return true;
    } catch (Throwable $e) {
        logEvent("EMAIL_CLIENT_FAIL", $e->getMessage());
        return false;
    }
}

// ============================================================
// INPUT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metoda invalida.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['fullName']) || empty($data['phone'])) {
    echo json_encode(['success' => false, 'message' => 'Date incomplete.']);
    exit;
}

// ============================================================
// SAVE
// ============================================================
$orderId = saveOrderToDatabase($data);
if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Eroare interna. Incearca din nou.']);
    exit;
}

$shipping    = ($data['shippingMethod'] === 'easybox') ? floatval(SHIPPING_COST_EASYBOX) : floatval(SHIPPING_COST_GLS);
$totalAmount = floatval($data['price']) + $shipping;
logEvent("INFO", "Comanda #{$orderId} salvata | {$data['paymentMethod']} | {$totalAmount} RON | locker_details=" . ($data['lockerDetails'] ?? 'N/A'));

// ============================================================
// CARD — Netopia init
// ============================================================
if ($data['paymentMethod'] === 'card') {
    try {
        require_once __DIR__ . '/netopia_functions.php';
        $parts  = explode(' ', trim($data['fullName']), 2);
        $result = createPaymentSession([
            'orderId'   => $orderId,
            'amount'    => $totalAmount,
            'email'     => $data['email']             ?? '',
            'phone'     => $data['phone']             ?? '0700000000',
            'firstName' => $parts[0]                  ?? 'Client',
            'lastName'  => $parts[1]                  ?? 'Necunoscut',
            'address'   => [
                'city'   => $data['address']['city']   ?? 'Bucuresti',
                'county' => $data['address']['county'] ?? 'Bucuresti',
                'line'   => $data['address']['line']   ?? '',
            ],
        ]);
        logEvent("INFO", "[Netopia] paymentUrl obtinut #{$orderId}");
        echo json_encode(['success' => true, 'orderId' => $orderId, 'paymentUrl' => $result['paymentUrl']]);
    } catch (Throwable $e) {
        logEvent("ERROR", "Netopia Init esec #{$orderId}: " . $e->getMessage());
        try {
            $pdo = getDbConnection();
            if ($pdo) $pdo->prepare("DELETE FROM orders WHERE id=? AND payment_method='card' AND status='pending'")->execute([$orderId]);
        } catch (Throwable $x) {}
        echo json_encode(['success' => false, 'message' => 'Eroare initiere plata: ' . $e->getMessage()]);
    }
    exit; // STOP — email+auto vin din payment-webhook.php dupa IPN
}

// ============================================================
// RAMBURS — raspuns rapid + procesare in background
// ============================================================
ob_start();
echo json_encode([
    'success' => true,
    'message' => 'Comanda a fost inregistrata!',
    'data'    => [
        'orderId'       => $orderId,
        'total'         => $totalAmount,
        'paymentMethod' => $data['paymentMethod'],
    ],
]);
$size = ob_get_length();
header("Content-Encoding: none");
header("Content-Length: {$size}");
header("Connection: close");
ob_end_flush();
flush();
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

// BACKGROUND — identic oclar ramburs_create
logEvent("INFO", "Background ramburs #{$orderId}");
try { sendAdminEmail($data, $orderId, $shipping);  logEvent("INFO", "Email Admin #{$orderId}"); } catch (Throwable $e) { logEvent("ERROR", "Email admin: " . $e->getMessage()); }
try { sendClientEmail($data, $orderId, $shipping); logEvent("INFO", "Email Client #{$orderId}"); } catch (Throwable $e) { logEvent("ERROR", "Email client: " . $e->getMessage()); }
runAutomations($orderId, 'ramburs_create');
logEvent("INFO", "Procesare completa #{$orderId}");
exit;
?>