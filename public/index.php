<?php
// public/index.php

// Chargement des classes
require_once __DIR__ . '/../src/RhymeEngine.php';

$engine = new RhymeEngine();
$results = [];
$searchQuery = $_GET['q'] ?? '';

if (!empty($searchQuery)) {
    // Par défaut, on cherche par rime (terminaison)
    $results = $engine->searchByRhyme($searchQuery);
}

// On inclut la vue (le HTML)
include __DIR__ . '/../src/views/home.php';