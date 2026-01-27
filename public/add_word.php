<?php
/**
 * PAGE D'AJOUT DE MOT - VERSION DESIGN AMAWAL PRO
 * Alignée sur l'esthétique de profile.php avec champs à dégradé et boutons stylisés.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/terminaisons.php'; 

Auth::init();
if (!Auth::isLogged()) { header('Location: login.php'); exit; }

$engine = new RhymeEngine();
$admin = new AdminEngine($engine->getPDO());
$message = "";
$showDuplicateWarning = false;
$savedData = [];

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
    <style>
        body { background: #f8fafc; color: #1e293b; }

        .add-container { max-width: 900px; margin: 40px auto; }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* Bouton Tableau de Bord (Orange Amawal) */
        .btn-dashboard-orange {
            background: #e67e22 !important;
            color: white !important;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(230, 126, 34, 0.2);
        }
        .btn-dashboard-orange:hover { 
            background: #d35400 !important; 
            transform: translateX(3px);
            box-shadow: 0 6px 12px rgba(230, 126, 34, 0.3);
        }

        /* Carte de formulaire large */
        .add-card {
            background: white;
            padding: 45px;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border-top: 6px solid #e67e22;
        }

        /* Style des champs (Dégradé intérieur comme profile.php) */
        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 14px;
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            font-size: 1rem;
            background: linear-gradient(to bottom right, #ffffff, #faf9f7);
            transition: 0.3s;
            color: #1e293b;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #e67e22;
            outline: none;
            background: #ffffff;
            box-shadow: inset 0 2px 4px rgba(230, 126, 34, 0.05);
        }

        /* Bouton Enregistrer Centré (Vert) */
        .submit-container {
            text-align: center;
            margin-top: 40px;
        }

        .btn-save-large {
            background: #27ae60 !important;
            color: white !important;
            padding: 18px 60px;
            border-radius: 15px;
            font-weight: 900;
            font-size: 1.2rem;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.25);
        }
        .btn-save-large:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 12px 25px rgba(39, 174, 96, 0.35); 
            background: #219150 !important;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        .full-width { grid-column: span 2; }

        @media (max-width: 768px) {
            .admin-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>
    
    <div class="container add-container">
        <header class="admin-header">
            <div>
                <h1 style="margin:0; font-weight: 900; font-size: 2.2rem; color: #1e293b;">Nouveau mot</h1>
                <p style="color: #64748b; margin-top: 5px;">Ajoutez une nouvelle entrée linguistique au dictionnaire</p>
            </div>
            <a href="admin.php" class="btn-dashboard-orange">
                <span>📊</span> Tableau de bord
            </a>
        </header>

        <?= $message ?>

        <?php if ($showDuplicateWarning): ?>
            <div class="error-box" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 30px; border-radius: 15px; margin-bottom: 30px; text-align:center; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <h3 style="margin-top:0;">⚠️ Ce mot existe déjà !</h3>
                <p>Le mot <strong>"<?= htmlspecialchars($savedData['mot']) ?>"</strong> est déjà présent dans la base.</p>
                <p>Souhaitez-vous l'ajouter comme une <strong>nouvelle variante</strong> ?</p>
                
                <form method="POST" style="margin-top: 20px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <?php foreach($savedData as $key => $val): ?>
                        <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($val) ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="confirm_variant" value="1">
                    
                    <button type="submit" class="btn-save-large" style="font-size:1rem; padding: 12px 25px;">✅ Confirmer la variante</button>
                    <a href="add_word.php" class="btn-dashboard-orange" style="background: #94a3b8 !important; box-shadow:none;">❌ Annuler</a>
                </form>
            </div>
        <?php endif; ?>

        <div class="add-card">
            <form method="POST" class="admin-grid" style="<?= $showDuplicateWarning ? 'opacity: 0.2; pointer-events: none;' : '' ?>">
                
                <div class="form-group full-width">
                    <label>Mot (Kabyle / Taqbaylit)</label>
                    <input type="text" name="mot" required value="<?= htmlspecialchars($savedData['mot'] ?? '') ?>" placeholder="Saisissez le mot..." autofocus>
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
                        <option value="">-- Sélectionnez d'abord la lettre --</option>
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
                    <div style="display: flex; gap: 10px;">
                        <select name="genre">
                            <option value="Amlay" <?= (isset($savedData['genre']) && $savedData['genre'] == 'Amlay') ? 'selected' : '' ?>>Masculin</option>
                            <option value="Untlay" <?= (isset($savedData['genre']) && $savedData['genre'] == 'Untlay') ? 'selected' : '' ?>>Féminin</option>
                            <option value="N/A" <?= (isset($savedData['genre']) && $savedData['genre'] == 'N/A') ? 'selected' : '' ?>>N/A</option>
                        </select>
                        <select name="nombre">
                            <option value="Asuf" <?= (isset($savedData['nombre']) && $savedData['nombre'] == 'Asuf') ? 'selected' : '' ?>>Singulier</option>
                            <option value="Asget" <?= (isset($savedData['nombre']) && $savedData['nombre'] == 'Asget') ? 'selected' : '' ?>>Pluriel</option>
                            <option value="N/A" <?= (isset($savedData['nombre']) && $savedData['nombre'] == 'N/A') ? 'selected' : '' ?>>N/A</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Signification (Français)</label>
                    <input type="text" name="signification" required value="<?= htmlspecialchars($savedData['signification'] ?? '') ?>" placeholder="Sens du mot en français...">
                </div>

                <div class="form-group full-width">
                    <label>Exemple d'utilisation (Amedya)</label>
                    <textarea name="exemple" rows="4" placeholder="Écrivez une phrase d'exemple..."><?= htmlspecialchars($savedData['exemple'] ?? '') ?></textarea>
                </div>

                <div class="full-width submit-container">
                    <button type="submit" class="btn-save-large">Enregistrer dans le dictionnaire</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const rimesData = <?= getRimesJson($familles) ?>;
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
        
        if(familleSelect.value) { 
            updateRimes(familleSelect.value, savedRime); 
        }
    </script>
</body>
</html>