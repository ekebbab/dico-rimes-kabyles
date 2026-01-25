<?php
/**
 * DASHBOARD ADMINISTRATEUR
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

$engine = new RhymeEngine();

if (!Auth::isLogged()) {
    header('Location: login.php');
    exit;
}

$admin = new AdminEngine($engine->getPDO());

if (isset($_GET['delete'])) {
    $admin->deleteWord((int)$_GET['delete']);
    header('Location: admin.php?msg=deleted'); 
    exit;
}

$params = [
    'q'     => $_GET['q'] ?? '',
    'sort'  => $_GET['sort'] ?? 'created_at',
    'order' => $_GET['order'] ?? 'desc',
    'limit' => $_GET['limit'] ?? '50'
];

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
                <p>Connecté : <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
            </div>
            <div class="admin-actions">
                <a href="add_word.php" class="btn-add">+ Nouveau Mot</a>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET" class="filter-form" id="filterForm">
                <input type="text" name="q" placeholder="Rechercher..." value="<?= htmlspecialchars($params['q']) ?>">
                
                <select name="sort">
                    <option value="created_at" <?= $params['sort'] == 'created_at' ? 'selected' : '' ?>>Date</option>
                    <option value="mot" <?= $params['sort'] == 'mot' ? 'selected' : '' ?>>Mot</option>
                    <option value="rime" <?= $params['sort'] == 'rime' ? 'selected' : '' ?>>Rime</option>
                </select>

                <select name="order">
                    <option value="desc" <?= $params['order'] == 'desc' ? 'selected' : '' ?>>Décroissant</option>
                    <option value="asc" <?= $params['order'] == 'asc' ? 'selected' : '' ?>>Croissant</option>
                </select>

                <select name="limit">
                    <option value="10" <?= $params['limit'] == '10' ? 'selected' : '' ?>>10 lignes</option>
                    <option value="20" <?= $params['limit'] == '20' ? 'selected' : '' ?>>20 lignes</option>
                    <option value="50" <?= $params['limit'] == '50' ? 'selected' : '' ?>>50 lignes</option>
                    <option value="100" <?= $params['limit'] == '100' ? 'selected' : '' ?>>100 lignes</option>
                    <option value="500" <?= $params['limit'] == '500' ? 'selected' : '' ?>>500 lignes</option>
                    <option value="all" <?= $params['limit'] == 'all' ? 'selected' : '' ?>>Tout</option>
                </select>
                <button type="submit" style="display:none;">Appliquer</button>
            </form>
        </div>

        <div class="table-container">
            <table class="styled-table">
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
                        <td class="bold"><?= htmlspecialchars($word['mot']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($word['rime']) ?></span></td>
                        <td><?= htmlspecialchars(mb_strimwidth($word['signification'], 0, 50, "...")) ?></td>
                        <td class="actions">
                            <a href="edit_word.php?id=<?= $word['id'] ?>" class="link-edit">Modifier</a>
                            <a href="admin.php?delete=<?= $word['id'] ?>" class="link-delete" onclick="return confirm('Supprimer ?')">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto-submit du formulaire lors du changement des selects
        document.querySelectorAll('#filterForm select').forEach(select => {
            select.addEventListener('change', () => {
                document.getElementById('filterForm').submit();
            });
        });
    </script>
</body>
</html>