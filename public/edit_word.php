<?php
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

$engine = new RhymeEngine();
if (!Auth::isLogged()) { header('Location: login.php'); exit; }

$admin = new AdminEngine($engine->getPDO());
$message = "";

// Récupération du mot à modifier
$id = $_GET['id'] ?? null;
$word = $admin->getWordById($id);

if (!$word) { die("Mot non trouvé."); }

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
        'id'            => $id,
        'mot'           => $_POST['mot'],
        'rime'          => $_POST['rime'],
        'signification' => $_POST['signification'],
        'exemple'       => $_POST['exemple'],
        'famille'       => $_POST['famille']
    ];

    if ($admin->updateWord($id, $data)) {
        $message = "<p class='success-msg'>✅ Modifications enregistrées !</p>";
        $word = $admin->getWordById($id); // Rafraîchir les données locales
    }
}
?>

<!DOCTYPE html>
<html lang="ber">
<head>
    <meta charset="UTF-8">
    <title>Modifier le mot - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-form { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 25px; 
            background: white; padding: 30px; border-radius: 12px; 
            box-shadow: var(--shadow); max-width: 800px; margin: 0 auto;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; box-sizing: border-box; padding: 12px; 
            border: 1px solid var(--border-color); border-radius: 6px; 
        }
        .full-width { grid-column: 1 / span 2; }
        .form-group label { font-weight: bold; margin-bottom: 8px; }
        .success-msg { text-align: center; color: #27ae60; font-weight: bold; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>
    
    <div class="container">
        <header>
            <h1>Modifier : <?= htmlspecialchars($word['mot']) ?></h1>
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
                <select name="rime" id="rimeSelect" required>
                    </select>
            </div>

            <div class="form-group full-width">
                <label>Exemple</label>
                <textarea name="exemple" rows="3"><?= htmlspecialchars($word['exemple']) ?></textarea>
            </div>

            <div class="full-width">
                <button type="submit" style="width: 100%;">Mettre à jour le mot</button>
                <p style="text-align: center; margin-top: 15px;">
                    <a href="admin.php" style="color: var(--text-muted);">Retour au dashboard</a>
                </p>
            </div>
        </form>
    </div>

    <script>
        const rimesData = <?= json_encode($familles) ?>;
        const currentRime = "<?= $word['rime'] ?>"; // On récupère la rime actuelle
        
        const familleSelect = document.getElementById('familleSelect');
        const rimeSelect = document.getElementById('rimeSelect');

        function updateRimes(selectedFamille, selectedRime = null) {
            rimeSelect.innerHTML = '<option value="">-- Choisir une rime --</option>';
            if (selectedFamille && rimesData[selectedFamille]) {
                rimesData[selectedFamille].forEach(rime => {
                    const option = document.createElement('option');
                    option.value = rime;
                    option.textContent = rime;
                    if (rime === selectedRime) option.selected = true;
                    rimeSelect.appendChild(option);
                });
            }
        }

        // Initialisation au chargement
        updateRimes(familleSelect.value, currentRime);

        // Changement dynamique
        familleSelect.addEventListener('change', function() {
            updateRimes(this.value);
        });
    </script>
</body>
</html>