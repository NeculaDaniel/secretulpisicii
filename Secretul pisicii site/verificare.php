<?php
// Include fisierul cu configurari
require_once 'oblio_functions.php';

echo "<h1>🔍 Verificare Exactă a Datelor</h1>";

echo "<h3>1. Verificare Email:</h3>";
// Folosim paranteze drepte [] ca sa vedem daca exista spatii goale ascunse
echo "Ce vede PHP: <code>[" . OBLIO_EMAIL . "]</code><br>";
if (strpos(OBLIO_EMAIL, ' ') !== false) {
    echo "<b style='color:red'>⚠️ ATENȚIE: Ai un spațiu gol în Email! Șterge-l.</b>";
} else {
    echo "<b style='color:green'>✅ Format Email OK (fără spații).</b>";
}

echo "<h3>2. Verificare Secret:</h3>";
echo "Ce vede PHP: <code>[" . OBLIO_API_SECRET . "]</code><br>";
if (strpos(OBLIO_API_SECRET, ' ') !== false) {
    echo "<b style='color:red'>⚠️ ATENȚIE: Ai un spațiu gol în API Secret! Șterge-l.</b>";
} else {
    echo "<b style='color:green'>✅ Format Secret OK (fără spații).</b>";
}

echo "<h3>3. Verificare Lungime Secret:</h3>";
echo "Lungime cheie: " . strlen(OBLIO_API_SECRET) . " caractere.<br>";

echo "<h3>4. Header-ul Final (Authorization):</h3>";
echo "<code>Bearer " . OBLIO_EMAIL . ":" . OBLIO_API_SECRET . "</code>";
?>