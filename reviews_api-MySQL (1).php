<?php
/**
 * ==========================================
 *   REVIEWS API - Secretul Pisicii
 *   MySQL Storage + IP & LocalStorage Validation
 *   Versiune 2.0 - Optimizată
 * ==========================================
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configurare MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'alvoro_r1_admin');
define('DB_USER', 'alvoro_r1_user');
define('DB_PASS', 'Parola2020@');

/**
 * Conexiune MySQL
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Reviews MySQL Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Eroare bază de date']);
            exit;
        }
    }
    
    return $pdo;
}

// ==========================================
//   GET: Returnează toate reviews-urile publicate
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = getDbConnection();
    
    try {
        // Selectează doar reviews publicate, cele mai noi primele
        $stmt = $pdo->prepare("
            SELECT id, name, stars, text, created_at 
            FROM reviews 
            WHERE status = 'published' 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute();
        $reviews = $stmt->fetchAll();
        
        // Formatare date pentru frontend
        foreach ($reviews as &$review) {
            $review['date'] = $review['created_at'];
            unset($review['created_at']);
        }
        
        echo json_encode($reviews, JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        error_log("Reviews SELECT Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Eroare la încărcarea recenziilor']);
    }
    
    exit;
}

// ==========================================
//   POST: Adaugă review nou
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validare câmpuri
    if (!isset($input['name']) || !isset($input['stars']) || !isset($input['text'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date incomplete']);
        exit;
    }
    
    // Identificare utilizator prin IP
    $userIP = $_SERVER['REMOTE_ADDR'];
    
    // VERIFICARE: A mai dat acest IP review?
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE ip_address = ?");
        $stmt->execute([$userIP]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Ai trimis deja o recenzie! Se acceptă doar una per persoană.']);
            exit;
        }
        
        // Sanitizare date
        $name = htmlspecialchars(strip_tags(trim($input['name'])), ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars(strip_tags(trim($input['text'])), ENT_QUOTES, 'UTF-8');
        $stars = intval($input['stars']);
        
        // Validare stars
        if ($stars < 1) $stars = 1;
        if ($stars > 5) $stars = 5;
        
        // Validare lungime
        if (strlen($name) < 2 || strlen($name) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Numele trebuie să aibă între 2-100 caractere']);
            exit;
        }
        
        if (strlen($text) < 10 || strlen($text) > 500) {
            http_response_code(400);
            echo json_encode(['error' => 'Textul trebuie să aibă între 10-500 caractere']);
            exit;
        }
        
        // Inserare în MySQL - PUBLICAT INSTANT (fără aprobare)
        $stmt = $pdo->prepare("
            INSERT INTO reviews (name, stars, text, ip_address, status) 
            VALUES (?, ?, ?, ?, 'published')
        ");
        
        $stmt->execute([$name, $stars, $text, $userIP]);
        $newReviewId = $pdo->lastInsertId();
        
        // Returnează review-ul adăugat
        $stmt = $pdo->prepare("SELECT id, name, stars, text, created_at FROM reviews WHERE id = ?");
        $stmt->execute([$newReviewId]);
        $newReview = $stmt->fetch();
        
        $newReview['date'] = $newReview['created_at'];
        unset($newReview['created_at']);
        
        echo json_encode([
            'success' => true, 
            'review' => $newReview,
            'message' => 'Recenzie adăugată cu succes!'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        error_log("Reviews INSERT Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Eroare la salvarea recenziei']);
    }
    
    exit;
}

// Metodă invalidă
http_response_code(405);
echo json_encode(['error' => 'Metodă HTTP invalidă']);