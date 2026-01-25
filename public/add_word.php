<?php
/**
 * PAGE D'AJOUT DE MOT AVEC GESTION DE DOUBLONS/VARIANTES
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
if (!Auth::isLogged()) { header('Location: login.php'); exit; }

$engine = new RhymeEngine();
$admin = new AdminEngine($engine->getPDO());
$message = "";
$showDuplicateWarning = false;
$savedData = [];

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
    $savedData = [
        'mot'           => trim($_POST['mot']),
        'rime'          => trim($_POST['rime']),
        'signification' => trim($_POST['signification']),
        'exemple'       => trim($_POST['exemple']),
        'famille'       => trim($_POST['famille'])
    ];

    $isForced = isset($_POST['confirm_variant']) && $_POST['confirm_variant'] == '1';

    if (!empty($savedData['mot']) && !empty($savedData['rime'])) {
        
        // Si on ne force pas, on vérifie si le mot existe
        if (!$isForced && $admin->checkWordExists($savedData['mot']) > 0) {
            $showDuplicateWarning = true;
        } else {
            // Sinon on insère (soit c'est nouveau, soit c'est forcé)
            if ($admin->addWord($savedData)) {
                header('Location: admin.php?msg=added');
                exit;
            } else {
                $message = "<p class='error-msg'>❌ Erreur lors de l'insertion.</p>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un mot - Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>
    
    <div class="container">
        <header class="admin-header">
            <h1>Nouveau mot</h1>
            <a href="admin.php" class="btn-primary">← Dashboard</a>
        </header>

        <?= $message ?>

        <?php if ($showDuplicateWarning): ?>
            <div class="error-box" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align:center;">
                <h3>⚠️ Ce mot existe déjà !</h3>
                <p>Le mot <strong>"<?= htmlspecialchars($savedData['mot']) ?>"</strong> est déjà présent dans le dictionnaire.</p>
                <p>Souhaitez-vous l'ajouter comme une <strong>nouvelle variante</strong> ?</p>
                
                <form method="POST" style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                    <input type="hidden" name="mot" value="<?= htmlspecialchars($savedData['mot']) ?>">
                    <input type="hidden" name="rime" value="<?= htmlspecialchars($savedData['rime']) ?>">
                    <input type="hidden" name="signification" value="<?= htmlspecialchars($savedData['signification']) ?>">
                    <input type="hidden" name="exemple" value="<?= htmlspecialchars($savedData['exemple']) ?>">
                    <input type="hidden" name="famille" value="<?= htmlspecialchars($savedData['famille']) ?>">
                    <input type="hidden" name="confirm_variant" value="1">
                    
                    <button type="submit" class="btn-add">✅ Oui, ajouter la variante</button>
                    <a href="add_word.php" class="btn-primary" style="background: var(--text-muted);">❌ Non, annuler</a>
                </form>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-form admin-grid" style="<?= $showDuplicateWarning ? 'opacity: 0.3; pointer-events: none;' : '' ?>">
            <div class="form-group">
                <label>Mot (Kabyle)</label>
                <input type="text" name="mot" required value="<?= htmlspecialchars($savedData['mot'] ?? '') ?>" placeholder="Awal..." autofocus>
            </div>
            <div class="form-group">
                <label>Signification</label>
                <input type="text" name="signification" required value="<?= htmlspecialchars($savedData['signification'] ?? '') ?>" placeholder="Anamek...">
            </div>

            <div class="form-group">
                <label>Famille (Lettre)</label>
                <select name="famille" id="familleSelect" required>
                    <option value="">-- Choisir une lettre --</option>
                    <?php foreach(array_keys($familles) as $f): ?>
                        <option value="<?= $f ?>" <?= (isset($savedData['famille']) && $savedData['famille'] == $f) ? 'selected' : '' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sous-famille (Rime)</label>
                <select name="rime" id="rimeSelect" required disabled>
                    <option value="">-- Sélectionnez d'abord la lettre --</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label>Exemple</label>
                <textarea name="exemple" rows="3" placeholder="Amedya..."><?= htmlspecialchars($savedData['exemple'] ?? '') ?></textarea>
            </div>

            <div class="full-width">
                <button type="submit" class="btn-submit btn-full">Enregistrer dans le dictionnaire</button>
            </div>
        </form>
    </div>

    <script>
        const rimesData = <?= json_encode($familles) ?>;
        const savedRime = "<?= $savedData['rime'] ?? '' ?>";
        const familleSelect = document.getElementById('familleSelect');
        const rimeSelect = document.getElementById('rimeSelect');

        function updateRimes(selectedFamille, selectedRime = null) {
            rimeSelect.innerHTML = '<option value="">-- Choisir une rime --</option>';
            if (selectedFamille && rimesData[selectedFamille]) {
                rimeSelect.disabled = false;
                rimesData[selectedFamille].forEach(rime => {
                    const option = document.createElement('option');
                    option.value = rime; option.textContent = rime;
                    if (rime === selectedRime) option.selected = true;
                    rimeSelect.appendChild(option);
                });
            } else { rimeSelect.disabled = true; }
        }

        familleSelect.addEventListener('change', function() { updateRimes(this.value); });
        
        // Initialisation si données déjà présentes (cas du retour erreur)
        if(familleSelect.value) { updateRimes(familleSelect.value, savedRime); }
    </script>
</body>
</html>