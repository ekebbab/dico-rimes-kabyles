<?php
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/Auth.php';

$engine = new RhymeEngine(); // Contient déjà la connexion PDO
$auth = new Auth($engine->getPDO()); // On va devoir ajouter une méthode getPDO() dans RhymeEngine

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth->login($_POST['username'], $_POST['password'])) {
        header('Location: admin.php');
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include __DIR__ . '/../src/views/navbar.php'; ?>
    <div class="container">
        <form method="POST" style="max-width: 400px; margin: 100px auto; background: white; padding: 20px; border-radius: 8px;">
            <h2>Connexion Admin</h2>
            <?php if ($error): ?> <p style="color: red;"><?= $error ?></p> <?php endif; ?>
            <input type="text" name="username" placeholder="Nom d'utilisateur" required style="width: 100%; margin-bottom: 10px;">
            <input type="password" name="password" placeholder="Mot de passe" required style="width: 100%; margin-bottom: 20px;">
            <button type="submit" style="width: 100%;">Se connecter</button>
        </form>
    </div>
</body>
</html>