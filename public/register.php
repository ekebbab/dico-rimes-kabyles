<?php
/**
 * PAGE D'INSCRIPTION - DICO KABYLE
 * Crée un compte en attente de validation (is_active = 0)
 * Envoie un mail de notification au Superadmin.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

// Si déjà connecté, redirection
if (Auth::isLogged()) {
    header('Location: admin.php');
    exit;
}

$engine = new RhymeEngine();
$db = $engine->getPDO();
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $nom      = trim($_POST['nom'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $message = "<p class='error-msg'>❌ Veuillez remplir tous les champs obligatoires.</p>";
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $message = "<p class='error-msg'>❌ Ce nom d'utilisateur ou cet email est déjà utilisé.</p>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $db->prepare("
                INSERT INTO users (username, email, prenom, nom, password, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, 'user', 0, datetime('now'))
            ");

            if ($insert->execute([$username, $email, $prenom, $nom, $hashedPassword])) {
                $adminEmail = "votre-email@exemple.com"; // ⚠️ À MODIFIER
                $subject = "✨ Nouveau membre en attente : $username";
                $body = "Un nouvel utilisateur s'est inscrit sur le Dico Kabyle.\n\n" .
                        "Pseudo : $username\n" .
                        "Nom : $prenom $nom\n" .
                        "Email : $email\n\n" .
                        "Veuillez vous connecter pour activer son compte.";
                
                @mail($adminEmail, $subject, $body);

                $message = "<p class='success-msg'>✅ Inscription réussie ! Votre compte est en attente d'activation par un administrateur.</p>";
            } else {
                $message = "<p class='error-msg'>❌ Erreur technique lors de l'inscription.</p>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rejoindre la Team - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-card { max-width: 500px; margin: 50px auto; }
        .info-box { background: #e3f2fd; color: #0d47a1; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: left; }
        
        /* Style pour l'oeil */
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .toggle-eye { position: absolute; right: 12px; cursor: pointer; font-size: 1.2rem; user-select: none; color: var(--text-muted); }
        .toggle-eye:hover { color: var(--accent-color); }
        #password { padding-right: 45px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <div class="login-card shadow-box" style="background:white; padding:40px; border-radius:12px;">
            <h2 class="text-center">S'inscrire</h2>
            <p class="text-center text-muted">Rejoignez la communauté du dictionnaire</p>
            
            <div class="info-box">
                ℹ️ Votre compte sera examiné par un modérateur avant d'être activé. Vous recevrez un accès complet après validation.
            </div>

            <?= $message ?>

            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label>Nom d'utilisateur *</label>
                    <input type="text" name="username" required placeholder="Ex: Amazigh2026">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" placeholder="Votre prénom">
                    </div>
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" placeholder="Votre nom">
                    </div>
                </div>

                <div class="form-group">
                    <label>Adresse Email *</label>
                    <input type="email" name="email" required placeholder="email@exemple.com">
                </div>

                <div class="form-group">
                    <label>Mot de passe *</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" required placeholder="Choisissez un secret robuste">
                        <span class="toggle-eye" id="togglePassword">👁️</span>
                    </div>
                </div>

                <button type="submit" class="btn-submit btn-full">Envoyer ma demande d'adhésion</button>
            </form>
            
            <footer style="margin-top: 25px; text-align: center; border-top: 1px solid #eee; padding-top: 15px;">
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 10px;">Déjà membre ?</p>
                <a href="login.php" style="color: var(--primary-color); text-decoration: none; font-weight: bold; font-size: 0.95rem;">Se connecter à l'espace membre</a>
                
                <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: block; margin-top: 10px;">
                    ← Retour au dictionnaire
                </a>
            </footer>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePassword');

        toggleIcon.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>
</body>
</html>