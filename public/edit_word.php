<?php
/**
 * PAGE D'ÉDITION DE MOT
 * Sécurisée par rôle et auteur.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
if (!Auth::isLogged()) { header('Location: login.php'); exit; }

$engine = new RhymeEngine();
$admin = new AdminEngine($engine->getPDO());
$message = "";

$id = (int)($_GET['id'] ?? 0);

// Récupération des données avec infos auteur pour la sécurité
$stmt = $engine->getPDO()->prepare("
    SELECT r.*, u.role as author_role 
    FROM rimes r 
    LEFT JOIN users u ON r.auteur_id = u.id 
    WHERE r.id = ?
");
$stmt->execute([$id]);
$word = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$word) { die("Mot non trouvé."); }

// VÉRIFICATION DES DROITS
if (!Auth::canManage($word['auteur_id'], $word['author_role'])) {
    header('Location: admin.php?msg=denied');
    exit;
}

$familles = [
    'B'=>['BA','BI','BU','AB','IB','UB','EB'], 'C'=>['CA','CI','CU','AC','IC','UC','EC'],
    'Č'=>['ČA','ČI','ČU','AČ','IČ','UČ','EČ'], 'D'=>['DA','DI','DU','AD','ID','UD','ED'],
    'Ḍ'=>['ḌA','ḌI','ḌU','AḌ','IḌ','UḌ','EḌ'], 'F'=>['FA','FI','FU','AF','IF','UF','EF'],
    'G'=>['GA','GI','GU','AG','IG','UG','EG'], 'Ǧ'=>['ǦA','ǦI','ǦU','AǦ','IǦ','UǦ','EǦ'],
    'H'=>['HA','HI','HU','AH','IH','UH','EH'], 'Ḥ'=>['ḤA','ḤI','ḤU','AḤ','IḤ','UḤ','EḤ'],
    'J'=>['JA','JI','JU','AJ','IJ','UJ','EJ'], 'K'=>['KA','KI','KU','AK','IK','UK','EK'],
    'L'=>['LA','LI','LU','AL','IL','UL','EL'], 'M'=>['MA','MI','MU','AM','IM','UM','EM'],
    'N'=>['NA','NI','NU','AN','IN','UN','EN'], 'Q'=>['QA','QI','QU','AQ','IQ','UQ','EQ'],
    'R'=>['RA','RI','RU','AR','IR','UR','ER'], 'Ṛ'=>['ṚA','ṚI','ṚU','AṚ','IṚ','UṚ','EṚ'],
    'S'=>['SA','SI','SU','AS','IS','US','ES'], 'Ṣ'=>['ṢA','ṢI','ṢU','AṢ','IṢ','UṢ','EṢ'],
    'T'=>['TA','TI','TU','AT','IT','UT','ET'], 'Ṭ'=>['ṬA','ṬI','ṬU','AṬ','IṬ','UṬ','EṬ'],
    'X'=>['XA','XI','XU','AX','IX','UX','EX'], 'Y'=>['YA','YI','YU','AY','IY','UY','EY'],
    'Z'=>['ZA','ZI','ZU','AZ','IZ','UZ','EZ'], 'Ẓ'=>['ẒA','ẒI','ẒU','AẒ','IẒ','UẒ','EẒ'],
    'Ž'=>['ŽA','ŽI','ŽU','AŽ','IŽ','UŽ','EŽ']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'mot'           => trim($_POST['mot']),
        'rime'          => trim($_POST['rime']),
        'signification' => trim($_POST['signification']),
        'exemple'       => trim($_POST['exemple']),
        'famille'       => trim($_POST['famille'])
    ];

    if ($admin->updateWord($id, $data)) {
        $message = "<p class='success-msg'>✅ Modifications enregistrées !</p>";
        // Recharger les données fraîches
        $stmt->execute([$id]);
        $word = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier : <?= htmlspecialchars($word['mot']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>
    
    <div class="container">
        <header class="admin-header" style="background:none; box-shadow:none; padding:0;">
            <h1>Modifier le mot</h1>
            <a href="admin.php" class="btn-primary">← Retour</a>
        </header>

        <?= $message ?>

        <form method="POST" class="admin-form">
            <div class="form-group">
                <label>Mot</label>
                <input type="text" name="mot" value="<?= htmlspecialchars($word['mot']) ?>" required>
            </div>
            <div class="form-group">
                <label>Signification</label>
                <input type="text" name="signification" value="<?= htmlspecialchars($word['signification']) ?>" required>
            </div>

            <div class="form-group">
                <label>Famille (Lettre)</label>
                <select name="famille" id="familleSelect" required>
                    <?php foreach(array_keys($familles) as $f): ?>
                        <option value="<?= $f ?>" <?= ($word['famille'] == $f) ? 'selected' : '' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sous-famille (Rime)</label>
                <select name="rime" id="rimeSelect" required></select>
            </div>

            <div class="form-group full-width">
                <label>Exemple</label>
                <textarea name="exemple" rows="3"><?= htmlspecialchars($word['exemple']) ?></textarea>
            </div>

            <div class="full-width">
                <button type="submit" class="btn-large">Mettre à jour le mot</button>
            </div>
        </form>
    </div>

    <script>
        const rimesData = <?= json_encode($familles) ?>;
        const currentRime = "<?= $word['rime'] ?>";
        const familleSelect = document.getElementById('familleSelect');
        const rimeSelect = document.getElementById('rimeSelect');

        function updateRimes(selectedFamille, selectedRime = null) {
            rimeSelect.innerHTML = '<option value="">-- Choisir une rime --</option>';
            if (selectedFamille && rimesData[selectedFamille]) {
                rimesData[selectedFamille].forEach(rime => {
                    const option = document.createElement('option');
                    option.value = rime; option.textContent = rime;
                    if (rime === selectedRime) option.selected = true;
                    rimeSelect.appendChild(option);
                });
            }
        }

        updateRimes(familleSelect.value, currentRime);
        familleSelect.addEventListener('change', function() { updateRimes(this.value); });
    </script>
</body>
</html>