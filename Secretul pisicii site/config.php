<?php
// config.php - Configurare Centralizata Secretul Pisicii
// Acest fisier contine toate parolele si setarile importante.

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// --- 1. BAZA DE DATE (Din vechiul config.php) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'alvoro_r1_admin');
define('DB_USER', 'alvoro_r1_user');
define('DB_PASS', 'Parola2020@');

// --- 2. EMAIL (Din vechiul config.php - App Password) ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'alvoro.enterprise@gmail.com');
define('SMTP_PASS', 'muxtrnkxpnxqqfjq'); // Parola de aplicatie existenta
define('ADMIN_EMAIL', 'alvoro.enterprise@gmail.com');
define('FROM_EMAIL', 'alvoro.enterprise@gmail.com');
define('FROM_NAME', 'Secretul Pisicii');

// --- 3. ADMIN & SETARI ---
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'pisica2024');
define('ADMIN_URL', 'https://secretulpisicii.alvoro.ro/admin.php'); 
define('SHIPPING_COST', 14.00);

// --- 4. OBLIO (Din vechiul oblio_functions.php) ---
define('OBLIO_EMAIL', 'david.altafini@gmail.com');
define('OBLIO_API_SECRET', '9b8deadd81e5fa6017575ec822820740a44306c1');
define('OBLIO_CUI_FIRMA', '53181323');
define('OBLIO_SERIE', 'ALT'); // Seria setata in Oblio
?>
