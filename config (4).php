<?php
// config.php - Citeste configuratia din .env

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            // FOLOSIM TRIM AICI PENTRU A ELIMINA SPAȚIILE INVIZIBILE
            $name = trim($name); 
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value"); 
                $_ENV[$name] = $value; 
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Incarcam .env
loadEnv(__DIR__ . '/.env');

// === DEFINIRE CONSTANTE (CU TRIM DE SIGURANȚĂ) ===

// DATABASE
define('DB_HOST', trim(getenv('DB_HOST') ?: 'localhost'));
define('DB_NAME', trim(getenv('DB_NAME')));
define('DB_USER', trim(getenv('DB_USER')));
define('DB_PASS', trim(getenv('DB_PASS')));

// EMAIL
define('SMTP_HOST', trim(getenv('SMTP_HOST')));
define('SMTP_PORT', trim(getenv('SMTP_PORT')));
define('SMTP_USER', trim(getenv('SMTP_USER')));
define('SMTP_PASS', trim(getenv('SMTP_PASS')));
define('ADMIN_EMAIL', trim(getenv('ADMIN_EMAIL')));
define('FROM_EMAIL', trim(getenv('SMTP_USER')));
define('FROM_NAME', 'Secretul Pisicii');

// OBLIO
define('OBLIO_EMAIL', trim(getenv('OBLIO_EMAIL')));
define('OBLIO_API_SECRET', trim(getenv('OBLIO_API_SECRET')));
define('OBLIO_CUI_FIRMA', trim(getenv('OBLIO_CUI')));
define('OBLIO_SERIE', trim(getenv('OBLIO_SERIE')));

// ECOLET
define('ECOLET_CLIENT_ID', trim(getenv('ECOLET_CLIENT_ID')));
define('ECOLET_CLIENT_SECRET', trim(getenv('ECOLET_CLIENT_SECRET')));
define('ECOLET_USERNAME', trim(getenv('ECOLET_USERNAME')));
define('ECOLET_PASSWORD', trim(getenv('ECOLET_PASSWORD')));
define('ECOLET_BASE_URL', trim(getenv('ECOLET_BASE_URL')));

// ECOLET SENDER
define('ECOLET_SENDER_NAME', trim(getenv('ECOLET_SENDER_NAME') ?: 'Secretul Pisicii'));
define('ECOLET_SENDER_COUNTY', trim(getenv('ECOLET_SENDER_COUNTY')));
define('ECOLET_SENDER_CITY', trim(getenv('ECOLET_SENDER_CITY')));
define('ECOLET_SENDER_STREET', trim(getenv('ECOLET_SENDER_STREET')));
define('ECOLET_SENDER_POSTAL', trim(getenv('ECOLET_SENDER_POSTAL')));
define('ECOLET_SENDER_LOCALITY_ID', trim(getenv('ECOLET_SENDER_LOCALITY_ID'))); 

// NETOPIA
define('NETOPIA_MERCHANT_ID', trim(getenv('NETOPIA_MERCHANT_ID')));
define('NETOPIA_API_KEY', trim(getenv('NETOPIA_API_KEY')));
define('NETOPIA_SIGNATURE_KEY', trim(getenv('NETOPIA_SIGNATURE_KEY')));
define('NETOPIA_SANDBOX', trim(getenv('NETOPIA_SANDBOX')) === 'true');
define('NETOPIA_URL', NETOPIA_SANDBOX
    ? 'https://secure.sandbox.netopia-payments.com/payment/card/start'
    : 'https://secure.netopia-payments.com/payment/card/start'
);

// GOOGLE & EASYBOX
define('GOOGLE_PLACES_API_KEY', trim(getenv('GOOGLE_PLACES_API_KEY')));
define('EASYBOX_API_KEY', trim(getenv('EASYBOX_API_KEY')));
define('EASYBOX_SANDBOX', trim(getenv('EASYBOX_SANDBOX')) === 'true');
define('EASYBOX_API_URL', EASYBOX_SANDBOX
    ? 'https://sandbox.easybox.bg/api'
    : 'https://api.easybox.bg/api'
);

// SHIPPING
define('SHIPPING_COST_GLS', floatval(trim(getenv('SHIPPING_COST_GLS') ?: 14.00)));
define('SHIPPING_COST_EASYBOX', floatval(trim(getenv('SHIPPING_COST_EASYBOX') ?: 10.00)));
define('SHIPPING_COST', SHIPPING_COST_GLS);

// ADMIN
define('ADMIN_USER', trim(getenv('ADMIN_USER') ?: 'admin'));
define('ADMIN_PASS', trim(getenv('ADMIN_PASS') ?: 'pisica2024'));
define('ADMIN_URL', trim(getenv('ADMIN_URL')));
define('SITE_URL', trim(getenv('SITE_URL') ?: 'https://secretulpisicii.alvoro.ro'));

// LOGGING
define('LOG_ORDERS', __DIR__ . '/logs/orders.log');
define('LOG_PAYMENTS', __DIR__ . '/logs/payments.log');
define('LOG_ERRORS', __DIR__ . '/logs/errors.log');

if (!function_exists('systemLog')) {
    function systemLog($file, $message) {
        $dir = dirname($file);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $timestamp = date('Y-m-d H:i:s');
        $line = "[$timestamp] $message" . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND);
    }
}
?>