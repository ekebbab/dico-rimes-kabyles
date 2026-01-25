<?php
/**
 * GESTION DES UTILISATEURS (Réservé au Superadmin)
 * Permet de modifier les rôles et de supprimer des membres.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

// SÉCURITÉ : Seul le superadmin peut accéder à cette page
if (!Auth::isLogged() || Auth::getRole() !== 'superadmin') {
    header('Location: admin.php');
    exit;
}

$engine = new RhymeEngine();
$db = $engine->getPDO();

// 1. GESTION DU CHANGEMENT DE RÔLE
if (isset($_POST['update_role'])) {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    
    // Empêcher le superadmin de se rétrograder lui-même par erreur s'il est seul
    if ($userId !== Auth::getUserId()) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        header('Location: manage_users.php?msg=role_updated');
        exit;
    }
}

// 2. GESTION DE LA SUPPRESSION
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    // On ne peut pas se supprimer soi-même ici
    if ($userId !== Auth::getUserId()) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        header('Location: manage_users.php?msg=deleted');
        exit;
    }
}

// 3. RÉCUPÉRATION DE TOUS LES UTILISATEURS
$users = $db->query("SELECT id, username, email, role, prenom, nom FROM users ORDER BY role DESC, username ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Membres - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .role-select { padding: 5px; border-radius: 4px; border: 1px solid var(--border-color); }
        .btn-update {
            background: var(--primary-color);
            color: white; border: none; padding: 6px 12px;
            border-radius: 4px; cursor: pointer; font-size: 0.8rem;
        }
        .badge-superadmin { background: #8e44ad !important; color: white; }
        .badge-admin { background: #2980b9 !important; color: white; }
        .badge-user { background: #95a5a6 !important; color: white; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <div class="admin-header">
            <div>
                <h1>Gestion des Utilisateurs</h1>
                <p>Niveau d'accès : <strong>Superadmin</strong></p>
            </div>
            <a href="admin.php" class="btn-primary">← Dashboard</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="error-box" style="background: var(--success-color); color: white; border: none;">
                <?php
                    if($_GET['msg'] === 'deleted') echo "Utilisateur supprimé.";
                    if($_GET['msg'] === 'role_updated') echo "Rôle mis à jour avec succès.";
                ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Changer le Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['username']) ?></strong><br>
                            <small><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="badge badge-<?= $u['role'] ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['id'] != Auth::getUserId()): ?>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="role-select">
                                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="superadmin" <?= $u['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn-update">OK</button>
                                </form>
                            <?php else: ?>
                                <small class="text-muted">Vous (Superadmin)</small>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?php if ($u['id'] != Auth::getUserId()): ?>
                                <a href="manage_users.php?delete=<?= $u['id'] ?>" class="link-delete" onclick="return confirm('Bannir cet utilisateur ?')">Supprimer</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>