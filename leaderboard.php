<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dataFile = 'leaderboard_data.json';

// Чтение данных
function readData() {
    global $dataFile;
    if (!file_exists($dataFile)) {
        return ['players' => []];
    }
    $content = file_get_contents($dataFile);
    return json_decode($content, true) ?: ['players' => []];
}

// Запись данных
function writeData($data) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

// GET запрос - получение таблицы
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = readData();
    usort($data['players'], function($a, $b) {
        return ($b['score'] ?? 0) - ($a['score'] ?? 0);
    });
    $data['players'] = array_slice($data['players'], 0, 100);
    echo json_encode($data);
    exit;
}

// POST запрос - обновление игрока
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['username'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $data = readData();
    $username = $input['username'];
    $score = $input['score'] ?? 0;
    $coins = $input['coins'] ?? 0;

    $found = false;
    foreach ($data['players'] as &$player) {
        if ($player['username'] === $username) {
            if ($score > $player['score']) {
                $player['score'] = $score;
                $player['coins'] = $coins;
                $player['lastUpdate'] = time();
            }
            $found = true;
            break;
        }
    }

    if (!$found) {
        $data['players'][] = [
            'username' => $username,
            'score' => $score,
            'coins' => $coins,
            'lastUpdate' => time()
        ];
    }

    usort($data['players'], function($a, $b) {
        return ($b['score'] ?? 0) - ($a['score'] ?? 0);
    });
    $data['players'] = array_slice($data['players'], 0, 100);
    
    writeData($data);
    echo json_encode(['success' => true]);
    exit;
}