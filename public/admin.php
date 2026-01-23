<?php
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

$engine = new RhymeEngine();
$auth = new Auth($engine->getPDO());

// Sécurité : si pas connecté, oust !
if (!Auth::isLogged()) {
    header('Location: login.php');
    exit;
}

$admin = new AdminEngine($engine->getPDO());

// Gestion de la suppression (si on clique sur supprimer)
if (isset($_GET['delete'])) {
    $admin->deleteWord($_GET['delete']);
    header('Location: admin.php'); // Rafraîchir pour voir la liste à jour
    exit;
}

$words = $admin->getAllWords(100); // On récupère les 100 derniers mots
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .btn-add { background: #27ae60; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; }
        .btn-del { color: #e74c3c; text-decoration: none; font-weight: bold; }
        .header-admin { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../src/views/navbar.php'; ?>
    <div class="container">
        <div class="header-admin">
            <h1>Gestion du Dictionnaire</h1>
            <a href="logout.php" style="color: #7f8c8d;">Déconnexion (<?= $_SESSION['username'] ?>)</a>
        </div>

        <a href="add_word.php" class="btn-add">+ Ajouter un nouveau mot</a>

        <table>
            <thead>
                <tr>
                    <th>Mot</th>
                    <th>Rime</th>
                    <th>Signification</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($words as $word): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($word['mot']) ?></strong></td>
                    <td><?= htmlspecialchars($word['rime']) ?></td>
                    <td><?= htmlspecialchars(substr($word['signification'], 0, 50)) ?>...</td>
                    <td>
                        <a href="edit_word.php?id=<?= $word['id'] ?>" style="color: #3498db;">Modifier</a> | 
                        <a href="admin.php?delete=<?= $word['id'] ?>" class="btn-del" onclick="return confirm('Supprimer ce mot ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>