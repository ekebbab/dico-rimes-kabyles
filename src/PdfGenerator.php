<?php
// src/PdfGenerator.php
require_once __DIR__ . '/../vendor/autoload.php';

class PdfGenerator {
    public function generate($query, $results) {
        // Création d'une nouvelle instance TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Informations du document
        $pdf->SetCreator('Dico Kabyle');
        $pdf->SetTitle('Rimes en ' . $query);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        // Ajout d'une page
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        // Contenu HTML
        $html = "<h1>Liste des rimes pour : " . htmlspecialchars($query) . "</h1>";
        $html .= '<table border="1" cellpadding="5">
                    <thead>
                        <tr style="background-color: #f2f2f2;">
                            <th><b>Mot</b></th>
                            <th><b>Signification</b></th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($results as $row) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($row['mot']) . '</td>
                        <td>' . htmlspecialchars($row['signification'] ?? 'N/A') . '</td>
                      </tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Sortie du PDF (I = Envoi au navigateur, D = Téléchargement forcé)
        $pdf->Output('rimes-' . $query . '.pdf', 'D');
    }
}