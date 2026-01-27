<?php
/**
 * PAGE ÉDITION DE MOT - DESIGN AMAWAL PRO
 * Version 2026 : Support des terminaisons dynamiques et design harmonisé.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/terminaisons.php';

Auth::init();
$engine = new RhymeEngine();
$db = $engine->getPDO();

if (!Auth::isLogged($db)) { header('Location: login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
$admin = new AdminEngine($db);
$message = "";

// Récupération du mot avec vérification des droits
$stmt = $db->prepare("
    SELECT r.*, u.role as author_role 
    FROM rimes r 
    LEFT JOIN users u ON r.auteur_id = u.id 
    WHERE r.id = ?
");
$stmt->execute([$id]);
$word = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$word) { die("Erreur : Ce mot n'existe pas dans le dictionnaire."); }

if (!Auth::canManage($word['auteur_id'], $word['author_role'])) {
    header('Location: admin.php?msg=denied');
    exit;
}

// TRAITEMENT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'mot'                 => trim($_POST['mot']),
        'lettre'              => trim($_POST['lettre']),
        'rime'                => trim($_POST['rime']),
        'signification'       => trim($_POST['signification']),
        'exemple'             => trim($_POST['exemple']),
        'classe_grammaticale' => trim($_POST['classe_grammaticale']),
        'genre'               => trim($_POST['genre']),
        'nombre'              => trim($_POST['nombre'])
    ];

    if (empty($data['mot']) || empty($data['lettre']) || empty($data['rime'])) {
        $message = "<p class='error-msg'>❌ Les champs Mot, Lettre et Rime sont obligatoires.</p>";
    } else {
        if ($admin->updateWord($id, $data)) {
            $message = "<p class='success-msg'>✅ Entrée linguistique mise à jour avec succès.</p>";
            $stmt->execute([$id]); $word = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "<p class='error-msg'>❌ Erreur lors de la mise à jour des données.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Mot - Amawal Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f8fafc; color: #1e293b; }
        .add-container { max-width: 900px; margin: 40px auto; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-dashboard-orange {
            background: #e67e22 !important; color: white !important;
            padding: 12px 24px; border-radius: 10px; text-decoration: none;
            font-weight: 800; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;
            transition: 0.3s; box-shadow: 0 4px 6px rgba(230, 126, 34, 0.2);
        }
        .btn-dashboard-orange:hover { transform: translateX(3px); background: #d35400 !important; }

        .add-card {
            background: white; padding: 45px; border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05); border-top: 6px solid #e67e22;
        }

        .form-group label { display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 10px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;
            font-size: 1rem; background: linear-gradient(to bottom right, #ffffff, #faf9f7);
            transition: 0.3s; color: #1e293b; box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #e67e22; outline: none; background: #ffffff;
        }

        .admin-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .full-width { grid-column: span 2; }
        .submit-container { text-align: center; margin-top: 40px; }
        .btn-save-large {
            background: #27ae60 !important; color: white !important;
            padding: 18px 60px; border-radius: 15px; font-weight: 900;
            font-size: 1.2rem; border: none; cursor: pointer; transition: 0.3s;
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.25);
        }
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px);
            display: none; align-items: center; justify-content: center; z-index: 10000;
        }
        .modal-card { background: white; padding: 40px; border-radius: 24px; text-align: center; max-width: 400px; width: 90%; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container add-container">
        <header class="admin-header">
            <div>
                <h1 style="margin:0; font-weight: 900; font-size: 2.2rem; color: #1e293b;">Modifier le mot</h1>
                <p style="color: #64748b; margin-top: 5px;">Édition de : <strong><?= htmlspecialchars($word['mot']) ?></strong></p>
            </div>
            <a href="admin.php" class="btn-dashboard-orange">
                <span>📊</span> Tableau de bord
            </a>
        </header>

        <?= $message ?>

        <div class="add-card">
            <form method="POST" id="editWordForm" class="admin-grid">
                <div class="form-group full-width">
                    <label>Mot (Kabyle / Taqbaylit)</label>
                    <input type="text" name="mot" value="<?= htmlspecialchars($word['mot']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Lettre Pivot</label>
                    <select name="lettre" id="familleSelect" required>
                        <?php foreach(array_keys($familles) as $f): ?>
                            <option value="<?= $f ?>" <?= ($word['lettre'] == $f) ? 'selected' : '' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Terminaison (Rime)</label>
                    <select name="rime" id="rimeSelect" required></select>
                </div>
                <div class="form-group">
                    <label>Classe Grammaticale</label>
                    <select name="classe_grammaticale">
                        <?php $c_list = ['Nom', 'Verbe', 'Adjectif', 'Adverbe', 'Autre'];
                        foreach($c_list as $cl): ?>
                            <option value="<?= $cl ?>" <?= ($word['classe_grammaticale'] == $cl) ? 'selected' : '' ?>><?= $cl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Genre & Nombre</label>
                    <div style="display: flex; gap: 10px;">
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
                    <label>Signification (Français)</label>
                    <input type="text" name="signification" value="<?= htmlspecialchars($word['signification']) ?>" required>
                </div>
                <div class="form-group full-width">
                    <label>Exemple (Amedya)</label>
                    <textarea name="exemple" rows="4"><?= htmlspecialchars($word['exemple']) ?></textarea>
                </div>
                <div class="full-width submit-container">
                    <button type="button" class="btn-save-large" onclick="openModal()">Valider les changements</button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmModal" class="modal-overlay">
        <div class="modal-card">
            <div style="font-size: 4rem; margin-bottom: 20px;">💾</div>
            <h2 style="font-weight: 900;">Confirmer ?</h2>
            <p style="color: #64748b; margin-bottom: 30px;">Appliquer les modifications à cette entrée ?</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button class="btn-modal" style="background:#f1f5f9; color:#64748b;" onclick="closeModal()">ANNULER</button>
                <button class="btn-modal" style="background:#27ae60; color:white;" onclick="submitForm()">CONFIRMER</button>
            </div>
        </div>
    </div>

    <script>
        const rimesData = <?= getRimesJson($familles) ?>;
        const currentRime = "<?= $word['rime'] ?>";
        const fSel = document.getElementById('familleSelect');
        const rSel = document.getElementById('rimeSelect');

        function updateRimes(fam, selRime = null) {
            rSel.innerHTML = '<option value="">-- Sélection --</option>';
            if (fam && rimesData[fam]) {
                rimesData[fam].forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r; opt.textContent = r;
                    if (r === selRime) opt.selected = true;
                    rSel.appendChild(opt);
                });
            }
        }
        updateRimes(fSel.value, currentRime);
        fSel.addEventListener('change', function() { updateRimes(this.value); });
        function openModal() { document.getElementById('confirmModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('confirmModal').style.display = 'none'; }
        function submitForm() { document.getElementById('editWordForm').submit(); }
    </script>
</body>
</html>