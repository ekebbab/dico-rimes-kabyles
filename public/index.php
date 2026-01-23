<?php
// public/index.php
require_once __DIR__ . '/../src/RhymeEngine.php';

$engine = new RhymeEngine();

$params = [
    'q'     => $_GET['q'] ?? '',
    'sort'  => $_GET['sort'] ?? 'created_at', 
    'order' => $_GET['order'] ?? 'desc',
    'limit' => $_GET['limit'] ?? '20'
];

// On définit explicitement la variable attendue par home.php
$searchQuery = $params['q']; 

$results = $engine->searchAdvanced($params);

include __DIR__ . '/../src/views/home.php';