<?php
// Aceasta linie blocheaza accesul direct daca cineva incearca sa deschida fisierul
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// --- BAZA DE DATE ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'alvoro_r1_admin');
define('DB_USER', 'alvoro_r1_user');
define('DB_PASS', 'Parola2020@');

// --- EMAIL ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'alvoro.enterprise@gmail.com');
define('SMTP_PASS', 'muxtrnkxpnxqqfjq');
define('ADMIN_EMAIL', 'alvoro.enterprise@gmail.com');

// --- ADMIN ---
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'pisica2024');

// --- API ---
define('GLS_USER', '');
define('GLS_PASS', '');
define('OBLIO_EMAIL', '');
define('OBLIO_API_SECRET', '');
define('OBLIO_CIF', '');
?>