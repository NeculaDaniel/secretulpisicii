<?php
/**
 * NETOPIA PAYMENT CALLBACKS
 * Pagini de redirecționare după plată
 */

require_once __DIR__ . '/config.php';

if (!function_exists('getDbConnection')) {
    function getDbConnection() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            return new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('logEvent')) {
    function logEvent($file, $message) {
        $dir = defined('LOG_ORDERS') ? dirname(LOG_ORDERS) : __DIR__ . '/logs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents($file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
}

// ==========================================
// 1. RETURN PAGE (payment-return.php)
// ==========================================

if (basename($_SERVER['PHP_SELF']) == 'payment-return.php') {

    $orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;

    if (!$orderId) {
        header('Location: /');
        exit;
    }

    $pdo = getDbConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT payment_status FROM orders WHERE id=?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if ($order && $order['payment_status'] === 'paid') {
                $status = 'success';
                $message = 'Plata a fost confirmată! Comanda ta va fi procesată în curând.';
            } else {
                $status = 'pending';
                $message = 'Plata ta este în curs de procesare. Te vom contacta în scurt timp.';
            }
        } catch (Exception $e) {
            $status = 'error';
            $message = 'Eroare la verificarea plății.';
        }
    } else {
        $status = 'error';
        $message = 'Eroare de conexiune.';
    }

    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $status === 'success' ? 'Plată Confirmată' : 'Plată în Curs'; ?></title>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
            .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; text-align: center; }
            .icon { font-size: 60px; margin-bottom: 20px; }
            h1 { color: #1f2937; margin-bottom: 10px; font-size: 28px; }
            p { color: #6b7280; font-size: 16px; line-height: 1.6; margin-bottom: 30px; }
            .btn { display: inline-block; padding: 12px 30px; background: #eb2571; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
            .btn:hover { background: #c91e57; }
            .order-id { background: #f3f4f6; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; color: #eb2571; }
        </style>
    </head>
    <body>
        <div class="container">
            <?php if ($status === 'success'): ?>
                <div class="icon">✅</div>
                <h1>Plată Confirmată!</h1>
                <div class="order-id">Comandă #<?php echo $orderId; ?></div>
                <p><?php echo $message; ?></p>
            <?php else: ?>
                <div class="icon">⏳</div>
                <h1>Plată în Curs</h1>
                <div class="order-id">Comandă #<?php echo $orderId; ?></div>
                <p><?php echo $message; ?></p>
            <?php endif; ?>
            <a href="/" class="btn">Înapoi la Site</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// 2. WEBHOOK (payment-webhook.php)
// Netopia trimite confirmare la serverul nostru
// ==========================================

if (basename($_SERVER['PHP_SELF']) == 'payment-webhook.php') {

    // Identic cu oclar /api/netopia/confirm
    $rawInput = file_get_contents('php://input');
    $reqBody  = json_decode($rawInput, true);

    systemLog(LOG_PAYMENTS, "--------------- NETOPIA IPN (REST) ---------------");

    try {
        require_once __DIR__ . '/netopia_functions.php';
        require_once __DIR__ . '/automation.php';

        $paymentInfo = validatePaymentNotification($reqBody);

        if ($paymentInfo['success']) {
            $orderId = $paymentInfo['orderId'];
            systemLog(LOG_PAYMENTS, "PLATA CONFIRMATA: Comanda #$orderId | " . $paymentInfo['message']);

            $pdo = getDbConnection();
            // UPDATE status — identic oclar
            $pdo->prepare("UPDATE orders SET payment_status='paid', payment_method='card' WHERE id=?")
                ->execute([$orderId]);

            // Email confirmare card
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            if ($order && !empty($order['email'])) {
                try {
                    if (file_exists(__DIR__ . '/PHPMailer-master/src/PHPMailer.php')) {
                        require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
                        require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
                        require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
                    }
                    $shipping = ($order['shipping_method'] === 'easybox') ? floatval(SHIPPING_COST_EASYBOX) : floatval(SHIPPING_COST_GLS);
                    $total    = floatval($order['total_price']);
                    $method   = ($order['shipping_method'] === 'easybox') ? 'EasyBox Locker' : 'GLS Curier';

                    $sendMail = function($to, $name, $subject, $body) {
                        $m = new PHPMailer\PHPMailer\PHPMailer(true);
                        $m->isSMTP(); $m->Host=SMTP_HOST; $m->SMTPAuth=true;
                        $m->Username=SMTP_USER; $m->Password=SMTP_PASS;
                        $m->SMTPSecure=PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $m->Port=intval(SMTP_PORT); $m->CharSet='UTF-8'; $m->Timeout=10;
                        $m->setFrom(FROM_EMAIL, FROM_NAME);
                        $m->addAddress($to, $name);
                        $m->Subject=$subject; $m->isHTML(true); $m->Body=$body;
                        $m->send();
                    };

                    $bodyClient = "<div style='font-family:Arial;max-width:600px;margin:0 auto;'>
                        <div style='background:#eb2571;padding:20px;text-align:center;'><h1 style='color:#fff;margin:0;'>Plata Confirmata!</h1></div>
                        <div style='padding:20px;border:1px solid #eee;'>
                            <p>Salut <b>{$order['full_name']}</b>,</p>
                            <p>Plata ta a fost confirmata! Comanda se pregateste pentru expediere.</p>
                            <div style='background:#f9fafb;padding:15px;border-radius:8px;margin:20px 0;'>
                                <h3 style='margin-top:0;'>Comanda #$orderId</h3>
                                <p><b>Total achitat:</b> <span style='color:#eb2571;font-size:18px;'>$total Lei</span></p>
                                <p><b>Livrare:</b> $method</p>
                                <p><b>Adresa:</b> {$order['address_line']}, {$order['city']}, {$order['county']}</p>
                            </div>
                            <p style='font-size:14px;color:#666;'>Vei primi SMS de la curier in ziua livrarii.</p>
                        </div></div>";
                    $sendMail($order['email'], $order['full_name'], "Plata Confirmata - Comanda #$orderId", $bodyClient);

                    $bodyAdmin = "<div style='font-family:Arial;padding:20px;'>
                        <h2 style='color:#eb2571;'>Plata Card Confirmata #$orderId</h2>
                        <p><b>{$order['full_name']}</b> | {$order['phone']} | {$order['email']}</p>
                        <p>Total: <b>$total Lei</b> | $method</p>
                        <p>{$order['address_line']}, {$order['city']}, {$order['county']}</p></div>";
                    $sendMail(ADMIN_EMAIL, 'Admin', "Plata Card #$orderId - $total RON", $bodyAdmin);

                    systemLog(LOG_PAYMENTS, "Email confirmare trimis #$orderId");
                } catch (Throwable $e) {
                    systemLog(LOG_ERRORS, "Email card #$orderId: " . $e->getMessage());
                }
            }

            // runAutomations — identic oclar netopia_confirm
            runAutomations($orderId, 'netopia_confirm');

        } else {
            systemLog(LOG_PAYMENTS, "PLATA NE-CONFIRMATA: " . $paymentInfo['message']);
        }

        // Raspuns JSON pentru Netopia — identic oclar: { error: { code: 0, message: "success" } }
        header('Content-Type: application/json');
        echo json_encode(['error' => ['code' => 0, 'message' => 'success']]);

    } catch (Throwable $e) {
        systemLog(LOG_ERRORS, "Eroare procesare IPN: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => ['code' => 1, 'message' => $e->getMessage()]]);
    }

    exit;
}
// ==========================================
// 3. CANCEL PAGE (payment-cancel.php)
// ==========================================

if (basename($_SERVER['PHP_SELF']) == 'payment-cancel.php') {

    $orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;

    if ($orderId) {
        $pdo = getDbConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET payment_status='cancelled' WHERE id=?");
                $stmt->execute([$orderId]);
                logEvent(LOG_PAYMENTS, "Payment cancelled by user: #$orderId");
            } catch (Exception $e) {
                // Silent fail
            }
        }
    }

    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Plată Anulată</title>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
            .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; text-align: center; }
            .icon { font-size: 60px; margin-bottom: 20px; }
            h1 { color: #1f2937; margin-bottom: 10px; }
            p { color: #6b7280; margin-bottom: 30px; }
            .btn { display: inline-block; padding: 12px 30px; background: #f5576c; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
            .btn:hover { background: #f093fb; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">❌</div>
            <h1>Plată Anulată</h1>
            <p>Ai anulat procesul de plată. Comanda nu a fost finalizată.</p>
            <p>Dacă dorești, poți plasa comanda din nou cu <strong>plată la livrare</strong> sau să încerci o nouă plată cu card.</p>
            <a href="/#order" class="btn">Încearcă Din Nou</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// 4. CONFIRM PAGE (payment-confirm.php)
// ==========================================

if (basename($_SERVER['PHP_SELF']) == 'payment-confirm.php') {

    $orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;

    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Procesare Plată...</title>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
            .container { text-align: center; }
            .spinner { width: 50px; height: 50px; border: 4px solid #e5e7eb; border-top-color: #eb2571; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px; }
            @keyframes spin { to { transform: rotate(360deg); } }
            h1 { color: #1f2937; margin-bottom: 10px; }
            p { color: #6b7280; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="spinner"></div>
            <h1>Se Procesează Plata...</h1>
            <p>Comandă #<?php echo $orderId; ?></p>
            <p style="font-size: 14px; color: #9ca3af;">Nu închide browserul. Vei fi redirecționat în scurt timp.</p>
            <script>
                function checkPayment() {
                    fetch('/order-api.php?action=check_payment&orderId=<?php echo $orderId; ?>')
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'paid') {
                                window.location.href = '/payment-return.php?orderId=<?php echo $orderId; ?>';
                            } else {
                                setTimeout(checkPayment, 3000);
                            }
                        })
                        .catch(() => setTimeout(checkPayment, 3000));
                }
                setTimeout(checkPayment, 5000);
            </script>
        </div>
    </body>
    </html>
    <?php
    exit;
}

header('Location: /');
exit;
?>