<?php
// config.php - Citeste configuratia din .env

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// Functie simpla de incarcare .env (fara librarii externe)
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name); $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("$name=$value"); $_ENV[$name] = $value; $_SERVER[$name] = $value;
        }
    }
}

// Incarcam .env
loadEnv(__DIR__ . '/.env');

// Definim constantele folosite in site
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL'));
define('FROM_EMAIL', getenv('SMTP_USER'));
define('FROM_NAME', 'Secretul Pisicii');

define('OBLIO_EMAIL', getenv('OBLIO_EMAIL'));
define('OBLIO_API_SECRET', getenv('OBLIO_API_SECRET'));
define('OBLIO_CUI_FIRMA', getenv('OBLIO_CUI'));
define('OBLIO_SERIE', getenv('OBLIO_SERIE'));

// Date E-Colet (le pregatim pentru pasul 2)
define('ECOLET_CLIENT_ID', getenv('ECOLET_CLIENT_ID'));
define('ECOLET_CLIENT_SECRET', getenv('ECOLET_CLIENT_SECRET'));
define('ECOLET_USERNAME', getenv('ECOLET_USERNAME'));
define('ECOLET_PASSWORD', getenv('ECOLET_PASSWORD'));
define('ECOLET_SENDER_NAME', 'Secretul Pisicii');

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'pisica2024');
define('ADMIN_URL', 'https://secretulpisicii.alvoro.ro/admin.php'); 
define('SHIPPING_COST', 14.00);
?>