<?php
/**
 * PAGE D'AJOUT DE MOT - VERSION LINGUISTIQUE AVANCÉE
 * Gère les doublons par variantes et les nouveaux critères (Classe, Genre, Nombre).
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

// Configuration des familles (Lettres) et leurs terminaisons (Rimes) associées
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
    $savedData = [
        'mot'                 => trim($_POST['mot']),
        'lettre'              => trim($_POST['lettre']),
        'rime'                => trim($_POST['rime']),
        'signification'       => trim($_POST['signification']),
        'exemple'             => trim($_POST['exemple']),
        'classe_grammaticale' => trim($_POST['classe_grammaticale']),
        'genre'               => trim($_POST['genre']),
        'nombre'              => trim($_POST['nombre'])
    ];

    $isForced = isset($_POST['confirm_variant']) && $_POST['confirm_variant'] == '1';

    if (!empty($savedData['mot']) && !empty($savedData['lettre'])) {
        
        // Vérification de doublon (sauf si l'utilisateur a déjà cliqué sur "Ajouter la variante")
        if (!$isForced && $admin->checkWordExists($savedData['mot']) > 0) {
            $showDuplicateWarning = true;
        } else {
            if ($admin->addWord($savedData)) {
                header('Location: admin.php?msg=added');
                exit;
            } else {
                $message = "<p class='error-msg'>❌ Erreur lors de l'insertion dans la base de données.</p>";
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
            <div class="error-box" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 25px; border-radius: 12px; margin-bottom: 25px; text-align:center;">
                <h3>⚠️ Ce mot existe déjà !</h3>
                <p>Le mot <strong>"<?= htmlspecialchars($savedData['mot']) ?>"</strong> est déjà présent.</p>
                <p>Souhaitez-vous l'ajouter comme une <strong>nouvelle variante</strong> ?</p>
                
                <form method="POST" style="margin-top: 20px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <input type="hidden" name="mot" value="<?= htmlspecialchars($savedData['mot']) ?>">
                    <input type="hidden" name="lettre" value="<?= htmlspecialchars($savedData['lettre']) ?>">
                    <input type="hidden" name="rime" value="<?= htmlspecialchars($savedData['rime']) ?>">
                    <input type="hidden" name="signification" value="<?= htmlspecialchars($savedData['signification']) ?>">
                    <input type="hidden" name="exemple" value="<?= htmlspecialchars($savedData['exemple']) ?>">
                    <input type="hidden" name="classe_grammaticale" value="<?= htmlspecialchars($savedData['classe_grammaticale']) ?>">
                    <input type="hidden" name="genre" value="<?= htmlspecialchars($savedData['genre']) ?>">
                    <input type="hidden" name="nombre" value="<?= htmlspecialchars($savedData['nombre']) ?>">
                    <input type="hidden" name="confirm_variant" value="1">
                    
                    <button type="submit" class="btn-add">✅ Oui, ajouter la variante</button>
                    <a href="add_word.php" class="btn-primary" style="background: var(--text-muted);">❌ Non, annuler</a>
                </form>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-grid" style="<?= $showDuplicateWarning ? 'opacity: 0.2; pointer-events: none;' : '' ?>">
            
            <div class="form-group full-width">
                <label>Mot (Kabyle / Taqbaylit)</label>
                <input type="text" name="mot" required value="<?= htmlspecialchars($savedData['mot'] ?? '') ?>" placeholder="Awal..." autofocus>
            </div>

            <div class="form-group">
                <label>Lettre Pivot (Consonne)</label>
                <select name="lettre" id="familleSelect" required>
                    <option value="">-- Choisir une lettre --</option>
                    <?php foreach(array_keys($familles) as $f): ?>
                        <option value="<?= $f ?>" <?= (isset($savedData['lettre']) && $savedData['lettre'] == $f) ? 'selected' : '' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Rime (Terminaison)</label>
                <select name="rime" id="rimeSelect" required disabled>
                    <option value="">-- Sélectionnez la lettre --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Classe Grammaticale</label>
                <select name="classe_grammaticale">
                    <option value="Nom" <?= (isset($savedData['classe_grammaticale']) && $savedData['classe_grammaticale'] == 'Nom') ? 'selected' : '' ?>>Nom (Isem)</option>
                    <option value="Verbe" <?= (isset($savedData['classe_grammaticale']) && $savedData['classe_grammaticale'] == 'Verbe') ? 'selected' : '' ?>>Verbe (Amyag)</option>
                    <option value="Adjectif" <?= (isset($savedData['classe_grammaticale']) && $savedData['classe_grammaticale'] == 'Adjectif') ? 'selected' : '' ?>>Adjectif (Aɣawaw)</option>
                    <option value="Adverbe" <?= (isset($savedData['classe_grammaticale']) && $savedData['classe_grammaticale'] == 'Adverbe') ? 'selected' : '' ?>>Adverbe (Amyag n tɣuri)</option>
                    <option value="Autre" <?= (isset($savedData['classe_grammaticale']) && $savedData['classe_grammaticale'] == 'Autre') ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>

            <div class="form-group">
                <label>Genre & Nombre</label>
                <div style="display: flex; gap: 8px;">
                    <select name="genre">
                        <option value="Amlay" <?= (isset($savedData['genre']) && $savedData['genre'] == 'Amlay') ? 'selected' : '' ?>>Masc (Amlay)</option>
                        <option value="Untlay" <?= (isset($savedData['genre']) && $savedData['genre'] == 'Untlay') ? 'selected' : '' ?>>Fém (Untlay)</option>
                        <option value="N/A" <?= (isset($savedData['genre']) && $savedData['genre'] == 'N/A') ? 'selected' : '' ?>>N/A</option>
                    </select>
                    <select name="nombre">
                        <option value="Asuf" <?= (isset($savedData['nombre']) && $savedData['nombre'] == 'Asuf') ? 'selected' : '' ?>>Sing (Asuf)</option>
                        <option value="Asget" <?= (isset($savedData['nombre']) && $savedData['nombre'] == 'Asget') ? 'selected' : '' ?>>Plur (Asget)</option>
                        <option value="N/A" <?= (isset($savedData['nombre']) && $savedData['nombre'] == 'N/A') ? 'selected' : '' ?>>N/A</option>
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Signification (Français)</label>
                <input type="text" name="signification" required value="<?= htmlspecialchars($savedData['signification'] ?? '') ?>" placeholder="Traduction...">
            </div>

            <div class="form-group full-width">
                <label>Exemple d'utilisation</label>
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
                    option.value = rime; 
                    option.textContent = rime;
                    if (rime === selectedRime) option.selected = true;
                    rimeSelect.appendChild(option);
                });
            } else { 
                rimeSelect.disabled = true; 
            }
        }

        familleSelect.addEventListener('change', function() { updateRimes(this.value); });
        
        // Initialisation (pour conserver la rime sélectionnée en cas d'erreur/doublon)
        if(familleSelect.value) { 
            updateRimes(familleSelect.value, savedRime); 
        }
    </script>
</body>
</html>