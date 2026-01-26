<?php
/**
 * GESTION DES UTILISATEURS (Superadmin uniquement)
 * Avec colonne Activation dédiée et Modale de confirmation
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

if (!Auth::isLogged() || Auth::getRole() !== 'superadmin') {
    header('Location: admin.php');
    exit;
}

$engine = new RhymeEngine();
$db = $engine->getPDO();

// --- LOGIQUE DE SUPPRESSION ---
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    // On ne peut pas se supprimer soi-même
    if ($userId !== Auth::getUserId()) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        header('Location: manage_users.php?msg=deleted');
        exit;
    }
}

// --- LOGIQUE D'ACTIVATION / DÉSACTIVATION ---
if (isset($_GET['toggle_active'])) {
    $userId = (int)$_GET['toggle_active'];
    $currentStatus = (int)$_GET['current'];
    $newStatus = ($currentStatus === 1) ? 0 : 1;

    // On ne peut pas se désactiver soi-même pour éviter de perdre l'accès admin
    if ($userId !== Auth::getUserId()) {
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        header('Location: manage_users.php?msg=status_updated');
        exit;
    }
}

// Récupération des utilisateurs
$users = $db->query("SELECT id, username, email, role, prenom, nom, is_active FROM users ORDER BY role DESC, username ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membres - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* --- STYLE DES STATUTS --- */
        .status-btn {
            text-decoration: none;
            font-size: 1.2rem;
            vertical-align: middle;
            display: inline-block;
            transition: transform 0.2s;
            border: none;
            background: none;
            cursor: pointer;
        }
        .status-btn:hover { transform: scale(1.2); }
        .status-active { color: #27ae60; } /* Vert Nike */
        .status-inactive { color: #e74c3c; } /* Rouge Croix */

        /* --- STYLE DE LA MODALE MODERNE --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7); display: none; align-items: center; 
            justify-content: center; z-index: 1000; backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }
        .modal-card {
            background: white; width: 90%; max-width: 450px; padding: 30px; 
            border-radius: 16px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            transform: translateY(-20px); transition: 0.3s ease;
        }
        .modal-card h2 { margin-top: 0; color: #2c3e50; }
        .modal-card p { color: #636e72; line-height: 1.6; margin: 20px 0; }
        .modal-icon { font-size: 3.5rem; margin-bottom: 15px; display: block; }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 25px; }
        .btn-modal { padding: 12px 25px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 1rem; }
        .btn-confirm { background: #d63031; color: white; }
        .btn-confirm.edit { background: var(--primary-color); }
        .btn-confirm.activate { background: #27ae60; }
        .btn-cancel { background: #dfe6e9; color: #2d3436; }
        .btn-modal:hover { opacity: 0.85; transform: translateY(-2px); }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <div class="admin-header">
            <div>
                <h1>Membres du dictionnaire</h1>
                <p>Gestionnaire de comptes sécurisé</p>
            </div>
            <a href="admin.php" class="btn-primary">← Dashboard</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'deleted'): ?>
                <p class="success-msg">✅ Utilisateur supprimé avec succès.</p>
            <?php elseif ($_GET['msg'] === 'status_updated'): ?>
                <p class="success-msg">✅ Le statut du compte a été mis à jour.</p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th style="text-align: center;">Activation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['username']) ?></strong><br>
                            <small style="color: var(--text-muted);"><?= htmlspecialchars(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?></small>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($u['id'] != Auth::getUserId()): ?>
                                <a href="#" class="status-btn" 
                                   onclick="openModal('toggle', '<?= $u['id'] ?>', '<?= htmlspecialchars($u['username']) ?>', <?= $u['is_active'] ?>)">
                                    <?= ($u['is_active'] == 1) ? '<span class="status-active" title="Compte Actif">✔</span>' : '<span class="status-inactive" title="Compte Inactif">✘</span>' ?>
                                </a>
                            <?php else: ?>
                                <span class="status-active" title="C'est vous">✔</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="#" class="link-edit" onclick="openModal('edit', '<?= $u['id'] ?>', '<?= htmlspecialchars($u['username']) ?>')">Modifier</a>
                            
                            <?php if ($u['id'] != Auth::getUserId()): ?>
                                <a href="#" class="link-delete" onclick="openModal('delete', '<?= $u['id'] ?>', '<?= htmlspecialchars($u['username']) ?>')">Supprimer</a>
                            <?php else: ?>
                                <small class="text-muted" style="font-style: italic;">(C'est vous)</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="customModal" class="modal-overlay">
        <div class="modal-card">
            <span id="modalIcon" class="modal-icon">⚠️</span>
            <h2 id="modalTitle">Confirmation</h2>
            <p id="modalText">Voulez-vous vraiment effectuer cette action ?</p>
            
            <div class="modal-buttons">
                <button class="btn-modal btn-cancel" onclick="closeModal()">Annuler</button>
                <button id="confirmBtn" class="btn-modal btn-confirm">Confirmer</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('customModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalText = document.getElementById('modalText');
        const modalIcon = document.getElementById('modalIcon');
        const confirmBtn = document.getElementById('confirmBtn');

        function openModal(type, id, username, currentStatus = null) {
            modal.style.display = 'flex';
            
            if (type === 'delete') {
                modalIcon.textContent = "🗑️";
                modalTitle.textContent = "Suppression Définitive";
                modalText.innerHTML = `Êtes-vous sûr de vouloir supprimer <strong>${username}</strong> ?<br><br>Cette opération est <strong>irréversible</strong>.`;
                confirmBtn.textContent = "Supprimer l'utilisateur";
                confirmBtn.className = "btn-modal btn-confirm";
                confirmBtn.onclick = () => window.location.href = `manage_users.php?delete=${id}`;
            } 
            else if (type === 'edit') {
                modalIcon.textContent = "📝";
                modalTitle.textContent = "Modifier le Profil";
                modalText.innerHTML = `Voulez-vous éditer le compte de <strong>${username}</strong> ?`;
                confirmBtn.textContent = "Aller à l'édition";
                confirmBtn.className = "btn-modal btn-confirm edit";
                confirmBtn.onclick = () => window.location.href = `edit_user.php?id=${id}`;
            }
            else if (type === 'toggle') {
                const action = (currentStatus === 1) ? "DÉSACTIVER" : "ACTIVER";
                modalIcon.textContent = (currentStatus === 1) ? "🚫" : "✅";
                modalTitle.textContent = `${action} le compte`;
                modalText.innerHTML = `Voulez-vous ${action.toLowerCase()} l'accès au dictionnaire pour <strong>${username}</strong> ?<br><br>Un compte inactif ne peut plus se connecter au site.`;
                confirmBtn.textContent = `Confirmer l'${action.toLowerCase()}`;
                confirmBtn.className = (currentStatus === 1) ? "btn-modal btn-confirm" : "btn-modal btn-confirm activate";
                confirmBtn.onclick = () => window.location.href = `manage_users.php?toggle_active=${id}&current=${currentStatus}`;
            }
        }

        function closeModal() { modal.style.display = 'none'; }
        window.onclick = (event) => { if (event.target == modal) closeModal(); }
    </script>
</body>
</html>