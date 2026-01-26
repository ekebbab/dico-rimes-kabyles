<?php
/**
 * PAGE ÉDITION - VERSION LINGUISTIQUE AVANCÉE
 * Supporte la nouvelle structure : Lettre, Classe, Genre, Nombre.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
$engine = new RhymeEngine();

if (!Auth::isLogged($engine->getPDO())) { 
    header('Location: login.php'); 
    exit; 
}

$id = (int)($_GET['id'] ?? 0);
$admin = new AdminEngine($engine->getPDO());
$message = "";

// Récupération des données avec jointure pour vérifier les droits d'auteur
$stmt = $engine->getPDO()->prepare("
    SELECT r.*, u.role as author_role 
    FROM rimes r 
    LEFT JOIN users u ON r.auteur_id = u.id 
    WHERE r.id = ?
");
$stmt->execute([$id]);
$word = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$word) { die("Erreur : Mot introuvable."); }

// Sécurité : Vérifier si l'utilisateur a le droit de modifier ce mot
if (!Auth::canManage($word['auteur_id'], $word['author_role'])) {
    header('Location: admin.php?msg=denied');
    exit;
}

// Configuration des lettres pivots et des terminaisons (Rimes)
$familles = [
    'B'=>['BA','BI','BU','AB','IB','UB','EB'], 'C'=>['CA','CI','CU','AC','IC','UC','EC'],
    'Č'=>['ČA','ČI','ČU','AČ','IČ','UČ','EČ'], 'D'=>['DA','DI','DU','AD','ID','UD','ED'],
    'Ḍ'=>['ḌA','ḌI','ḌU','AḌ','IḌ','UḌ','EḌ'], 'F'=>['FA','FI','FU','AF','IF','UF','EF'],
    'G'=>['GA','GI','GU','AG','IG','UG','EG'], 'Ǧ'=>['ǦA','ǦI','ǦU','AǦ','IǦ','UǦ','EǦ'],
    'H'=>['HA','HI','HU','AH','IH','UH','EH'], 'Ḥ'=>['ḤA','ḤI','ḤU','AḤ','IḤ','UḤ','EḤ'],
    'J'=>['JA','JI','JU','AJ','IJ','UJ','EJ'], 'K'=>['KA','KI','KU','AK','IK','UK','EK'],
    'L'=>['LA','LI','LU','AL','IL','UL','EL'], 'M'=>['MA','MI','MU','AM','IM','UM','EM'],
    'N'=>['NA','NI','NU','AN','IN','UN','EN'], 'Q'=>['QA','QI','QU','AQ','IQ','UQ','EQ'],
    'R'=>['RA','RI','RU','AR','IR','UR','ER'], 'Ř'=>['ŘA','ŘI','ŘU','AŘ','IŘ','UŘ','EŘ'],
    'S'=>['SA','SI','SU','AS','IS','US','ES'], 'Ṣ'=>['ṢA','ṢI','ṢU','AṢ','IṢ','UṢ','EṢ'],
    'T'=>['TA','TI','TU','AT','IT','UT','ET'], 'Ṭ'=>['ṬA','ṬI','ṬU','AṬ','IṬ','UṬ','EṬ'],
    'X'=>['XA','XI','XU','AX','IX','UX','EX'], 'Y'=>['YA','YI','YU','AY','IY','UY','EY'],
    'Z'=>['ZA','ZI','ZU','AZ','IZ','UZ','EZ'], 'Ẓ'=>['ẒA','ẒI','ẒU','AẒ','IẒ','UẒ','EẒ'],
    'Ž'=>['ŽA','ŽI','ŽU','AŽ','IŽ','UŽ','EŽ'], 'Ɛ'=>['ƐA','ƐI','ƐU','AƐ','IƐ','UƐ','EƐ'],
    'Ɣ'=>['ƔA','ƔI','ƔU','AƔ','IƔ','UƔ','EƔ']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extraction des données POST
    $data = [
        'mot'                 => trim($_POST['mot'] ?? ''),
        'lettre'              => trim($_POST['lettre'] ?? ''),
        'rime'                => trim($_POST['rime'] ?? ''),
        'signification'       => trim($_POST['signification'] ?? ''),
        'exemple'             => trim($_POST['exemple'] ?? ''),
        'classe_grammaticale' => trim($_POST['classe_grammaticale'] ?? ''),
        'genre'               => trim($_POST['genre'] ?? ''),
        'nombre'              => trim($_POST['nombre'] ?? '')
    ];

    if (empty($data['mot']) || empty($data['lettre']) || empty($data['rime']) || empty($data['signification'])) {
        $message = "<p class='error-msg'>❌ Veuillez remplir tous les champs obligatoires.</p>";
    } else {
        if ($admin->updateWord($id, $data)) {
            $message = "<p class='success-msg'>✅ Modifications enregistrées avec succès !</p>";
            // Rafraîchir les données locales
            $stmt->execute([$id]); 
            $word = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "<p class='error-msg'>❌ Erreur lors de la mise à jour.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier : <?= htmlspecialchars($word['mot']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
        .modal-card { background: white; width: 90%; max-width: 400px; padding: 30px; border-radius: 16px; text-align: center; }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 25px; }
        .btn-confirm-save { background: var(--primary-color); color: white; border-radius: 8px; padding: 12px 25px; border:none; cursor:pointer; font-weight:bold; }
        .btn-cancel { background: #dfe6e9; color: #2d3436; border-radius: 8px; padding: 12px 25px; border:none; cursor:pointer; font-weight:bold; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>
    <div class="container">
        <header class="admin-header">
            <h1>Modifier la rime</h1>
            <a href="admin.php" class="btn-primary">← Retour</a>
        </header>

        <?= $message ?>

        <form method="POST" id="editWordForm" class="admin-form admin-grid">
            <div class="form-group full-width">
                <label>Mot (Kabyle / Taqbaylit) *</label>
                <input type="text" name="mot" value="<?= htmlspecialchars($word['mot']) ?>" required>
            </div>
            
            <div class="form-group full-width">
                <label>Signification (Français) *</label>
                <input type="text" name="signification" value="<?= htmlspecialchars($word['signification']) ?>" required>
            </div>

            <div class="form-group">
                <label>Lettre Pivot (Consonne) *</label>
                <select name="lettre" id="familleSelect" required>
                    <?php foreach(array_keys($familles) as $f): ?>
                        <option value="<?= $f ?>" <?= ($word['lettre'] == $f) ? 'selected' : '' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Terminaison (Rime) *</label>
                <select name="rime" id="rimeSelect" required></select>
            </div>

            <div class="form-group">
                <label>Classe Grammaticale</label>
                <select name="classe_grammaticale">
                    <?php $classes = ['Nom', 'Verbe', 'Adjectif', 'Adverbe', 'Autre']; 
                    foreach($classes as $c): ?>
                        <option value="<?= $c ?>" <?= ($word['classe_grammaticale'] == $c) ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Genre & Nombre</label>
                <div style="display: flex; gap: 8px;">
                    <select name="genre">
                        <option value="Amlay" <?= ($word['genre'] == 'Amlay') ? 'selected' : '' ?>>Masc (Amlay)</option>
                        <option value="Untlay" <?= ($word['genre'] == 'Untlay') ? 'selected' : '' ?>>Fém (Untlay)</option>
                        <option value="N/A" <?= ($word['genre'] == 'N/A') ? 'selected' : '' ?>>N/A</option>
                    </select>
                    <select name="nombre">
                        <option value="Asuf" <?= ($word['nombre'] == 'Asuf') ? 'selected' : '' ?>>Sing (Asuf)</option>
                        <option value="Asget" <?= ($word['nombre'] == 'Asget') ? 'selected' : '' ?>>Plur (Asget)</option>
                        <option value="N/A" <?= ($word['nombre'] == 'N/A') ? 'selected' : '' ?>>N/A</option>
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Exemple</label>
                <textarea name="exemple" rows="3"><?= htmlspecialchars($word['exemple']) ?></textarea>
            </div>

            <div class="full-width">
                <button type="button" class="btn-submit btn-full" onclick="openModal()">Mettre à jour le mot</button>
            </div>
        </form>
    </div>

    <div id="confirmModal" class="modal-overlay">
        <div class="modal-card">
            <span style="font-size: 3.5rem;">💾</span>
            <h2>Enregistrer ?</h2>
            <p>Voulez-vous valider les modifications pour ce mot ?</p>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeModal()">Annuler</button>
                <button class="btn-confirm-save" onclick="document.getElementById('editWordForm').submit()">Valider</button>
            </div>
        </div>
    </div>

    <script>
        const rimesData = <?= json_encode($familles) ?>;
        const currentRime = "<?= $word['rime'] ?>";
        const fSel = document.getElementById('familleSelect');
        const rSel = document.getElementById('rimeSelect');

        function updateRimes(fam, selRime = null) {
            rSel.innerHTML = '<option value="">-- Rime --</option>';
            if (fam && rimesData[fam]) {
                rimesData[fam].forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r; 
                    opt.textContent = r;
                    if (r === selRime) opt.selected = true;
                    rSel.appendChild(opt);
                });
            }
        }

        // Initialisation
        updateRimes(fSel.value, currentRime);
        fSel.addEventListener('change', function() { updateRimes(this.value); });

        function openModal() { document.getElementById('confirmModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('confirmModal').style.display = 'none'; }
    </script>
</body>
</html>