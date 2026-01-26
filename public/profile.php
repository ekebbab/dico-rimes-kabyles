<?php
/**
 * PAGE DE PROFIL UTILISATEUR - VERSION FINALE SÉCURISÉE
 * Gestion des infos, double mot de passe, statistiques réelles et agent de communication.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

$engine = new RhymeEngine();
$db = $engine->getPDO();

// Sécurité : Tracking last_seen via Auth
if (!Auth::isLogged($db)) { 
    header('Location: login.php'); 
    exit; 
}

$admin = new AdminEngine($db);
$userId = Auth::getUserId();
$message = "";

// 1. TRAITEMENT DU FORMULAIRE DE MISE À JOUR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';

    // Validation des champs obligatoires (Prénom, Nom, Email)
    if (empty($prenom) || empty($nom) || empty($email)) {
        $message = "<p class='error-msg'>❌ Tous les champs (Prénom, Nom, Email) doivent être renseignés.</p>";
    } else {
        $user_data = $admin->getUserById($userId);
        $can_update = true;

        // LOGIQUE MOT DE PASSE : Si on veut changer de mot de passe
        if (!empty($new_pass)) {
            if (empty($current_pass)) {
                $message = "<p class='error-msg'>❌ Vous devez saisir votre mot de passe actuel pour en définir un nouveau.</p>";
                $can_update = false;
            } elseif (!password_verify($current_pass, $user_data['password'])) {
                $message = "<p class='error-msg'>❌ Le mot de passe actuel saisi est incorrect.</p>";
                $can_update = false;
            }
        }

        if ($can_update) {
            $data = [
                'prenom'   => $prenom,
                'nom'      => $nom,
                'email'    => $email,
                'password' => $new_pass // AdminEngine gérera le hachage si non vide
            ];

            if ($admin->updateUser($userId, $data)) {
                $message = "<p class='success-msg'>✅ Votre profil a été mis à jour avec succès !</p>";
            } else {
                $message = "<p class='error-msg'>❌ Une erreur est survenue lors de l'enregistrement en base.</p>";
            }
        }
    }
}

// 2. RÉCUPÉRATION DES DONNÉES FRAÎCHES
$user = $admin->getUserById($userId);

// --- Statistiques ---
$totalRimesPerso = $db->prepare("SELECT COUNT(*) FROM rimes WHERE auteur_id = ?");
$totalRimesPerso->execute([$userId]);
$countPerso = $totalRimesPerso->fetchColumn();

$totalRimesGlobal = $db->query("SELECT COUNT(*) FROM rimes")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$onlineUsers = $db->query("SELECT COUNT(*) FROM users WHERE last_seen > datetime('now', '-5 minutes')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .toggle-eye { position: absolute; right: 12px; cursor: pointer; font-size: 1.2rem; user-select: none; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); animation: fadeIn 0.3s ease; }
        .modal-card { background: white; width: 90%; max-width: 450px; padding: 30px; border-radius: 16px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 25px; }
        .btn-confirm-save { background: var(--primary-color); color: white; border-radius: 8px; }
        .btn-cancel { background: #dfe6e9; color: #2d3436; border-radius: 8px; }
        .readonly-field { background-color: #f0f0f0 !important; cursor: not-allowed; color: #666 !important; }
        .stats-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .stat-box { background: var(--white); padding: 20px; border-radius: 12px; box-shadow: var(--shadow); text-align: center; }
        .stat-number { font-size: 2.2rem; font-weight: bold; color: var(--accent-color); display: block; }
        .stat-label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <header class="admin-header">
            <div>
                <h1>Mon Profil</h1>
                <p>Gestion des informations de <strong><?= htmlspecialchars($user['username']) ?></strong></p>
            </div>
            <a href="admin.php" class="btn-primary">← Dashboard</a>
        </header>

        <?= $message ?>

        <form method="POST" id="profileForm" class="admin-form shadow-box" style="background:white; padding:30px; border-radius:12px;">
            <div class="admin-grid" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Adresse Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" class="readonly-field" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Mot de passe actuel</label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" id="current_password" placeholder="Requis pour changer de mot de passe">
                        <span class="toggle-eye" onclick="togglePass('current_password', this)">👁️</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="new_password" placeholder="Laisser vide pour ne pas changer">
                        <span class="toggle-eye" onclick="togglePass('new_password', this)">👁️</span>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-submit btn-full" onclick="openConfirmModal()">Mettre à jour mon profil</button>
        </form>

        <div class="stats-container">
            <div class="stat-box">
                <h3 style="margin-top:0; color:var(--primary-color);">🌍 Site</h3>
                <div style="display:flex; justify-content: space-around; margin-top:15px;">
                    <div><span class="stat-number"><?= $totalRimesGlobal ?></span><span class="stat-label">Rimes</span></div>
                    <div><span class="stat-number"><?= $totalUsers ?></span><span class="stat-label">Inscrits</span></div>
                    <div><span class="stat-number" style="color:var(--success-color)"><?= $onlineUsers ?></span><span class="stat-label">En ligne</span></div>
                </div>
            </div>
            <div class="stat-box">
                <h3 style="margin-top:0; color:var(--primary-color);">👤 Moi</h3>
                <div style="display:flex; justify-content: space-around; margin-top:15px;">
                    <div><span class="stat-number"><?= $countPerso ?></span><span class="stat-label">Mes rimes</span></div>
                    <div><span class="stat-number" style="font-size:1.1rem; padding: 12px 0;"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span><span class="stat-label">Membre depuis</span></div>
                    <div><span class="badge" style="margin-top:15px;"><?= ucfirst($user['role']) ?></span><span class="stat-label" style="display:block;">Rang</span></div>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="modal-overlay">
        <div class="modal-card">
            <span style="font-size: 3.5rem; display: block; margin-bottom: 15px;">🛡️</span>
            <h2>Confirmer ?</h2>
            <p>Voulez-vous enregistrer les modifications apportées à votre profil ?</p>
            <div class="modal-buttons">
                <button class="btn-modal btn-cancel" onclick="closeConfirmModal()">Annuler</button>
                <button class="btn-modal btn-confirm-save" onclick="document.getElementById('profileForm').submit()">Valider</button>
            </div>
        </div>
    </div>

    <script>
        function togglePass(id, icon) {
            const input = document.getElementById(id);
            input.type = input.type === "password" ? "text" : "password";
            icon.textContent = input.type === "password" ? "👁️" : "🙈";
        }
        function openConfirmModal() { document.getElementById('confirmModal').style.display = 'flex'; }
        function closeConfirmModal() { document.getElementById('confirmModal').style.display = 'none'; }
        window.onclick = function(e) { if (e.target.className === 'modal-overlay') closeConfirmModal(); }
    </script>
</body>
</html>