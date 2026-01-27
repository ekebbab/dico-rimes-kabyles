<?php
require_once __DIR__ . '/../src/Auth.php';
Auth::init(); 

require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/terminaisons.php';

$engine = new RhymeEngine();

$params = [
    'q'      => $_GET['q'] ?? '',
    'type'   => $_GET['type'] ?? 'all',
    'lettre' => $_GET['lettre'] ?? '',
    'rime'   => $_GET['rime'] ?? '',
    'classe' => $_GET['classe'] ?? '',
    'sort'   => $_GET['sort'] ?? 'mot',
    'order'  => $_GET['order'] ?? 'asc',
    'limit'  => $_GET['limit'] ?? '50'
];

$results = [];
// Recherche déclenchée si q ou lettre est présent
if (!empty($params['q']) || !empty($params['lettre'])) {
    $results = $engine->searchAdvanced($params);
}

// On garde la variable vide si aucune recherche n'est faite
$searchQuery = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; 

include __DIR__ . '/../src/views/home.php';