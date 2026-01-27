<?php
/**
 * CONFIGURATION LINGUISTIQUE DES TERMINAISONS (RIMES)
 * Centralise les lettres pivots et génère les combinaisons.
 */

// 1. Liste officielle des consonnes (Lettres pivots)
// Ajout de Ṛ et W - Suppression de Ř
$consonnes_kabyles = [
    'B', 'C', 'Č', 'D', 'Ḍ', 'F', 'G', 'Ǧ', 'H', 'Ḥ', 'J', 'K', 'L', 'M', 
    'N', 'Q', 'R', 'Ṛ', 'S', 'Ṣ', 'T', 'Ṭ', 'W', 'X', 'Y', 'Z', 'Ẓ', 'Ž', 'Ɛ', 'Ɣ'
];

$voyelles = ['A', 'I', 'U', 'E'];

$familles = [];

foreach ($consonnes_kabyles as $pivot) {
    $rimes = [];

    // A. Combinaisons simples : PIVOT + VOYELLE (BA, BI, BU...)
    foreach (['A', 'I', 'U'] as $v) {
        $rimes[] = $pivot . $v;
    }

    // B. Combinaisons inverses : VOYELLE + PIVOT (AB, IB, UB, EB)
    foreach ($voyelles as $v) {
        $rimes[] = $v . $pivot;
    }

    // C. Redoublements : VOYELLE + PIVOT + PIVOT (ABB, EBB...)
    foreach ($voyelles as $v) {
        $rimes[] = $v . $pivot . $pivot;
    }

    // D. Combinaisons de consonnes : AUTRE CONSONNE + PIVOT (CB, DB, FB...)
    foreach ($consonnes_kabyles as $c) {
        if ($c !== $pivot) {
            $rimes[] = $c . $pivot;
        }
    }

    $familles[$pivot] = $rimes;
}

/**
 * Fonction utilitaire pour générer le JSON des rimes pour le JavaScript
 */
function getRimesJson($familles) {
    return json_encode($familles, JSON_UNESCAPED_UNICODE);
}