<?php
// public/api.php
header('Content-Type: application/json');

require_once __DIR__ . '/../src/RhymeEngine.php';

$engine = new RhymeEngine();
$q = $_GET['q'] ?? '';

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$results = $engine->searchByRhyme($q);

// On ne renvoie que les données pures
echo json_encode($results);