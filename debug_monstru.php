<?php
/**
 * DEBUG MONSTRU - Secretul Pisicii
 * AJAX HANDLERS SUNT PRIMII - asta e fix-ul principal
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(60);
require_once __DIR__ . '/config.php';

// ============================================================
// AJAX - PRIMUL lucru verificat, INAINTE de orice HTML
// Problema anterioara: AJAX era dupa exit() => PHP returna HTML
// => JS primea "<" in loc de JSON => SyntaxError
// ============================================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $action = $_GET['action'];

    if ($action === 'test_oblio_token') {
        require_once __DIR__ . '/oblio_functions.php';
        try {
            $token = getOblioToken();
            echo json_encode(['success' => true, 'message' => 'TOKEN OK: ' . substr($token,0,20) . '...' . substr($token,-6)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'test_oblio_invoice') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'ID lipsa']); exit; }
        require_once __DIR__ . '/oblio_functions.php';
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $msg  = sendToOblio($id);
            $stmt = $pdo->prepare("SELECT oblio_link FROM orders WHERE id=?");
            $stmt->execute([$id]);
            $row  = $stmt->fetch();
            echo json_encode(['success'=>true,'message'=>$msg,'link'=>$row['oblio_link']??'']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'test_ecolet') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'ID lipsa']); exit; }
        require_once __DIR__ . '/ecolet_functions.php';
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $msg = generateEcoletAWB($id);
            echo json_encode(['success'=>true,'message'=>$msg]);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    // Test Oblio raw - arata exact ce trimite si primeste
    if ($action === 'test_oblio_raw') {
        $email  = defined('OBLIO_EMAIL')      ? trim(OBLIO_EMAIL)      : '';
        $secret = defined('OBLIO_API_SECRET') ? trim(OBLIO_API_SECRET) : '';
        // Testeaza si variante diferite ca sa vedem care merge
        $variant = $_GET['variant'] ?? 'json';
        $ch = curl_init('https://www.oblio.eu/api/authorize/token');
        if ($variant === 'form') {
            // Varianta form-encoded cu body
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query(['client_id'=>$email,'client_secret'=>$secret,'grant_type'=>'client_credentials']),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
        } else {
            // Varianta JSON body (standard Oblio)
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['client_id'=>$email,'client_secret'=>$secret]),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json','Accept: application/json'],
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
        }
        $resp    = curl_exec($ch);
        $httpCode= curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        echo json_encode([
            'success'   => ($httpCode===200),
            'http_code' => $httpCode,
            'curl_error'=> $curlErr,
            'response'  => json_decode($resp, true),
            'email_used'=> $email,
            'secret_len'=> strlen($secret),
        ]);
        exit;
    }

    if ($action === 'test_ecolet_raw') {
        $clientId     = defined('ECOLET_CLIENT_ID')     ? ECOLET_CLIENT_ID     : '';
        $clientSecret = defined('ECOLET_CLIENT_SECRET') ? ECOLET_CLIENT_SECRET : '';
        $baseUrl      = defined('ECOLET_BASE_URL')      ? rtrim(ECOLET_BASE_URL, '/') : 'https://panel.ecolet.ro/api/v1';
        $tokenUrl     = $baseUrl . '/oauth/token';
        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        echo json_encode([
            'success'    => ($httpCode === 200),
            'http_code'  => $httpCode,
            'curl_error' => $curlErr,
            'token_url'  => $tokenUrl,
            'response'   => json_decode($resp, true),
        ]);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'actiune necunoscuta']);
    exit;
}

// ============================================================
// DATE PAGINA
// ============================================================
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $dbOk   = true;
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $totalO = in_array('orders',$tables) ? $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() : 0;
    $last   = in_array('orders',$tables) ? $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) : null;
    $dbErr  = '';
} catch (PDOException $e) {
    $pdo=null; $dbOk=false; $tables=[]; $totalO=0; $last=null; $dbErr=$e->getMessage();
}

function ok($m)  { echo "<div class='box ok'><span>✅</span> $m</div>"; }
function bad($m) { echo "<div class='box bad'><span>❌</span> $m</div>"; }
function wrn($m) { echo "<div class='box wrn'><span>⚠️</span> $m</div>"; }
function inf($m) { echo "<div class='box inf'><span>ℹ️</span> $m</div>"; }
function sec($t) { echo "<h2>$t</h2>"; }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Debug - Secretul Pisicii</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;padding:20px;font-size:14px}
h1{color:#eb2571;margin-bottom:4px;font-size:1.6rem}
.sub{color:#64748b;margin-bottom:20px;font-size:13px}
h2{color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:2px;border-top:1px solid #1e293b;padding:16px 0 8px;margin-top:12px}
.box{display:flex;align-items:flex-start;gap:10px;background:#1e293b;border-radius:6px;padding:10px 14px;margin-bottom:6px;border-left:4px solid #334155;line-height:1.5}
.ok{border-left-color:#22c55e}
.bad{border-left-color:#ef4444;background:#1f0d0d}
.wrn{border-left-color:#f59e0b}
.inf{border-left-color:#3b82f6}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
@media(max-width:700px){.grid{grid-template-columns:1fr}}
pre{background:#0f172a;border:1px solid #334155;padding:10px;border-radius:6px;font-size:12px;color:#a5b4c8;overflow-x:auto;white-space:pre-wrap;margin:8px 0}
.btn{display:inline-block;padding:8px 16px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;border:none;margin:4px 4px 0 0}
.roz{background:#eb2571;color:#fff}
.blau{background:#3b82f6;color:#fff}
.verd{background:#22c55e;color:#000}
.oran{background:#f59e0b;color:#000}
.res{background:#0f172a;border:1px solid #334155;border-radius:6px;padding:10px;margin-top:8px;font-size:13px;color:#a5b4c8;display:none;white-space:pre-wrap}
code{background:#0f172a;padding:2px 6px;border-radius:4px;font-size:12px}
</style>
</head>
<body>
<h1>🛠️ Debug Monstru — Secretul Pisicii</h1>
<p class="sub"><strong style="color:#ef4444">Sterge fisierul dupa diagnosticare!</strong> Contine date sensibile.</p>

<?php
// ---- 1. ENV ----
sec("1. Variabile .env / config.php");
$vars = [
    'DB_HOST'=>DB_HOST,'DB_NAME'=>DB_NAME,'DB_USER'=>DB_USER,'DB_PASS'=>DB_PASS,
    'SMTP_HOST'=>SMTP_HOST,'SMTP_PORT'=>SMTP_PORT,'SMTP_USER'=>SMTP_USER,'SMTP_PASS'=>SMTP_PASS,
    'ADMIN_EMAIL'=>ADMIN_EMAIL,
    'OBLIO_EMAIL'=>OBLIO_EMAIL,'OBLIO_API_SECRET'=>OBLIO_API_SECRET,'OBLIO_CUI_FIRMA'=>OBLIO_CUI_FIRMA,'OBLIO_SERIE'=>OBLIO_SERIE,
    'ECOLET_USERNAME'=>ECOLET_USERNAME,'ECOLET_PASSWORD'=>ECOLET_PASSWORD,
    'ECOLET_SENDER_NAME'=>ECOLET_SENDER_NAME,'ECOLET_SENDER_COUNTY'=>ECOLET_SENDER_COUNTY,
    'ECOLET_SENDER_CITY'=>ECOLET_SENDER_CITY,'ECOLET_SENDER_STREET'=>ECOLET_SENDER_STREET,
    'ECOLET_SENDER_POSTAL'=>ECOLET_SENDER_POSTAL,'ECOLET_SENDER_LOCALITY_ID'=>ECOLET_SENDER_LOCALITY_ID,
    'SHIPPING_COST_GLS'=>SHIPPING_COST_GLS,'SHIPPING_COST_EASYBOX'=>SHIPPING_COST_EASYBOX,
];
echo "<div class='grid'>";
foreach ($vars as $k => $v) {
    $sens = preg_match('/PASS|SECRET|KEY/', $k);
    $disp = $sens ? substr($v,0,4).'****'.substr($v,-3) : htmlspecialchars((string)$v);
    $v2 = (string)$v;
    if (empty(trim($v2)))    bad("<strong>$k</strong> = <em>GOL!</em>");
    elseif(trim($v2)!=$v2)  wrn("<strong>$k</strong> = '$disp' <small style='color:#f59e0b'>SPATII LA CAPETE!</small>");
    else                     ok("<strong>$k</strong> = $disp");
}
echo "</div>";

// ---- 2. DB ----
sec("2. Baza de Date MySQL");
if ($dbOk) {
    ok("MySQL OK — <strong>".DB_HOST."/".DB_NAME."</strong>");
    ok("Tabele: <strong>".implode(', ',$tables)."</strong>");
    if (in_array('orders',$tables)) {
        ok("Tabel <strong>orders</strong> — $totalO comenzi");
        if ($last) {
            inf("Ultima: <strong>#".$last['id']."</strong> | ".htmlspecialchars($last['full_name'])
               ." | ".$last['total_price']." Lei | ".$last['payment_method']
               ." | AWB: ".($last['awb_number']??'lipsa')
               ." | Oblio: ".(($last['oblio_status']??0)?'✅':'❌'));
            $miss = array_filter(['awb_number','ecolet_status','oblio_status','oblio_link','payment_status','easybox_locker_id','postal_code','shipping_method'],
                fn($c) => !array_key_exists($c,$last));
            if ($miss) bad("Coloane LIPSA: <strong>".implode(', ',$miss)."</strong>");
            else ok("Toate coloanele necesare exista");
        }
    } else {
        bad("Tabelul <strong>orders</strong> NU exista!");
    }
} else {
    bad("MySQL ESUAT: ".htmlspecialchars($dbErr));
}

// ---- 3. OBLIO ----
sec("3. Oblio — Facturare");
if (file_exists(__DIR__.'/oblio_functions.php')) ok("oblio_functions.php gasit");
else bad("oblio_functions.php LIPSESTE!");
$cd = __DIR__.'/cache';
if (!is_dir($cd)) @mkdir($cd,0755,true);
if (is_dir($cd)&&is_writable($cd)) ok("Folder <strong>cache/</strong> OK");
else bad("Folder <strong>cache/</strong> NU e inscriptibil — chmod 755");
inf("Email: <strong>".OBLIO_EMAIL."</strong> | Secret length: <strong>".strlen(OBLIO_API_SECRET)." chars</strong> | CUI: <strong>".OBLIO_CUI_FIRMA."</strong> | Serie: <strong>".OBLIO_SERIE."</strong>");
?>
<div style="margin-top:10px">
    <button class="btn oran" onclick="ajax('?action=test_oblio_raw&variant=json','oblio-raw')">🔍 Test RAW JSON body</button>
    <button class="btn oran" onclick="ajax('?action=test_oblio_raw&variant=form','oblio-raw2')">🔍 Test RAW form-encoded</button>
    <div id="oblio-raw" class="res"></div>
    <div id="oblio-raw2" class="res"></div>
    <br>
    <button class="btn roz" onclick="ajax('?action=test_oblio_token','oblio-tok')">🔑 Testeaza Token Oblio</button>
    <div id="oblio-tok" class="res"></div>
</div>
<?php if ($last): ?>
<div style="margin-top:8px">
    <button class="btn verd" onclick="ajax('?action=test_oblio_invoice&id=<?=$last['id']?>','oblio-inv')">🧾 Emite Factura pt #<?=$last['id']?></button>
    <div id="oblio-inv" class="res"></div>
</div>
<?php endif; ?>

<?php
// ---- 4. ECOLET ----
sec("4. Ecolet — AWB");
if (file_exists(__DIR__.'/ecolet_functions.php')) ok("ecolet_functions.php gasit");
else bad("ecolet_functions.php LIPSESTE!");
$eUrl = defined('ECOLET_BASE_URL') ? rtrim(ECOLET_BASE_URL,'/').'/shipments' : 'https://app.ecolet.ro/api/v1/shipments';
$ch = curl_init($eUrl);
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_NOBODY=>true,CURLOPT_TIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4]);
$t=microtime(true); curl_exec($ch); $el=round(microtime(true)-$t,2);
$cErr=curl_error($ch); $cHttp=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
if ($cErr) bad("Conexiune Ecolet ESUAT: $cErr");
else ok("Conexiune Ecolet OK — HTTP $cHttp in {$el}s | <code>$eUrl</code>");
inf("User: <strong>".trim(ECOLET_USERNAME)."</strong> | Sender: <strong>".ECOLET_SENDER_NAME."</strong> — ".ECOLET_SENDER_CITY.", ".ECOLET_SENDER_COUNTY." | locality_id: <strong>".ECOLET_SENDER_LOCALITY_ID."</strong>");
?>
<div style="margin-top:10px">
    <button class="btn oran" onclick="ajax('?action=test_ecolet_raw','ecolet-raw')">🔍 Test Token Ecolet RAW</button>
    <div id="ecolet-raw" class="res"></div>
</div>
<?php if ($last): ?>
<div style="margin-top:8px">
    <button class="btn blau" onclick="ajax('?action=test_ecolet&id=<?=$last['id']?>','ecolet-res')">📦 Genereaza AWB pt #<?=$last['id']?></button>
    <div id="ecolet-res" class="res"></div>
</div>
<?php endif; ?>

<?php
// ---- 5. NETOPIA ----
sec("5. Netopia — Card");
$nId  = defined('NETOPIA_MERCHANT_ID') ? NETOPIA_MERCHANT_ID : '';
$nKey = defined('NETOPIA_API_KEY') ? NETOPIA_API_KEY : '';
if (defined('NETOPIA_SANDBOX')&&NETOPIA_SANDBOX) wrn("Netopia SANDBOX — seteaza NETOPIA_SANDBOX=false pt productie");
else ok("Netopia PRODUCTIE");
if (empty($nId)||$nId==='your-merchant-id') bad("NETOPIA_MERCHANT_ID nesetat!");
else ok("NETOPIA_MERCHANT_ID: $nId");
if (empty($nKey)||$nKey==='your-api-key') bad("NETOPIA_API_KEY nesetat!");
else ok("NETOPIA_API_KEY: setat");

// ---- 6. FISIERE ----
sec("6. Fisiere Esentiale");
echo "<div class='grid'>";
$fs=['config.php','order-api.php','oblio_functions.php','ecolet_functions.php','netopia-payment.php','payment-callbacks.php','admin.php','login.php','.env','.htaccess'];
foreach ($fs as $f) {
    if (file_exists(__DIR__."/$f")) ok("<code>$f</code>");
    else bad("<code>$f</code> LIPSESTE!");
}
foreach (['cache'=>'Cache tokens','logs'=>'Loguri','PHPMailer-master/src'=>'PHPMailer'] as $d=>$n) {
    $p=__DIR__."/$d";
    if (is_dir($p)) ok("<code>$d/</code> (".($n).")");
    else bad("<code>$d/</code> LIPSESTE! ($n)");
}
echo "</div>";

// ---- 7. LOG-URI ----
sec("7. Log-uri Recente");
foreach (['logs/orders.log'=>'Orders Log','error_log'=>'PHP Errors'] as $lf=>$ln) {
    $lp=__DIR__."/$lf";
    if (file_exists($lp)) {
        $c=htmlspecialchars(implode('',array_slice(file($lp),-30)));
        $c=preg_replace('/\[ERROR\][^\n]*/','<span style="color:#ef4444">$0</span>',$c);
        $c=preg_replace('/\[INFO\][^\n]*/','<span style="color:#22c55e">$0</span>',$c);
        $c=preg_replace('/(Fatal error|CRITICAL|PHP Fatal)[^\n]*/','<span style="color:#f59e0b;font-weight:bold">$0</span>',$c);
        echo "<p style='color:#94a3b8;margin:8px 0 3px'>📄 $ln:</p><pre>$c</pre>";
    } else { wrn("$lf nu exista"); }
}

// ---- 8. FLUX ----
sec("8. Test Flux Complet");
inf("Simuleaza o comanda reala prin order-api.php. Verifica log-urile dupa 15 secunde.");
?>
<button class="btn roz" onclick="testFull()">🚀 Simuleaza Comanda (cash + gls)</button>
<div id="full-res" class="res"></div>

<div style="background:#1e293b;border-radius:8px;padding:14px;margin-top:24px;border-left:4px solid #ef4444">
    <strong style="color:#ef4444">⚠️ STERGE debug_monstru.php DUPA CE TERMINI!</strong>
</div>

<script>
function ajax(url, divId) {
    var d = document.getElementById(divId);
    d.style.display = 'block';
    d.textContent = '⏳ Se executa...';
    fetch(url)
        .then(r => r.json())
        .then(j => { d.textContent = (j.success ? '✅ ' : '❌ ') + (j.message || JSON.stringify(j, null, 2)); })
        .catch(e => { d.textContent = '❌ EROARE FETCH: ' + e + '\n(PHP a returnat HTML - verifica ca ajax-ul e primul in fisier)'; });
}
function testFull() {
    var d = document.getElementById('full-res');
    d.style.display = 'block'; d.textContent = '⏳ Se trimite comanda...';
    fetch('order-api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            fullName:'Test Debug', phone:'0700000000',
            email:'<?= addslashes(ADMIN_EMAIL) ?>', bundle:'1', price:'59',
            paymentMethod:'cash', shippingMethod:'gls', lockerId:'',
            address:{line:'Strada Test 1', county:'Ilfov', city:'Bragadiru', postal_code:'077025'}
        })
    })
    .then(r => r.json())
    .then(j => { d.textContent = (j.success?'✅ ':'❌ ') + JSON.stringify(j,null,2) + '\n\n⏳ Verifica log-urile dupa 15 sec!'; })
    .catch(e => { d.textContent = '❌ ' + e; });
}
</script>
</body>
</html>