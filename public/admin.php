<?php
/**
 * DASHBOARD ADMINISTRATEUR - VERSION AVANCÉE
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

if (!Auth::isLogged()) {
    header('Location: login.php');
    exit;
}

$engine = new RhymeEngine();
$admin = new AdminEngine($engine->getPDO());

// Gestion de la suppression sécurisée
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // On récupère les infos de la rime avant de supprimer pour vérifier les droits
    $stmt = $engine->getPDO()->prepare("
        SELECT r.auteur_id, u.role as author_role 
        FROM rimes r 
        LEFT JOIN users u ON r.auteur_id = u.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $info = $stmt->fetch();

    if ($info && Auth::canManage($info['auteur_id'], $info['author_role'])) {
        $admin->deleteWord($id);
        header('Location: admin.php?msg=deleted');
    } else {
        header('Location: admin.php?msg=denied');
    }
    exit;
}

$params = [
    'q'     => $_GET['q'] ?? '',
    'sort'  => $_GET['sort'] ?? 'created_at',
    'order' => $_GET['order'] ?? 'desc',
    'limit' => $_GET['limit'] ?? '50'
];

// REQUÊTE AVEC JOINTURE pour avoir l'auteur et son rôle
$sql = "SELECT r.*, u.username as auteur_nom, u.role as author_role 
        FROM rimes r 
        LEFT JOIN users u ON r.auteur_id = u.id 
        WHERE 1=1";

$binds = [];
if (!empty($params['q'])) {
    $sql .= " AND (r.mot LIKE :q OR r.rime LIKE :q OR r.signification LIKE :q)";
    $binds['q'] = '%' . $params['q'] . '%';
}

$allowedSort = ['mot', 'rime', 'created_at'];
$sort = in_array($params['sort'], $allowedSort) ? "r.".$params['sort'] : "r.created_at";
$order = (isset($params['order']) && strtolower($params['order']) === 'asc') ? 'ASC' : 'DESC';
$sql .= " ORDER BY $sort $order";

if ($params['limit'] !== 'all') {
    $sql .= " LIMIT " . (int)$params['limit'];
}

$stmt = $engine->getPDO()->prepare($sql);
$stmt->execute($binds);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <div class="admin-header">
            <div>
                <h1>Gestion des Rimes</h1>
                <p>Rôle : <span class="badge"><?= ucfirst(Auth::getRole()) ?></span></p>
            </div>
            <div class="admin-actions">
                <a href="add_word.php" class="btn-add">+ Nouveau Mot</a>
                <?php if(Auth::getRole() === 'superadmin'): ?>
                    <a href="manage_users.php" class="btn-primary">Membres</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET" class="filter-form" id="autoFilterForm">
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
                <button type="submit" style="display:none;">Appliquer</button>
            </form>
        </div>

        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Mot</th>
                        <th>Rime</th>
                        <th>Auteur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($words as $word): ?>
                    <tr>
                        <td class="bold"><?= htmlspecialchars($word['mot']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($word['rime']) ?></span></td>
                        <td><small><?= htmlspecialchars($word['auteur_nom'] ?? 'Inconnu') ?></small></td>
                        <td class="actions">
                            <?php if (Auth::canManage($word['auteur_id'], $word['author_role'])): ?>
                                <a href="edit_word.php?id=<?= $word['id'] ?>" class="link-edit">Modifier</a>
                                <a href="admin.php?delete=<?= $word['id'] ?>" class="link-delete" onclick="return confirm('Supprimer ?')">Supprimer</a>
                            <?php else: ?>
                                <span class="text-muted">Lecture seule</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.querySelectorAll('#autoFilterForm select').forEach(select => {
            select.addEventListener('change', () => document.getElementById('autoFilterForm').submit());
        });
    </script>
</body>
</html>