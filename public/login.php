<?php
require_once __DIR__ . '/../src/Auth.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (Auth::login($username, $password)) {
        header('Location: admin.php');
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="ber">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Dico Kabyle Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <main class="container login-wrapper">
        <section class="login-card">
            <header>
                <h2>Espace Admin</h2>
            </header>
            
            <?php if ($error): ?>
                <div class="error-box" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Utilisateur</label>
                    <input type="text" id="username" name="username" placeholder="Votre pseudo..." required autofocus>
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