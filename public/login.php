<?php
/**
 * PAGE DE CONNEXION
 * Gère l'authentification et l'initialisation des droits (rôles).
 */
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/RhymeEngine.php';

Auth::init();

// Si déjà connecté, redirection directe
if (Auth::isLogged()) {
    header('Location: admin.php');
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $engine = new RhymeEngine();
        $db = $engine->getPDO();

        // Récupération de l'utilisateur
        $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe (haché)
        if ($user && password_verify($password, $user['password'])) {
            // Stockage des informations cruciales en session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role']; // 'user', 'admin' ou 'superadmin'

            header('Location: admin.php');
            exit;
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <main class="container login-wrapper">
        <section class="login-card">
            <header>
                <h2>Espace Membres</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Connectez-vous pour gérer vos rimes</p>
            </header>
            
            <?php if ($error): ?>
                <div class="error-box" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" placeholder="Pseudo" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit">Se connecter</button>
            </form>

            <footer style="margin-top: 20px; text-align: center;">
                <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                    ← Retour au dictionnaire
                </a>
            </footer>
        </section>
    </main>
</body>
</html>