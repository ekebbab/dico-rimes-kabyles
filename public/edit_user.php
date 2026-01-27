<?php
/**
 * PAGE ÉDITION UTILISATEUR - DESIGN AMAWAL PRO
 * Version 2026 : Champs à dégradé, boutons animés et sécurité Superadmin.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
$engine = new RhymeEngine();
$db = $engine->getPDO();

// 1. SÉCURITÉ D'ACCÈS : Seul un Superadmin peut modifier les membres
if (!Auth::isLogged($db) || Auth::getRole() !== 'superadmin') { 
    header('Location: admin.php'); 
    exit; 
}

$admin = new AdminEngine($db);
$id = (int)($_GET['id'] ?? 0);
$user = $admin->getUserById($id);

if (!$user) {
    die("Erreur : Utilisateur introuvable dans la base de données.");
}

$message = "";
$isTargetSuperadmin = ($user['role'] === 'superadmin');

// 2. TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $nom      = trim($_POST['nom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : $user['is_active'];
    
    if (empty($username) || empty($prenom) || empty($nom) || empty($email)) {
        $message = "<p class='error-msg'>❌ Les champs marqués d'une étoile sont obligatoires.</p>";
    } else {
        // Protection du rôle Superadmin
        $roleToSave = $isTargetSuperadmin ? 'superadmin' : ($_POST['role'] ?? $user['role']);

        $data = [
            'username'  => $username,
            'prenom'    => $prenom,
            'nom'       => $nom,
            'email'     => $email,
            'role'      => $roleToSave,
            'is_active' => $isActive,
            'password'  => $_POST['password']
        ];

        if ($admin->updateUser($id, $data)) {
            $message = "<p class='success-msg'>✅ Fiche membre mise à jour avec succès.</p>";
            $user = $admin->getUserById($id);
            if ($id === Auth::getUserId()) { $_SESSION['username'] = $user['username']; }
        } else {
            $message = "<p class='error-msg'>❌ Erreur technique lors de l'enregistrement.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer Membre - Amawal Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f8fafc; color: #1e293b; }
        .edit-container { max-width: 900px; margin: 40px auto; }

        /* Header avec bouton animé */
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-dashboard-orange {
            background: #e67e22 !important; color: white !important;
            padding: 12px 24px; border-radius: 10px; text-decoration: none;
            font-weight: 800; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;
            transition: 0.3s; box-shadow: 0 4px 6px rgba(230, 126, 34, 0.2);
        }
        .btn-dashboard-orange:hover { 
            background: #d35400 !important; transform: translateX(3px);
            box-shadow: 0 6px 12px rgba(230, 126, 34, 0.3);
        }

        /* Carte Design Amawal */
        .add-card {
            background: white; padding: 45px; border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05); border-top: 6px solid #e67e22;
        }

        /* Style des champs */
        .form-group label {
            display: block; font-size: 0.75rem; font-weight: 800; color: #64748b;
            text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px;
        }
        .form-group input, .form-group select {
            width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;
            font-size: 1rem; background: linear-gradient(to bottom right, #ffffff, #faf9f7);
            transition: 0.3s; color: #1e293b; box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #e67e22; outline: none; background: #ffffff;
            box-shadow: inset 0 2px 4px rgba(230, 126, 34, 0.05);
        }
        .input-readonly { background: #f1f5f9 !important; color: #94a3b8 !important; cursor: not-allowed; }

        .admin-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .full-width { grid-column: span 2; }

        /* Bouton Enregistrer Vert */
        .submit-container { text-align: center; margin-top: 40px; }
        .btn-save-large {
            background: #27ae60 !important; color: white !important;
            padding: 18px 60px; border-radius: 15px; font-weight: 900;
            font-size: 1.2rem; border: none; cursor: pointer; transition: 0.3s;
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.25);
        }
        .btn-save-large:hover { transform: translateY(-4px); background: #219150 !important; }

        /* Modale floutée */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px);
            display: none; align-items: center; justify-content: center; z-index: 10000;
        }
        .modal-card { background: white; padding: 40px; border-radius: 24px; text-align: center; max-width: 400px; width: 90%; }
        .btn-modal { padding: 14px 25px; border-radius: 12px; font-weight: 800; cursor: pointer; border: none; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container edit-container">
        <header class="admin-header">
            <div>
                <h1 style="margin:0; font-weight: 900; font-size: 2.2rem; color: #1e293b;">Éditer membre</h1>
                <p style="color: #64748b; margin-top: 5px;">Modification du profil de <strong><?= htmlspecialchars($user['username']) ?></strong></p>
            </div>
            <a href="manage_users.php" class="btn-dashboard-orange">
                <span>👤</span> Liste des membres
            </a>
        </header>

        <?= $message ?>

        <div class="add-card">
            <form method="POST" id="editUserForm" class="admin-grid">
                <div class="form-group">
                    <label>Nom d'utilisateur *</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe (Laisser vide si inchangé)</label>
                    <input type="password" name="password" placeholder="••••••••">
                </div>
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
                    <label>Rôle du compte</label>
                    <?php if ($isTargetSuperadmin): ?>
                        <input type="text" class="input-readonly" value="Superadmin (Protégé)" readonly>
                        <input type="hidden" name="role" value="superadmin">
                    <?php else: ?>
                        <select name="role" required>
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Contributeur (User)</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Modérateur (Admin)</option>
                            <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Gestionnaire (Superadmin)</option>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="form-group full-width">
                    <label>Statut du compte</label>
                    <?php if ($id === Auth::getUserId()): ?>
                        <input type="text" class="input-readonly" value="Actif (Votre compte)" readonly>
                        <input type="hidden" name="is_active" value="1">
                    <?php else: ?>
                        <select name="is_active" required>
                            <option value="1" <?= $user['is_active'] == 1 ? 'selected' : '' ?>>✅ Compte Actif</option>
                            <option value="0" <?= $user['is_active'] == 0 ? 'selected' : '' ?>>🚫 Compte Suspendu</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="full-width submit-container">
                    <button type="button" class="btn-save-large" onclick="openModal()">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmModal" class="modal-overlay">
        <div class="modal-card">
            <div style="font-size: 4rem; margin-bottom: 20px;">🛡️</div>
            <h2 style="font-weight: 900;">Confirmer ?</h2>
            <p style="color: #64748b; margin-bottom: 30px;">Voulez-vous vraiment modifier les informations de ce membre ?</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button class="btn-modal" style="background:#f1f5f9; color:#64748b;" onclick="closeModal()">ANNULER</button>
                <button class="btn-modal" style="background:#27ae60; color:white;" onclick="submitForm()">VALIDER</button>
            </div>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('confirmModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('confirmModal').style.display = 'none'; }
        function submitForm() { document.getElementById('editUserForm').submit(); }
    </script>
</body>
</html>