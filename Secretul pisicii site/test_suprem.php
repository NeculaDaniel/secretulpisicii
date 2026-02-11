<?php
// === DATELE TALE DIRECT AICI (FARA OBLIO_FUNCTIONS) ===

$email = 'david.altafini@gmail.com'; 

// 2. Scrie Secretul exact intre ghilimele (copiaza-l din nou din Oblio!)
$secret = '9b8deadd81e5fa6017575ec822820740a44306c1'; // <--- PUNE TOT CODUL AICI IN LOC DE PUNCTE

// 3. Testam conexiunea simpla (Listare firme)
$url = 'https://www.oblio.eu/api/v1/nomenclatures/companies';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Dezactivam SSL temporar

// Construim header-ul manual
$auth = 'Bearer ' . $email . ':' . $secret;

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . $auth
]);

echo "<h1>Test Conexiune Directă</h1>";
echo "Incercam sa ne conectam cu:<br>";
echo "Email: <b>$email</b><br>";
echo "Secret (primele 10 caractere): <b>" . substr($secret, 0, 10) . "...</b><br><hr>";

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode == 200) {
    echo "<h2 style='color:green'>✅ VICTORIE! Conexiunea merge!</h2>";
    echo "Dacă vezi mesajul ăsta, înseamnă că datele sunt bune.<br>";
    echo "Răspuns Oblio (firmele tale): <pre>$result</pre>";
    echo "<br><b>Ce înseamnă asta?</b> Înseamnă că în fișierul vechi `oblio_functions.php` aveai un spațiu gol ascuns sau o greșeală mică de scriere.";
} else {
    echo "<h2 style='color:red'>❌ EROARE $httpCode</h2>";
    echo "Tot nu merge. Mesaj Oblio: $result<br>";
    
    if ($httpCode == 401) {
        echo "<br><b>DIAGNOSTIC:</b><br>";
        echo "1. Ori emailul <b>$email</b> nu este cel principal al contului (cu care te loghezi).<br>";
        echo "2. Ori API Secret a fost regenerat și cel vechi nu mai merge.<br>";
        echo "3. Ori ai un spațiu gol copiat la finalul emailului sau secretului.";
    }
}
?>