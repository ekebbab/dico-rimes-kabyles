<?php
/**
 * ÉDITION COMPLÈTE D'UN UTILISATEUR (Admin side)
 * Stratégie de sécurité : Protection absolue du rang Superadmin via Auth::getRole()
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
$engine = new RhymeEngine();

// 1. SÉCURITÉ D'ACCÈS : Seul un Superadmin peut entrer
if (!Auth::isLogged($engine->getPDO()) || Auth::getRole() !== 'superadmin') { 
    header('Location: admin.php'); 
    exit; 
}

$admin = new AdminEngine($engine->getPDO());
$id = (int)($_GET['id'] ?? 0);
$user = $admin->getUserById($id);

if (!$user) {
    die("Utilisateur introuvable.");
}

$message = "";
$isTargetSuperadmin = ($user['role'] === 'superadmin');

// 2. TRAITEMENT DE LA SOUMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $nom      = trim($_POST['nom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    
    // Validation : Aucun champ obligatoire vide
    if (empty($username) || empty($prenom) || empty($nom) || empty($email)) {
        $message = "<p class='error-msg'>❌ Tous les champs (Username, Prénom, Nom, Email) sont obligatoires.</p>";
    } else {
        $roleToSave = $isTargetSuperadmin ? 'superadmin' : ($_POST['role'] ?? $user['role']);

        $data = [
            'username' => $username,
            'prenom'   => $prenom,
            'nom'      => $nom,
            'email'    => $email,
            'role'     => $roleToSave,
            'password' => $_POST['password'] // L'AdminEngine gère le hash si non vide
        ];

        if ($admin->updateUser($id, $data)) {
            $message = "<p class='success-msg'>✅ Fiche utilisateur mise à jour avec succès.</p>";
            $user = $admin->getUserById($id);
            if ($id === Auth::getUserId()) {
                $_SESSION['username'] = $user['username'];
            }
        } else {
            $message = "<p class='error-msg'>❌ Erreur lors de l'enregistrement.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Éditer Membre - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .password-container { position: relative; display: flex; align-items: center; }
        .toggle-password { position: absolute; right: 10px; cursor: pointer; font-size: 1.2rem; user-select: none; }
        .info-lock { font-size: 0.85rem; color: #d35400; margin-top: 8px; display: block; font-weight: bold; }
        .input-readonly { background-color: #f4f4f4 !important; color: #7f8c8d !important; cursor: not-allowed; border: 1px solid var(--border-color); font-weight: 600; }
        
        /* Modale */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); animation: fadeIn 0.3s ease; }
        .modal-card { background: white; width: 90%; max-width: 450px; padding: 30px; border-radius: 16px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 25px; }
        .btn-confirm-save { background: var(--primary-color); color: white; border-radius: 8px; padding: 12px 25px; border:none; cursor:pointer; font-weight:bold; }
        .btn-cancel { background: #dfe6e9; color: #2d3436; border-radius: 8px; padding: 12px 25px; border:none; cursor:pointer; font-weight:bold; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>
    <div class="container">
        <header class="admin-header">
            <h1>Éditer le membre : <?= htmlspecialchars($user['username']) ?></h1>
            <a href="manage_users.php" class="btn-primary">← Liste des membres</a>
        </header>

        <?= $message ?>

        <form method="POST" id="editUserForm" class="admin-form admin-grid">
            <div class="form-group">
                <label>Nom d'utilisateur *</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label>Nouveau mot de passe (laisser vide pour inchangé)</label>
                <div class="password-container">
                    <input type="password" name="password" id="pass_field" placeholder="Secret...">
                    <span class="toggle-password" id="toggle_eye">👁️</span>
                </div>
            </div>
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Prénom *</label>
                <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Rôle du compte</label>
                <?php if ($isTargetSuperadmin): ?>
                    <input type="text" class="input-readonly" value="Superadmin (Accès Racine)" readonly>
                    <span class="info-lock">🔒 Rôle protégé (Super Admin).</span>
                <?php else: ?>
                    <select name="role" required>
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User (Contributeur)</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin (Modérateur)</option>
                        <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin (Gestionnaire)</option>
                    </select>
                <?php endif; ?>
            </div>
            <div class="full-width">
                <button type="button" class="btn-submit btn-full" onclick="openModal()">Sauvegarder les modifications</button>
            </div>
        </form>
    </div>

    <div id="confirmModal" class="modal-overlay">
        <div class="modal-card">
            <span style="font-size: 3.5rem;">👤</span>
            <h2>Confirmer l'édition ?</h2>
            <p>Voulez-vous enregistrer les changements pour cet utilisateur ?</p>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeModal()">Annuler</button>
                <button class="btn-confirm-save" onclick="submitForm()">Confirmer</button>
            </div>
        </div>
    </div>

    <script>
        const passField = document.getElementById('pass_field');
        const toggleEye = document.getElementById('toggle_eye');
        toggleEye.addEventListener('click', () => {
            const isPassword = passField.type === "password";
            passField.type = isPassword ? "text" : "password";
            toggleEye.textContent = isPassword ? "🙈" : "👁️";
        });

        function openModal() { document.getElementById('confirmModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('confirmModal').style.display = 'none'; }
        function submitForm() { document.getElementById('editUserForm').submit(); }
        window.onclick = (e) => { if(e.target.className === 'modal-overlay') closeModal(); }
    </script>
</body>
</html>