<?php
/**
 * EXPORTATION LINGUISTIQUE DU CATALOGUE - DICO KABYLE
 * Supporte la nouvelle structure : Lettre Pivot, Classe, Genre, Nombre.
 * Moteur : Mpdf
 */

// 1. CHARGEMENT DES COMPOSANTS
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

$engine = new RhymeEngine();
$db = $engine->getPDO();

// 2. SÉCURITÉ
if (!Auth::isLogged($db)) {
    header('Location: login.php');
    exit;
}

// 3. CONFIGURATION GRAPHIQUE
$colorMain    = '#e67e22'; // Orange : Titres et Mots
$colorDark    = '#2c3e50'; // Bleu Nuit : Bandeaux et Texte
$colorMuted   = '#7f8c8d'; // Gris : Pied de page
$colorBgBadge = '#f1f2f6'; // Gris clair pour les badges grammaire
$colorAccent  = '#d35400'; // Orange foncé pour la rime

// 4. RÉCUPÉRATION DES DONNÉES (Triées par Lettre Pivot)
$rimes = $db->query("SELECT * FROM rimes ORDER BY lettre ASC, mot ASC")->fetchAll(PDO::FETCH_ASSOC);
$total = count($rimes);

// 5. INITIALISATION DE MPDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 25,
    'default_font' => 'dejavusans' 
]);

$mpdf->SetWatermarkText('Dictionnaire Kabyle', 0.04);
$mpdf->showWatermarkText = true;

// 6. STYLE CSS (Adapté aux badges linguistiques)
$stylesheet = "
    body { font-family: 'dejavusans', sans-serif; color: {$colorDark}; font-size: 10pt; }
    
    .cover { text-align: center; margin-top: 80mm; }
    .cover h1 { font-size: 35pt; color: {$colorMain}; margin-bottom: 2mm; }
    .cover p { font-size: 14pt; color: {$colorMuted}; }
    
    .letter-title { 
        background-color: {$colorDark}; color: white; 
        padding: 8px 15px; margin-top: 30px; margin-bottom: 10px;
        border-radius: 4px; font-size: 16pt; font-weight: bold;
    }
    
    table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
    td { padding: 10px; border-bottom: 0.1mm solid #eee; vertical-align: top; }
    
    .mot-box { width: 30%; }
    .mot-text { font-weight: bold; color: {$colorMain}; font-size: 11pt; }
    .variante { font-size: 8pt; color: {$colorMuted}; font-weight: normal; font-style: italic; }
    
    .grammaire-info { font-size: 7.5pt; color: #57606f; margin-top: 4px; text-transform: uppercase; }
    
    .rime-box { width: 15%; text-align: center; }
    .rime-tag { 
        background: #fdf2e9; color: {$colorAccent}; 
        padding: 4px 10px; border-radius: 15px; font-size: 9pt; font-weight: bold;
        border: 0.1mm solid #fadbd8;
    }
    
    .signif-box { width: 55%; font-size: 9.5pt; color: #2f3542; }
    .exemple-text { font-size: 8.5pt; color: {$colorMuted}; font-style: italic; margin-top: 5px; border-left: 0.5mm solid {$colorMain}; padding-left: 8px; }
";

// 7. PAGE DE GARDE
$coverHtml = "
<div class='cover'>
    <h1 style='letter-spacing: 2px;'>AMAWAL</h1>
    <p>Dictionnaire des Rimes Kabyles</p>
    <div style='margin-top: 30px; font-size: 12pt;'>Total des entrées : <strong>$total</strong></div>
    <div style='margin-top: 10px; font-size: 9pt; color: {$colorMuted};'>Généré le " . date('d/m/Y') . "</div>
</div>";

// 8. CORPS DU DOCUMENT
$contentHtml = "";
$currentLetter = '';

foreach ($rimes as $r) {
    // Rupture de séquence par Lettre Pivot
    if ($currentLetter !== $r['lettre']) {
        $currentLetter = $r['lettre'];
        $contentHtml .= "<div class='letter-title'>Consonne $currentLetter</div>";
        $contentHtml .= "<bookmark content='Lettre $currentLetter' level='0' />";
    }

    // Préparation des infos grammaticales
    $grammaire = htmlspecialchars($r['classe_grammaticale']);
    if (!empty($r['genre']) && $r['genre'] !== 'N/A') $grammaire .= " | " . htmlspecialchars($r['genre']);
    if (!empty($r['nombre']) && $r['nombre'] !== 'N/A') $grammaire .= " | " . htmlspecialchars($r['nombre']);

    $contentHtml .= "
    <table>
        <tr>
            <td class='mot-box'>
                <span class='mot-text'>" . htmlspecialchars($r['mot']) . "</span>" . 
                ($r['variante'] > 1 ? " <span class='variante'>(v{$r['variante']})</span>" : "") . "
                <div class='grammaire-info'>$grammaire</div>
            </td>
            <td class='rime-box'>
                <span class='rime-tag'>" . htmlspecialchars($r['rime']) . "</span>
            </td>
            <td class='signif-box'>
                <div>" . nl2br(htmlspecialchars($r['signification'])) . "</div>" . 
                (!empty($r['exemple']) ? "<div class='exemple-text'>" . htmlspecialchars($r['exemple']) . "</div>" : "") . "
            </td>
        </tr>
    </table>";
}

// 9. GÉNÉRATION FINALE
$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($coverHtml, \Mpdf\HTMLParserMode::HTML_BODY);

$mpdf->AddPage();

// Pied de page
$mpdf->SetHTMLFooter("
    <table width='100%' style='border-top: 0.1mm solid {$colorMuted}; font-size: 8pt; color: {$colorMuted}; padding-top: 5px;'>
        <tr>
            <td width='33%'>Dico Kabyle des Rimes</td>
            <td width='33%' align='center'>Page {PAGENO} sur {nbpg}</td>
            <td width='33%' style='text-align: right;'>© " . date('Y') . " - Catalogue Linguistique</td>
        </tr>
    </table>");

$mpdf->WriteHTML($contentHtml, \Mpdf\HTMLParserMode::HTML_BODY);

ob_end_clean();
$mpdf->Output('Dico_Kabyle_Catalogue.pdf', 'I');