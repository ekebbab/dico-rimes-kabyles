<?php
require_once __DIR__ . '/../src/RhymeEngine.php';

$engine = new RhymeEngine();

// Paramètres étendus pour les 3 colonnes
$params = [
    'q'            => $_GET['q'] ?? '',
    'type'         => $_GET['type'] ?? 'all',
    'famille'      => $_GET['famille'] ?? '',
    'sous_famille' => $_GET['sous_famille'] ?? '',
    'sort'         => $_GET['sort'] ?? 'mot', // Changé par défaut en 'mot' pour le dictionnaire
    'order'        => $_GET['order'] ?? 'asc',
    'limit'        => $_GET['limit'] ?? '50'
];

$searchQuery = htmlspecialchars($params['q']); 
$results = $engine->searchAdvanced($params);

include __DIR__ . '/../src/views/home.php';