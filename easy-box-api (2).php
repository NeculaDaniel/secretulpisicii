<?php
/**
 * EASY BOX LOCKERS API
 * Preluare liste lockers, filtrare pe județ
 * Version 1.0
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

class EasyBoxLockers {
    
    private $apiKey;
    private $apiUrl;
    private $cache_file;
    private $cache_time = 3600; // 1 oră
    
    public function __construct() {
        $this->apiKey = EASYBOX_API_KEY;
        $this->apiUrl = EASYBOX_API_URL;
        $this->cache_file = __DIR__ . '/cache/easybox-lockers.json';
    }
    
    /**
     * Obțin lista tuturor lockerelor
     */
    public function getAllLockers() {
        
        // Verific cache-ul
        if (file_exists($this->cache_file)) {
            $cache_age = time() - filemtime($this->cache_file);
            if ($cache_age < $this->cache_time) {
                return json_decode(file_get_contents($this->cache_file), true);
            }
        }
        
        // Fetch din API
        $lockers = $this->fetchFromAPI();
        
        // Cache-ez rezultatul
        @mkdir(dirname($this->cache_file), 0755, true);
        file_put_contents($this->cache_file, json_encode($lockers));
        
        return $lockers;
    }
    
    /**
     * Filtrare lockers după județ
     */
    public function getLockersByCounty($county) {
        
        $allLockers = $this->getAllLockers();
        
        if (!is_array($allLockers)) {
            return [];
        }
        
        return array_filter($allLockers, function($locker) use ($county) {
            return isset($locker['county']) && strtolower($locker['county']) === strtolower($county);
        });
    }
    
    /**
     * Fetch din API-ul Easy Box
     */
    private function fetchFromAPI() {
        
        if (!$this->apiKey) {
            return [];
        }
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/lockers',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => !EASYBOX_SANDBOX,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError || $httpCode !== 200) {
            logEvent(LOG_ERRORS, "Easy Box API Error: HTTP $httpCode - $curlError");
            
            // Return mock data pentru demo
            return $this->getMockLockers();
        }
        
        $data = json_decode($result, true);
        
        // Transform Easy Box response format
        return isset($data['lockers']) ? $data['lockers'] : [];
    }
    
    /**
     * Mock data pentru testing (demo)
     */
    private function getMockLockers() {
        return [
            [
                'id' => 'EZB001',
                'name' => 'Easy Box - Piața Amzei, București',
                'county' => 'Ilfov',
                'city' => 'București',
                'address' => 'Piața Amzei 1',
                'latitude' => 44.4479,
                'longitude' => 26.0974,
                'slots_available' => 12
            ],
            [
                'id' => 'EZB002',
                'name' => 'Easy Box - Obor, București',
                'county' => 'Ilfov',
                'city' => 'București',
                'address' => 'Bulevardul Obor 45',
                'latitude' => 44.4150,
                'longitude' => 26.1300,
                'slots_available' => 8
            ],
            [
                'id' => 'EZB003',
                'name' => 'Easy Box - Dorobanți, București',
                'county' => 'Ilfov',
                'city' => 'București',
                'address' => 'Strada Dorobanți 22',
                'latitude' => 44.4618,
                'longitude' => 26.0700,
                'slots_available' => 15
            ]
        ];
    }
}

// ============================================
// ENDPOINT: GET /easy-box-api.php?county=Ilfov
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $county = isset($_GET['county']) ? htmlspecialchars($_GET['county']) : '';
    
    $easybox = new EasyBoxLockers();
    
    if ($county) {
        $lockers = $easybox->getLockersByCounty($county);
    } else {
        $lockers = $easybox->getAllLockers();
    }
    
    if (empty($lockers)) {
        echo json_encode([
            'success' => false,
            'message' => 'Nu sunt lockers disponibili în acest județ',
            'lockers' => []
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'county' => $county,
            'count' => count($lockers),
            'lockers' => array_values($lockers)
        ]);
    }
    
    exit;
}

// ============================================
// ENDPOINT: POST - Create pickup point
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['lockerId']) || !isset($input['orderId'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Date incomplete']);
        exit;
    }
    
    // Connect la DB și salvez selecția
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        
        $stmt = $pdo->prepare("UPDATE orders SET shipping_method='easybox', easybox_locker_id=? WHERE id=?");
        $stmt->execute([$input['lockerId'], $input['orderId']]);
        
        logEvent(LOG_ORDERS, "Easy Box locker selectat: orderId={$input['orderId']}, lockerId={$input['lockerId']}");
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
