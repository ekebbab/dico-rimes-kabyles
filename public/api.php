<?php
// public/api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../src/RhymeEngine.php';

$q = $_GET['q'] ?? '';
$results = [];

if (!empty($q)) {
    $engine = new RhymeEngine();
    $results = $engine->searchByRhyme($q);
}

echo json_encode($results);