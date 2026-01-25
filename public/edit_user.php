<?php
/**
 * ÉDITION COMPLÈTE D'UN UTILISATEUR (Admin side)
 * Stratégie de sécurité : Protection absolue du rang Superadmin via Auth::getRole()
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

// 1. SÉCURITÉ D'ACCÈS : Seul un Superadmin peut entrer sur cette page
if (Auth::getRole() !== 'superadmin') { 
    header('Location: admin.php'); 
    exit; 
}

$engine = new RhymeEngine();
$admin = new AdminEngine($engine->getPDO());

$id = (int)($_GET['id'] ?? 0);
$user = $admin->getUserById($id);

if (!$user) {
    die("Utilisateur introuvable.");
}

$message = "";

/**
 * NOUVELLE STRATÉGIE DE RECONNAISSANCE
 * On vérifie si l'utilisateur que l'on édite est UN superadmin.
 * Dans votre logique, un superadmin ne doit jamais pouvoir rétrograder un autre superadmin 
 * (ou lui-même) depuis cette interface pour éviter de casser les accès racine.
 */
$isTargetSuperadmin = ($user['role'] === 'superadmin');

// 2. TRAITEMENT DE LA SOUMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Si la cible était déjà superadmin, on FORCE le maintien du rôle 'superadmin'
    // peu importe ce qui a été envoyé par le formulaire.
    $roleToSave = $isTargetSuperadmin ? 'superadmin' : ($_POST['role'] ?? $user['role']);

    $data = [
        'username' => trim($_POST['username']),
        'prenom'   => trim($_POST['prenom']),
        'nom'      => trim($_POST['nom']),
        'email'    => trim($_POST['email']),
        'role'     => $roleToSave,
        'password' => $_POST['password'] 
    ];

    if ($admin->updateUser($id, $data)) {
        $message = "<p class='success-msg'>✅ Fiche utilisateur mise à jour avec succès.</p>";
        
        // Actualisation des données locales
        $user = $admin->getUserById($id);
        
        // Si on vient de modifier son propre compte, on met à jour la session
        if ($id === Auth::getUserId()) {
            $_SESSION['username'] = $user['username'];
        }
    } else {
        $message = "<p class='error-msg'>❌ Erreur lors de l'enregistrement.</p>";
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
        .toggle-password { 
            position: absolute; right: 10px; cursor: pointer; 
            font-size: 1.2rem; user-select: none; 
        }
        .info-lock { 
            font-size: 0.85rem; 
            color: #d35400; 
            margin-top: 8px; 
            display: block; 
            font-weight: bold;
        }
        /* Style du champ de texte qui remplace le select */
        .input-readonly {
            background-color: #f4f4f4 !important;
            color: #7f8c8d !important;
            cursor: not-allowed;
            border: 1px solid var(--border-color);
            font-weight: 600;
        }
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

        <form method="POST" class="admin-form admin-grid" onsubmit="return confirm('Confirmer les modifications sur ce compte ?')">
            
            <div class="form-group">
                <label>Nom d'utilisateur</label>
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
                <label>Nom</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
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
                <button type="submit" class="btn-submit btn-full">Sauvegarder les modifications</button>
            </div>
        </form>
    </div>

    <script>
        // Gestion de l'affichage du mot de passe
        const passField = document.getElementById('pass_field');
        const toggleEye = document.getElementById('toggle_eye');

        toggleEye.addEventListener('click', function() {
            const isPassword = passField.type === "password";
            passField.type = isPassword ? "text" : "password";
            this.textContent = isPassword ? "🙈" : "👁️";
        });
    </script>
</body>
</html>