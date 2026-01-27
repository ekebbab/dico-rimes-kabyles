<?php
/**
 * MON PROFIL - VERSION DESIGN OPTIMISÉE
 * Intègre les retours sur les boutons et les styles de champs.
 */
require_once __DIR__ . '/../src/Auth.php';
Auth::init();

require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';

$engine = new RhymeEngine();
$db = $engine->getPDO();

if (!Auth::isLogged($db)) {
    header('Location: login.php');
    exit;
}

$admin = new AdminEngine($db);
$userId = Auth::getUserId();
$user = $admin->getUserById($userId);
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'prenom'   => trim($_POST['prenom']),
        'nom'      => trim($_POST['nom']),
        'email'    => trim($_POST['email']),
        'password' => $_POST['new_password']
    ];

    if ($admin->updateUser($userId, $data)) {
        $message = "<p class='success-msg'>✅ Tifrat! Profil mis à jour avec succès.</p>";
        $user = $admin->getUserById($userId);
    } else {
        $message = "<p class='error-msg'>❌ Erreur lors de l'enregistrement.</p>";
    }
}

$countPerso = $db->prepare("SELECT COUNT(*) FROM rimes WHERE auteur_id = ?");
$countPerso->execute([$userId]);
$myCount = $countPerso->fetchColumn();
$onlineUsers = $db->query("SELECT COUNT(*) FROM users WHERE last_seen > datetime('now', '-5 minutes')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ber">
<head>
    <meta charset="UTF-8">
    <title>Profil-iw - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f8fafc; color: #1e293b; }
        .profile-header { display: flex; justify-content: space-between; align-items: center; margin: 30px 0; }
        
        /* Bouton Tableau de bord (Orange assorti) */
        .btn-dashboard-orange { 
            background: #e67e22 !important; 
            color: white !important; 
            padding: 10px 22px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 800; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(230, 126, 34, 0.2);
        }
        .btn-dashboard-orange:hover { background: #d35400 !important; transform: translateX(3px); }

        .profile-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-top: 5px solid #e67e22; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        
        .form-group label { display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
        
        /* Style des champs avec dégradé intérieur vers gris orangé clair */
        .form-group input { 
            width: 100%; 
            padding: 14px; 
            border: 2px solid #f1f5f9; 
            border-radius: 10px; 
            font-size: 1rem; 
            background: linear-gradient(to bottom right, #ffffff, #faf9f7); 
            transition: 0.3s;
        }
        .form-group input:focus { 
            border-color: #e67e22; 
            outline: none; 
            background: #ffffff;
            box-shadow: inset 0 2px 4px rgba(230, 126, 34, 0.05);
        }
        .readonly-field { background: #f1f5f9 !important; color: #94a3b8; cursor: not-allowed; border: 2px solid #e2e8f0; }

        .password-wrapper { position: relative; }
        .toggle-eye { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; }

        /* Bouton de mise à jour centré (Vert assorti) */
        .submit-area { margin-top: 40px; text-align: center; }
        .btn-update-green { 
            background: #27ae60 !important; 
            color: white !important; 
            padding: 16px 50px; 
            border-radius: 12px; 
            font-weight: 900; 
            font-size: 1.1rem; 
            border: none; 
            cursor: pointer; 
            transition: 0.3s;
            box-shadow: 0 6px 15px rgba(39, 174, 96, 0.3);
        }
        .btn-update-green:hover { 
            transform: translateY(-3px); 
            background: #219150 !important;
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.4); 
        }

        .stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
        .stat-box { background: white; padding: 25px; border-radius: 15px; text-align: center; border-bottom: 4px solid #e2e8f0; }
        .stat-val { font-size: 2rem; font-weight: 900; color: #2c3e50; display: block; }
        .stat-lbl { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; }

        #confirmModal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px);
            display: none; align-items: center; justify-content: center; z-index: 10000;
        }
        .modal-card { background: white; padding: 40px; border-radius: 24px; text-align: center; max-width: 400px; width: 90%; }
        .btn-modal { padding: 12px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; border: none; transition: 0.2s; }
        .btn-m-cancel { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-m-confirm { background: #27ae60; color: white; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container" style="max-width: 800px;">
        <header class="profile-header">
            <div>
                <h1 style="font-weight: 900; color: #1e293b; margin:0;">Profil-iw</h1>
                <p style="color: #64748b; margin-top: 5px;">Amsidef n <strong><?= htmlspecialchars($user['username']) ?></strong></p>
            </div>
            <a href="admin.php" class="btn-dashboard-orange">
                <span>📊</span> Tableau de bord
            </a>
        </header>

        <?= $message ?>

        <div class="profile-card">
            <form method="POST" id="profileForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Isem (Prénom)</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Anessem (Nom)</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Imayl (Email)</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Pseudo (Ur yettbeddil ara)</label>
                        <input type="text" class="readonly-field" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Awal n uɛeddi n tura</label>
                        <div class="password-wrapper">
                            <input type="password" name="current_password" id="current_pass" placeholder="Requis pour changer">
                            <span class="toggle-eye" onclick="toggleVis('current_pass', this)">👁️</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Awal n uɛeddi amaynut</label>
                        <div class="password-wrapper">
                            <input type="password" name="new_password" id="new_pass" placeholder="Laissez vide si inchangé">
                            <span class="toggle-eye" onclick="toggleVis('new_pass', this)">👁️</span>
                        </div>
                    </div>
                </div>

                <div class="submit-area">
                    <button type="button" class="btn-update-green" onclick="showModal()">
                        Mettre à jour mes informations
                    </button>
                </div>
            </form>
        </div>

        <div class="stats-row">
            <div class="stat-box">
                <span class="stat-val"><?= $myCount ?></span>
                <span class="stat-lbl">Mes contributions</span>
            </div>
            <div class="stat-box">
                <span class="stat-val" style="color: #27ae60;"><?= $onlineUsers ?></span>
                <span class="stat-lbl">Membres en ligne</span>
            </div>
        </div>
    </div>

    <div id="confirmModal" onclick="hideModal()">
        <div class="modal-card" onclick="event.stopPropagation()">
            <div style="font-size: 4rem; margin-bottom: 20px;">🛡️</div>
            <h2 style="font-weight: 900;">Confirmation</h2>
            <p style="color: #64748b; margin-bottom: 25px;">Voulez-vous enregistrer les modifications de votre profil ?</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button class="btn-modal btn-m-cancel" onclick="hideModal()">ANNULER</button>
                <button class="btn-modal btn-m-confirm" onclick="document.getElementById('profileForm').submit()">VALIDER</button>
            </div>
        </div>
    </div>

    <script>
        function toggleVis(id, icon) {
            const el = document.getElementById(id);
            if (el.type === "password") {
                el.type = "text"; icon.textContent = "🙈";
            } else {
                el.type = "password"; icon.textContent = "👁️";
            }
        }
        function showModal() { document.getElementById('confirmModal').style.display = 'flex'; }
        function hideModal() { document.getElementById('confirmModal').style.display = 'none'; }
    </script>
</body>
</html>