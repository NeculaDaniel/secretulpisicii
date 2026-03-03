<?php
/**
 * automation.php
 * Echivalent PHP exact al runAutomations() + getAllAutomationSettings() din oclar/api/index.js
 *
 * 3 setari (identice cu oclar):
 *   automation_enabled  — master switch
 *   auto_oblio          — facturare automata
 *   auto_ecolet         — AWB automat
 */
require_once __DIR__ . '/config.php';

if (!function_exists('getDbConnection')) {
    function getDbConnection() {
        try {
            return new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) { return null; }
    }
}

// identic: async function getAllAutomationSettings()
function getAllAutomationSettings() {
    $pdo = getDbConnection();
    if (!$pdo) return [];
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value VARCHAR(255) NOT NULL DEFAULT 'false',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $rows = $pdo->query("SELECT setting_key, setting_value FROM admin_settings")->fetchAll();
        $s = [];
        foreach ($rows as $r) $s[$r['setting_key']] = ($r['setting_value'] === 'true');
        return $s;
    } catch (PDOException $e) { return []; }
}

// identic: POST /api/admin/settings — UPSERT
function updateAutomationSetting($key, $value) {
    $pdo = getDbConnection();
    if (!$pdo) return false;
    try {
        $v = $value ? 'true' : 'false';
        $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?, updated_at=NOW()")
            ->execute([$key, $v, $v]);
        return true;
    } catch (PDOException $e) { return false; }
}

// identic: async function runAutomations(orderId, source)
function runAutomations($orderId, $source) {
    systemLog(LOG_ORDERS, "[Auto] Verificare automatizari #$orderId ($source)...");

    $settings = getAllAutomationSettings();

    $autoEnabled = $settings['automation_enabled'] ?? false;
    if (!$autoEnabled) {
        systemLog(LOG_ORDERS, "[Auto] Master switch: OPRIT.");
        return;
    }

    $autoOblio  = $settings['auto_oblio']  ?? true;
    $autoEcolet = $settings['auto_ecolet'] ?? true;
    systemLog(LOG_ORDERS, "[Auto] Master=ON | Oblio=" . ($autoOblio?'true':'false') . " | Ecolet=" . ($autoEcolet?'true':'false'));

    $pdo = getDbConnection();
    if (!$pdo) return;

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) { systemLog(LOG_ORDERS, "[Auto] Comanda #$orderId nu exista."); return; }

    // --- A. OBLIO — identic oclar: if (autoOblio && !order.oblio_invoice_id)
    if ($autoOblio && $order['oblio_status'] == 0) {
        systemLog(LOG_ORDERS, "[Auto] Generare factura Oblio #$orderId...");
        try {
            if (!function_exists('sendToOblio')) require_once __DIR__ . '/oblio_functions.php';
            global $pdo;
            $msg = sendToOblio($orderId);
            systemLog(LOG_ORDERS, "[Auto] Oblio SUCCESS: $msg");
        } catch (Throwable $e) {
            systemLog(LOG_ERRORS, "[Auto] Oblio FAILED #$orderId: " . $e->getMessage());
        }
    } elseif ($autoOblio && $order['oblio_status'] == 1) {
        systemLog(LOG_ORDERS, "[Auto] Oblio: Factura deja exista #$orderId.");
    }

    // --- B. ECOLET — identic oclar: if (autoEcolet && !order.ecolet_shipment_id)
    if ($autoEcolet && empty($order['awb_number'])) {
        systemLog(LOG_ORDERS, "[Auto] Creare shipment Ecolet #$orderId...");
        try {
            if (!function_exists('generateEcoletAWB')) require_once __DIR__ . '/ecolet_functions.php';
            global $pdo;
            $msg = generateEcoletAWB($orderId);
            systemLog(LOG_ORDERS, "[Auto] Ecolet SUCCESS: $msg");
        } catch (Throwable $e) {
            systemLog(LOG_ERRORS, "[Auto] Ecolet FAILED #$orderId: " . $e->getMessage());
        }
    } elseif ($autoEcolet && !empty($order['awb_number'])) {
        systemLog(LOG_ORDERS, "[Auto] Ecolet: Shipment deja exista #$orderId.");
    }

    systemLog(LOG_ORDERS, "[Auto] Automatizare completa #$orderId.");
}
?>
