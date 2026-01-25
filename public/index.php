<?php
/**
 * CONTRÔLEUR PAGE D'ACCUEIL
 * Gère l'affichage public du dictionnaire.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';

$engine = new RhymeEngine();

// Récupération et nettoyage des paramètres de recherche
$params = [
    'q'     => $_GET['q'] ?? '',
    'sort'  => $_GET['sort'] ?? 'created_at', 
    'order' => $_GET['order'] ?? 'desc',
    'limit' => $_GET['limit'] ?? '20'
];

/**
 * Note : searchQuery est utilisée dans home.php pour pré-remplir 
 * le champ de recherche dans le header/hero.
 */
$searchQuery = htmlspecialchars($params['q']); 

// Exécution de la recherche avancée via le moteur SQLite
$results = $engine->searchAdvanced($params);

// Inclusion de la vue finale (S'assurer que src/views/home.php existe)
include __DIR__ . '/../src/views/home.php';