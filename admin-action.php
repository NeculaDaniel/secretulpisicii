<?php
session_start();
require_once __DIR__ . '/config.php';

// Verificare Securitate (Simplă - verifică dacă e logat adminul)
// Dacă în admin.php folosești $_SESSION['admin_logged_in'], pune la fel aici.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';
$orderId = $_GET['id'] ?? 0;

if (!$orderId) {
    die(json_encode(['success' => false, 'message' => 'ID Comandă lipsă.']));
}

try {
    $msg = "";
    
    if ($action === 'invoice') {
        require_once __DIR__ . '/oblio_functions.php';
        $msg = sendToOblio($orderId);
    } 
    elseif ($action === 'awb') {
        require_once __DIR__ . '/ecolet_functions.php';
        $msg = generateEcoletAWB($orderId);
    } 
    else {
        throw new Exception("Acțiune invalidă.");
    }

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>