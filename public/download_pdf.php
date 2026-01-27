<?php
/**
 * TÉLÉCHARGEMENT PDF DES RÉSULTATS DE RECHERCHE
 * Génère un document basé sur les filtres actifs de l'utilisateur.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

// 1. RÉCUPÉRATION DES PARAMÈTRES DE RECHERCHE
$params = [
    'q'            => $_GET['q'] ?? '',
    'type'         => $_GET['type'] ?? 'all',
    'lettre'       => $_GET['lettre'] ?? '',
    'classe'       => $_GET['classe'] ?? '',
    'genre'        => $_GET['genre'] ?? '',
    'nombre'       => $_GET['nombre'] ?? '',
    'sort'         => $_GET['sort'] ?? 'mot',
    'order'        => $_GET['order'] ?? 'ASC',
    'limit'        => 'all' // On télécharge tous les résultats correspondants
];

// 2. EXÉCUTION DU MOTEUR
$engine = new RhymeEngine();
$results = $engine->searchAdvanced($params);

if (empty($results)) {
    die("Aucun résultat à exporter pour cette recherche.");
}

// 3. CONFIGURATION MPDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 20,
    'default_font' => 'dejavusans'
]);

// 4. DESIGN DU DOCUMENT (CSS)
$stylesheet = "
    body { font-family: 'dejavusans', sans-serif; color: #2c3e50; }
    .header { text-align: center; border-bottom: 2px solid #e67e22; padding-bottom: 10px; margin-bottom: 20px; }
    .search-info { font-size: 10pt; color: #7f8c8d; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 8px; border-bottom: 0.1mm solid #eee; vertical-align: top; }
    .mot { font-weight: bold; color: #e67e22; font-size: 11pt; }
    .grammaire { font-size: 7pt; color: #636e72; text-transform: uppercase; margin-top: 3px; }
    .rime-badge { background: #fdf2e9; color: #d35400; padding: 3px 7px; border-radius: 4px; font-weight: bold; font-size: 9pt; }
    .signification { font-size: 9pt; }
    .exemple { font-size: 8pt; color: #7f8c8d; font-style: italic; margin-top: 4px; border-left: 0.5mm solid #e67e22; padding-left: 5px; }
";

// 5. CONSTRUCTION DU CONTENU
$html = "
<div class='header'>
    <h1>Résultats de recherche</h1>
    <div class='search-info'>
        Dictionnaire Kabyle des Rimes - " . date('d/m/Y H:i') . "
    </div>
</div>";

if (!empty($params['q'])) {
    $html .= "<p style='font-size: 10pt;'>Recherche textuelle : <strong>" . htmlspecialchars($params['q']) . "</strong></p>";
}

$html .= "<table>";
foreach ($results as $row) {
    // Label Grammatical
    $label = htmlspecialchars($row['classe_grammaticale']);
    if (!empty($row['genre']) && $row['genre'] !== 'N/A') $label .= " | " . htmlspecialchars($row['genre']);
    if (!empty($row['nombre']) && $row['nombre'] !== 'N/A') $label .= " | " . htmlspecialchars($row['nombre']);

    $html .= "
    <tr>
        <td style='width: 30%;'>
            <div class='mot'>" . htmlspecialchars($row['mot']) . "</div>
            <div class='grammaire'>$label</div>
        </td>
        <td style='width: 15%; text-align: center;'>
            <span class='rime-badge'>" . htmlspecialchars($row['rime']) . "</span>
        </td>
        <td style='width: 55%;'>
            <div class='signification'>" . nl2br(htmlspecialchars($row['signification'])) . "</div>" . 
            (!empty($row['exemple']) ? "<div class='exemple'>" . htmlspecialchars($row['exemple']) . "</div>" : "") . "
        </td>
    </tr>";
}
$html .= "</table>";

// 6. GÉNÉRATION
$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->SetHTMLFooter("<div style='text-align:center; font-size: 8pt;'>Page {PAGENO} sur {nbpg}</div>");
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

// Nettoyage et sortie
ob_end_clean();
$filename = "Recherche_Rimes_" . date('Ymd_Hi') . ".pdf";
$mpdf->Output($filename, 'D'); // 'D' force le téléchargement direct