<?php
/**
 * DASHBOARD ADMINISTRATEUR
 * Permet la gestion (CRUD) des mots du dictionnaire.
 */

require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

$engine = new RhymeEngine();

// Sécurité : Vérification de la session
if (!Auth::isLogged()) {
    header('Location: login.php');
    exit;
}

$admin = new AdminEngine($engine->getPDO());

// Gestion de la suppression sécurisée
if (isset($_GET['delete'])) {
    $admin->deleteWord((int)$_GET['delete']);
    header('Location: admin.php?msg=deleted'); 
    exit;
}

// Paramètres de filtrage pour le dashboard
$params = [
    'q'     => $_GET['q'] ?? '',
    'sort'  => $_GET['sort'] ?? 'date_ajout',
    'order' => $_GET['order'] ?? 'desc',
    'limit' => $_GET['limit'] ?? '50' // Plus de résultats par défaut en admin
];

// Récupération des mots via le moteur de recherche avancé
$words = $engine->searchAdvanced($params);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <div class="admin-header">
            <div>
                <h1>Gestion du Dictionnaire</h1>
                <p>Connecté en tant que : <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
            </div>
            <div class="admin-actions">
                <a href="add_word.php" class="btn-add">+ Nouveau Mot</a>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET" class="filter-form">
                <input type="text" name="q" placeholder="Rechercher (mot, sens, auteur...)" value="<?= htmlspecialchars($params['q']) ?>">
                
                <select name="sort">
                    <option value="date_ajout" <?= $params['sort'] == 'date_ajout' ? 'selected' : '' ?>>Date</option>
                    <option value="mot" <?= $params['sort'] == 'mot' ? 'selected' : '' ?>>Mot</option>
                    <option value="rime" <?= $params['sort'] == 'rime' ? 'selected' : '' ?>>Rime</option>
                    <option value="auteur" <?= $params['sort'] == 'auteur' ? 'selected' : '' ?>>Auteur</option>
                </select>

                <select name="limit">
                    <option value="20" <?= $params['limit'] == '20' ? 'selected' : '' ?>>20 lignes</option>
                    <option value="50" <?= $params['limit'] == '50' ? 'selected' : '' ?>>50 lignes</option>
                    <option value="all" <?= $params['limit'] == 'all' ? 'selected' : '' ?>>Tout</option>
                </select>

                <button type="submit">Appliquer</button>
            </form>
        </div>

        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Mot</th>
                        <th>Rime</th>
                        <th>Signification</th>
                        <th>Auteur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($words as $word): ?>
                    <tr>
                        <td class="bold"><?= htmlspecialchars($word['mot']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($word['rime']) ?></span></td>
                        <td title="<?= htmlspecialchars($word['signification']) ?>">
                            <?= htmlspecialchars(mb_strimwidth($word['signification'], 0, 40, "...")) ?>
                        </td>
                        <td><small><?= htmlspecialchars($word['auteur'] ?? 'Admin') ?></small></td>
                        <td class="actions">
                            <a href="edit_word.php?id=<?= $word['id'] ?>" class="link-edit">Modifier</a>
                            <a href="admin.php?delete=<?= $word['id'] ?>" class="link-delete" onclick="return confirm('Confirmer la suppression ?')">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>