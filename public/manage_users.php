<?php
/**
 * GESTION DES UTILISATEURS - VERSION MOTEUR DE RECHERCHE AVANCÉ
 * Design Premium 2026 : Filtres dynamiques, Modales floutées et UI contrastée.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
$engine = new RhymeEngine();
$db = $engine->getPDO();

// SÉCURITÉ : Accès réservé au Superadmin
if (!Auth::isLogged($db) || Auth::getRole() !== 'superadmin') {
    header('Location: admin.php?msg=denied');
    exit;
}

// --- PARAMÈTRES DE RECHERCHE ET FILTRES ---
$q      = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? 'username';
$order  = (isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC') ? 'DESC' : 'ASC';
$limit  = $_GET['limit'] ?? '10';

// --- LOGIQUE DE SUPPRESSION ---
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    if ($userId !== Auth::getUserId()) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        header('Location: manage_users.php?msg=deleted');
        exit;
    }
}

// --- LOGIQUE D'ACTIVATION / SUSPENSION ---
if (isset($_GET['toggle_active'])) {
    $userId = (int)$_GET['toggle_active'];
    $currentStatus = (int)$_GET['current'];
    $newStatus = ($currentStatus === 1) ? 0 : 1;
    if ($userId !== Auth::getUserId()) {
        $db->prepare("UPDATE users SET is_active = ?, updated_at = datetime('now') WHERE id = ?")
           ->execute([$newStatus, $userId]);
        header('Location: manage_users.php?msg=status_updated');
        exit;
    }
}

// --- CONSTRUCTION DE LA REQUÊTE SQL DYNAMIQUE ---
$queryStr = "SELECT u.*, (SELECT COUNT(*) FROM rimes WHERE auteur_id = u.id) as total_rimes FROM users u WHERE 1=1";
$sqlParams = [];

if (!empty($q)) {
    $queryStr .= " AND (username LIKE ? OR email LIKE ? OR nom LIKE ? OR prenom LIKE ?)";
    $search = "%$q%";
    $sqlParams = [$search, $search, $search, $search];
}

$allowedSort = ['username', 'nom', 'prenom', 'email', 'role', 'updated_at', 'total_rimes', 'is_active'];
if (!in_array($sort, $allowedSort)) { $sort = 'username'; }

$queryStr .= " ORDER BY $sort $order";

if ($limit !== 'all') {
    $queryStr .= " LIMIT " . (int)$limit;
}

$stmt = $db->prepare($queryStr);
$stmt->execute($sqlParams);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membres - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f8fafc; color: #1e293b; font-size: 0.95rem; margin: 0; padding-bottom: 50px; }
        
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; padding: 0 15px; flex-wrap: wrap; gap: 15px; }
        
        /* Bouton Tableau de bord (Animation ajoutée pour correspondre à profile.php) */
        .btn-dashboard-orange {
            background: #e67e22 !important; 
            color: white !important; 
            padding: 10px 22px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 800; 
            font-size: 0.9rem; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            box-shadow: 0 4px 6px rgba(230, 126, 34, 0.2);
            transition: 0.3s; /* Transition fluide */
        }
        .btn-dashboard-orange:hover { 
            background: #d35400 !important; 
            transform: translateX(3px); /* Animation de décalage identique à profile.php */
        }

        /* --- FILTRES --- */
        .filter-row { 
            display: grid; grid-template-columns: 2fr 1.2fr 1fr 0.8fr; gap: 15px; 
            background: white; padding: 20px; border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin: 0 15px 25px 15px; border: 1px solid #eef2f6;
        }
        .filter-row label { display: block; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .filter-row select, .filter-row input { 
            width: 100%; padding: 12px 15px; border: 2px solid #f1f5f9; border-radius: 10px;
            font-size: 0.95rem; color: #475569; background: #fff; box-sizing: border-box;
        }

        /* --- TABLEAU --- */
        .table-wrapper { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0; margin: 0 15px; }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f1f5f9; color: #64748b; text-align: left; padding: 15px; font-weight: 800; text-transform: uppercase; font-size: 0.7rem; }
        .styled-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #e2e8f0; }

        .role-badge { padding: 4px 10px; border-radius: 5px; font-weight: 900; font-size: 0.7rem; text-transform: uppercase; color: white !important; display: inline-block; }
        .role-superadmin { background: #7e22ce; }
        .role-admin { background: #2563eb; }
        .role-user { background: #d35400; }

        .status-toggle { cursor: pointer; padding: 6px 12px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; border: none; min-width: 90px; }
        .status-on { background: #dcfce7; color: #166534; }
        .status-off { background: #fee2e2; color: #991b1b; }

        .actions-cell { text-align: right; white-space: nowrap; }
        .btn-action { padding: 8px 14px; border-radius: 8px; font-weight: 800; text-decoration: none; font-size: 0.8rem; display: inline-block; transition: 0.2s; margin-bottom: 5px; cursor: pointer; border: none; }
        .btn-edit { background: #2563eb; color: white !important; }
        .btn-delete { background: #fee2e2; color: #e11d48; }
        .btn-me { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .alert-box { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin: 0 15px 15px 15px; font-weight: 700; }

        /* --- RESPONSIVE MOBILE --- */
        @media (max-width: 850px) {
            .filter-row { grid-template-columns: 1fr; padding: 15px; }
            .styled-table thead { display: none; }
            .styled-table tr { display: block; padding: 15px; border-bottom: 5px solid #f1f5f9; }
            .styled-table td { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #e2e8f0; text-align: right; width: 100% !important; box-sizing: border-box; }
            .styled-table td:last-child { border-bottom: none; flex-direction: column; align-items: stretch; gap: 10px; text-align: center; }
            .styled-table td::before { content: attr(data-label); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; color: #64748b; float: left; }
            .actions-cell { text-align: center; }
            .btn-action { width: 100%; box-sizing: border-box; padding: 12px; }
        }

        /* MODALE */
        #customModal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px); display: none; align-items: center; justify-content: center; z-index: 10000; }
        #customModal.active { display: flex; }
        .modal-card { background: white; padding: 30px; border-radius: 20px; max-width: 400px; width: 90%; text-align: center; }
        .btn-modal { padding: 14px 25px; border-radius: 12px; font-weight: 900; font-size: 0.9rem; cursor: pointer; border: none; width: 100%; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <header class="admin-header">
            <div>
                <h1 style="font-weight: 900; color: #1e293b; font-size: 2rem; margin:0;">Membres</h1>
                <p style="color: #64748b; font-size: 0.9rem;">Gestion communautaire</p>
            </div>
            <a href="admin.php" class="btn-dashboard-orange">
                <span>📊</span> <span class="hide-mobile">Tableau de bord</span>
            </a>
        </header>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-box">
                <span><?= ($_GET['msg'] === 'deleted') ? "✅ Utilisateur supprimé." : "✅ Statut mis à jour." ?></span>
                <span style="cursor:pointer;" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>

        <form method="GET" class="filter-row">
            <div>
                <label>Rechercher</label>
                <input type="text" name="q" placeholder="Pseudo, email..." value="<?= htmlspecialchars($q) ?>">
            </div>
            <div>
                <label>Trier par</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="username" <?= $sort == 'username' ? 'selected' : '' ?>>Pseudo</option>
                    <option value="email" <?= $sort == 'email' ? 'selected' : '' ?>>Email</option>
                    <option value="is_active" <?= $sort == 'is_active' ? 'selected' : '' ?>>État</option>
                    <option value="total_rimes" <?= $sort == 'total_rimes' ? 'selected' : '' ?>>Nombre de mots</option>
                </select>
            </div>
            <div>
                <label>Ordre</label>
                <select name="order" onchange="this.form.submit()">
                    <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>Croissant</option>
                    <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                </select>
            </div>
            <div>
                <label>Affichage</label>
                <select name="limit" onchange="this.form.submit()">
                    <option value="10" <?= $limit == '10' ? 'selected' : '' ?>>10</option>
                    <option value="50" <?= $limit == '50' ? 'selected' : '' ?>>50</option>
                    <option value="all" <?= $limit == 'all' ? 'selected' : '' ?>>Tout</option>
                </select>
            </div>
        </form>

        <div class="table-wrapper">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Identité</th>
                        <th>Rôle</th>
                        <th style="text-align: center;">État</th>
                        <th style="text-align: center;">Mots</th>
                        <th class="actions-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 30px;">Aucun membre trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): 
                            $isMe = ($u['id'] == Auth::getUserId());
                            $roleClass = 'role-' . strtolower($u['role']);
                        ?>
                        <tr>
                            <td data-label="Identité">
                                <div style="font-weight: 900;"><?= htmlspecialchars($u['username']) ?></div>
                                <div style="font-size: 0.75rem; color: #94a3b8;"><?= htmlspecialchars($u['email']) ?></div>
                            </td>
                            <td data-label="Rôle"><span class="role-badge <?= $roleClass ?>"><?= $u['role'] ?></span></td>
                            <td data-label="État" style="text-align: center;">
                                <?php if (!$isMe): ?>
                                    <button class="status-toggle <?= ($u['is_active'] == 1) ? 'status-on' : 'status-off' ?>"
                                            onclick="openModal('toggle', '<?= $u['id'] ?>', '<?= htmlspecialchars($u['username']) ?>', <?= $u['is_active'] ?>)">
                                        <?= ($u['is_active'] == 1) ? 'ACTIF' : 'SUSPENDU' ?>
                                    </button>
                                <?php else: ?>
                                    <span class="status-toggle status-on" style="opacity: 0.5;">MOI</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Mots" style="text-align: center; font-weight: 900;"><?= $u['total_rimes'] ?></td>
                            <td class="actions-cell">
                                <?php if (!$isMe): ?>
                                    <button class="btn-action btn-edit" onclick="openModal('edit', '<?= $u['id'] ?>', '<?= htmlspecialchars($u['username']) ?>')">Modifier</button>
                                    <button class="btn-action btn-delete" onclick="openModal('delete', '<?= $u['id'] ?>', '<?= htmlspecialchars($u['username']) ?>')">Supprimer</button>
                                <?php else: ?>
                                    <button class="btn-action btn-me" onclick="openModal('profile', '<?= $u['id'] ?>', '<?= htmlspecialchars($u['username']) ?>')">Mon Profil</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="customModal" onclick="closeModal()">
        <div class="modal-card" onclick="event.stopPropagation()">
            <div id="modalIcon" style="font-size: 4rem; margin-bottom: 20px;">⚠️</div>
            <h2 id="modalTitle" style="font-weight: 900; margin-bottom: 10px;">Confirmation</h2>
            <p id="modalText" style="color: #64748b; margin-bottom: 30px;"></p>
            <button id="confirmBtn" class="btn-modal" style="color:white; border:none;">CONFIRMER</button>
            <button class="btn-modal" style="background:#f1f5f9; color:#64748b; border:none;" onclick="closeModal()">ANNULER</button>
        </div>
    </div>

    <script>
        const modal = document.getElementById('customModal');
        const confirmBtn = document.getElementById('confirmBtn');

        function openModal(type, id, username, currentStatus = null) {
            modal.classList.add('active');
            if (type === 'delete') {
                document.getElementById('modalIcon').textContent = "🗑️";
                document.getElementById('modalTitle').textContent = "SUPPRIMER";
                document.getElementById('modalText').innerHTML = `Supprimer définitivement <strong>${username}</strong> ?`;
                confirmBtn.style.background = "#dc2626";
                confirmBtn.onclick = () => window.location.href = `manage_users.php?delete=${id}`;
            } else if (type === 'edit') {
                document.getElementById('modalIcon').textContent = "📝";
                document.getElementById('modalTitle').textContent = "MODIFIER";
                document.getElementById('modalText').innerHTML = `Éditer le compte de <strong>${username}</strong> ?`;
                confirmBtn.style.background = "#2563eb";
                confirmBtn.onclick = () => window.location.href = `edit_user.php?id=${id}`;
            } else if (type === 'profile') {
                document.getElementById('modalIcon').textContent = "👤";
                document.getElementById('modalTitle').textContent = "MON PROFIL";
                document.getElementById('modalText').innerHTML = `Voulez-vous modifier vos informations personnelles ?`;
                confirmBtn.style.background = "#2563eb";
                confirmBtn.onclick = () => window.location.href = `profile.php`;
            } else if (type === 'toggle') {
                document.getElementById('modalIcon').textContent = (currentStatus === 1) ? "🚫" : "✅";
                document.getElementById('modalTitle').textContent = "STATUT";
                document.getElementById('modalText').innerHTML = `Changer l'état pour <strong>${username}</strong> ?`;
                confirmBtn.style.background = (currentStatus === 1) ? "#ea580c" : "#16a34a";
                confirmBtn.onclick = () => window.location.href = `manage_users.php?toggle_active=${id}&current=${currentStatus}`;
            }
        }
        function closeModal() { modal.classList.remove('active'); }
    </script>
</body>
</html>