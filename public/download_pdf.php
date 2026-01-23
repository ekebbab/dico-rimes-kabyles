<?php
// public/download_pdf.php
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/PdfGenerator.php';

$q = $_GET['q'] ?? '';

if (!empty($q)) {
    $engine = new RhymeEngine();
    $results = $engine->searchByRhyme($q);

    $pdf = new PdfGenerator();
    $pdf->generate($q, $results);
}