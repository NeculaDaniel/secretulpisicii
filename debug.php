<?php
// debug.php - Script de diagnosticare Secretul Pisicii
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🛠️ Diagnosticare Server - Secretul Pisicii</h1>";
echo "<style>body{font-family:sans-serif; background:#f4f4f4; padding:20px;} .box{background:#fff; padding:15px; margin-bottom:15px; border-radius:5px; border-left:5px solid #ccc;} .ok{border-left-color:green;} .err{border-left-color:red;} pre{background:#eee; padding:10px; overflow:auto;}</style>";

// 1. TESTARE CONFIG & .ENV
echo "<div class='box'>"; 
echo "<h3>1. Verificare Configurare (.env)</h3>";
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    echo "<p style='color:green'>✅ config.php încărcat.</p>";
    
    $checks = [
        'OBLIO_EMAIL' => OBLIO_EMAIL,
        'OBLIO_API_SECRET' => OBLIO_API_SECRET,
        'ECOLET_USERNAME' => ECOLET_USERNAME,
        'SMTP_HOST' => SMTP_HOST
    ];

    foreach ($checks as $key => $val) {
        $len = strlen($val);
        echo "Checking <strong>$key</strong>: Lungime = $len caractere. ";
        // Verificăm spații ascunse
        if (trim($val) !== $val) {
            echo "<span style='color:red'>⚠️ ATENȚIE: Are spații invizibile la capete!</span><br>";
        } else {
            echo "<span style='color:green'>OK</span><br>";
        }
    }
} else {
    echo "<p style='color:red'>❌ config.php LIPSEȘTE!</p>";
    exit;
}
echo "</div>";

// 2. TESTARE PERMISIUNI CACHE
echo "<div class='box'>";
echo "<h3>2. Testare Cache (Folder Scriere)</h3>";
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    echo "Folderul /cache nu există. Încerc să-l creez... ";
    if (@mkdir($cacheDir, 0755, true)) {
        echo "<span style='color:green'>Creat cu succes!</span>";
    } else {
        echo "<span style='color:red'>EȘEC! Nu am permisiuni să creez folderul. Creează manual folderul 'cache' și dă-i permisiuni 777.</span>";
    }
} else {
    echo "Folderul /cache există. ";
    if (is_writable($cacheDir)) {
        echo "<span style='color:green'>✅ Este inscriptibil.</span>";
    } else {
        echo "<span style='color:red'>❌ NU se poate scrie în el (Permission Denied).</span>";
    }
}
echo "</div>";

// 3. TESTARE DNS & CURL (ECOLET)
echo "<div class='box'>";
echo "<h3>3. Testare Conexiune ECOLET (DNS & SSL)</h3>";

$url = "https://app.ecolet.ro/api/v1/shipments";
echo "Încerc conectare la: $url <br>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Doar check
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
// TESTĂM IPV4 FORȚAT
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$start = microtime(true);
$result = curl_exec($ch);
$end = microtime(true);
$error = curl_error($ch);
$errno = curl_errno($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($errno) {
    echo "<p style='color:red'>❌ Eroare cURL: $error (Cod: $errno)</p>";
    echo "<p>Sugestie: Serverul tău nu poate rezolva DNS-ul 'app.ecolet.ro'. Contactează hostingul.</p>";
} else {
    echo "<p style='color:green'>✅ Conexiune REUȘITĂ în " . round($end - $start, 2) . " secunde.</p>";
    echo "HTTP Code: " . $info['http_code'] . "<br>";
    echo "IP Rezolvat: " . $info['primary_ip'] . "<br>";
}
echo "</div>";

// 4. TESTARE TOKEN OBLIO
echo "<div class='box'>";
echo "<h3>4. Testare Generare Token OBLIO</h3>";

$ch = curl_init("https://www.oblio.eu/api/v1/authorize/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => OBLIO_EMAIL,
    'client_secret' => OBLIO_API_SECRET,
    'grant_type' => 'client_credentials'
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$res = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($err) {
    echo "<p style='color:red'>❌ Eroare cURL Oblio: $err</p>";
} else {
    $json = json_decode($res, true);
    if (isset($json['access_token'])) {
        echo "<p style='color:green'>✅ Token Generat cu Succes!</p>";
        echo "<textarea style='width:100%; height:50px; font-size:10px;'>" . $json['access_token'] . "</textarea>";
    } else {
        echo "<p style='color:red'>❌ Refuzat de Oblio:</p>";
        echo "<pre>" . htmlspecialchars($res) . "</pre>";
        echo "Verifică Email și API Secret în .env!";
    }
}
echo "</div>";

// 5. TESTARE MAIL (SMTP)
echo "<div class='box'>";
echo "<h3>5. Testare Conexiune SMTP (Gmail)</h3>";
echo "Host: " . SMTP_HOST . ":" . SMTP_PORT . "<br>";
echo "User: " . SMTP_USER . "<br>";

$fp = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 5);
if ($fp) {
    echo "<p style='color:green'>✅ Portul SMTP este DESCHIS.</p>";
    fclose($fp);
} else {
    echo "<p style='color:red'>❌ Portul SMTP este ÎNCHIS/BLOCAT ($errno - $errstr).</p>";
    echo "Hostingul blochează portul 587. Încearcă 465 sau cere deblocare.";
}
echo "</div>";

?>