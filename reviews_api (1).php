<?php
// reviews_api.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

$file = 'reviews.json';

// --- 1. GET: Returnează review-urile (fără IP-uri, pentru confidențialitate) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) $data = [];
        
        // Curățăm datele trimise către frontend (scoatem IP-ul)
        $publicData = array_map(function($review) {
            unset($review['ip']); // Nu trimitem IP-ul în browser
            return $review;
        }, $data);

        echo json_encode($publicData);
    } else {
        echo json_encode([]);
    }
    exit;
}

// --- 2. POST: Adaugă un review nou (cu verificare IP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validare câmpuri
    if (!isset($input['name']) || !isset($input['stars']) || !isset($input['text'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date incomplete']);
        exit;
    }

    // Identificare Utilizator prin IP
    $userIP = $_SERVER['REMOTE_ADDR'];

    // Încărcăm review-urile existente
    $reviews = [];
    if (file_exists($file)) {
        $reviews = json_decode(file_get_contents($file), true);
        if (!is_array($reviews)) $reviews = [];
    }

    // --- VERIFICARE: A mai dat acest IP review? ---
    foreach ($reviews as $existingReview) {
        if (isset($existingReview['ip']) && $existingReview['ip'] === $userIP) {
            http_response_code(403); // Forbidden
            echo json_encode(['error' => 'Ai trimis deja o recenzie! Se acceptă doar una per persoană.']);
            exit;
        }
    }

    // Sanitizare (Securitate XSS)
    $name = htmlspecialchars(strip_tags(trim($input['name'])), ENT_QUOTES, 'UTF-8');
    $text = htmlspecialchars(strip_tags(trim($input['text'])), ENT_QUOTES, 'UTF-8');
    $stars = intval($input['stars']);
    if ($stars < 1) $stars = 1;
    if ($stars > 5) $stars = 5;

    // Creare Review Nou (Includem IP-ul pentru verificări viitoare)
    $newReview = [
        'name'  => $name,
        'stars' => $stars,
        'text'  => $text,
        'date'  => date('Y-m-d H:i:s'),
        'ip'    => $userIP // Salvăm IP-ul intern
    ];

    // Adăugăm la începutul listei
    array_unshift($reviews, $newReview);

    // Salvare în fișier
    if (file_put_contents($file, json_encode($reviews, JSON_PRETTY_PRINT))) {
        // Răspundem cu succes (fără a trimite IP-ul înapoi)
        unset($newReview['ip']);
        echo json_encode(['success' => true, 'review' => $newReview]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Eroare la salvare pe server']);
    }
    exit;
}
?>