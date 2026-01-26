<?php
/**
 * EXPORTATION PREMIUM DU CATALOGUE - DICO KABYLE
 * Moteur : Mpdf (via Composer)
 * Gère parfaitement l'UTF-8 et le design élégant.
 */

// 1. CHARGEMENT DES COMPOSANTS
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

// 2. SÉCURITÉ
$engine = new RhymeEngine();
if (!Auth::isLogged($engine->getPDO())) {
    header('Location: login.php');
    exit;
}

// 3. CONFIGURATION DES COULEURS (La fameuse "Palette" à modifier ici)
$colorMain   = '#e67e22'; // Orange : Titres et Mots
$colorDark   = '#2c3e50'; // Bleu Nuit : Bandeaux et Texte
$colorMuted  = '#7f8c8d'; // Gris : Pied de page et Stats
$colorBgRime = '#fdf2e9'; // Fond léger pour les rimes

// 4. RÉCUPÉRATION DES DONNÉES
$db = $engine->getPDO();
$rimes = $db->query("SELECT * FROM rimes ORDER BY famille ASC, mot ASC")->fetchAll(PDO::FETCH_ASSOC);
$total = count($rimes);

// 5. INITIALISATION DE MPDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 25,
    'default_font' => 'dejavusans' // Supporte les caractères kabyles
]);

// --- FILIGRANE (Watermark) ---
$mpdf->SetWatermarkText('Dictionnaire Kabyle des Rimes', 0.05); // Texte et transparence (0.05 = très discret)
$mpdf->showWatermarkText = true;

// 6. DÉFINITION DU STYLE (CSS)
// On injecte les variables PHP (ex: {$colorMain}) directement dans le texte CSS
$stylesheet = "
    body { font-family: 'dejavusans', sans-serif; color: {$colorDark}; }
    
    /* Page de garde */
    .cover { text-align: center; margin-top: 80mm; }
    .cover h1 { font-size: 40pt; color: {$colorMain}; margin-bottom: 5mm; }
    .cover p { font-size: 14pt; color: {$colorMuted}; }
    
    /* Titres de sections (Lettres) */
    .letter-title { 
        background-color: {$colorDark}; color: white; 
        padding: 10px 15px; margin-top: 20px; 
        border-radius: 5px; font-size: 18pt; 
    }
    
    /* Tableau des rimes */
    table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
    td { padding: 12px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
    
    .mot-cell { font-weight: bold; color: {$colorMain}; font-size: 12pt; width: 30%; }
    .rime-tag { 
        background: {$colorBgRime}; color: #d35400; 
        padding: 3px 8px; border-radius: 4px; font-size: 10pt; font-weight: bold;
    }
    .signif-cell { font-size: 10pt; line-height: 1.5; width: 55%; }
";

// 7. CONSTRUCTION DE LA PAGE DE GARDE
$coverHtml = "
<div class='cover'>
    <h1>Dico Kabyle</h1>
    <p>Catalogue Complet des Rimes</p>
    <div style='margin-top: 20px; font-weight: bold;'>$total rimes répertoriées</div>
    <p style='font-size: 10pt;'>Généré le " . date('d/m/Y H:i') . "</p>
</div>";

// 8. CONSTRUCTION DU CORPS DU CATALOGUE
$contentHtml = "";
$currentLetter = '';

foreach ($rimes as $r) {
    // Si on change de lettre (ex: de A à B)
    if ($currentLetter !== $r['famille']) {
        $currentLetter = $r['famille'];
        $contentHtml .= "<div class='letter-title'>Lettre " . $currentLetter . "</div>";
        // Ajout d'un signet (bookmark) pour la navigation dans le PDF
        $contentHtml .= "<bookmark content='Lettre $currentLetter' level='0' />";
    }

    $contentHtml .= "
    <table>
        <tr>
            <td class='mot-cell'>" . htmlspecialchars($r['mot']) . "</td>
            <td style='width: 15%;'><span class='rime-tag'>" . htmlspecialchars($r['rime']) . "</span></td>
            <td class='signif-cell'>" . nl2br(htmlspecialchars($r['signification'])) . "</td>
        </tr>
    </table>";
}

// 9. ASSEMBLAGE ET GÉNÉRATION
$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

// Écriture de la page de garde
$mpdf->WriteHTML($coverHtml, \Mpdf\HTMLParserMode::HTML_BODY);

// Saut de page avant le catalogue
$mpdf->AddPage();

// Configuration du pied de page pour le catalogue
$mpdf->SetHTMLFooter("
    <table width='100%' style='border-top: 0.1mm solid {$colorMuted}; font-size: 9pt; color: {$colorMuted};'>
        <tr>
            <td width='33%'>Dico Kabyle des Rimes</td>
            <td width='33%' align='center'>Page {PAGENO} sur {nbpg}</td>
            <td width='33%' style='text-align: right;'>Catalogue Officiel</td>
        </tr>
    </table>");

// Écriture du contenu
$mpdf->WriteHTML($contentHtml, \Mpdf\HTMLParserMode::HTML_BODY);

// Nettoyage du tampon et Sortie
ob_end_clean();
$mpdf->Output('Catalogue_Rimes_Kabyles.pdf', 'I'); // 'I' pour l'afficher dans le navigateur