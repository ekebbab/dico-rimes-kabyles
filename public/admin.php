<?php
/**
 * DASHBOARD ADMINISTRATEUR - AMAWAL PRO 2026
 * Système de gestion de dictionnaire avec tris multicritères et pagination.
 */
require_once __DIR__ . '/../src/Auth.php';
Auth::init();

require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';

$engine = new RhymeEngine();
$db = $engine->getPDO();

// SÉCURITÉ : Redirection si non connecté
if (!Auth::isLogged($db)) { 
    header('Location: login.php'); 
    exit; 
}

$admin = new AdminEngine($db);
$role = Auth::getRole();
$userId = Auth::getUserId();

// --- LOGIQUE DE SUPPRESSION ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Vérification des droits avant suppression (Auteur ou Superadmin)
    $stmt = $db->prepare("SELECT auteur_id, (SELECT role FROM users WHERE id=auteur_id) as author_role FROM rimes WHERE id = ?");
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

// --- RÉCUPÉRATION ET VALIDATION DES PARAMÈTRES ---
$params = [
    'q'      => $_GET['q'] ?? '',
    'sort'   => $_GET['sort'] ?? 'updated_at',
    'order'  => (isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC') ? 'ASC' : 'DESC',
    'limit'  => $_GET['limit'] ?? '50'
];

/**
 * Traduction des clés de tri pour SQL (protection contre injection via liste blanche)
 */
$sortMapping = [
    'mot'           => 'mot',
    'signification' => 'signification',
    'lettre'        => 'lettre',
    'rime'          => 'rime',
    'classe'        => 'classe_grammaticale',
    'genre'         => 'genre',
    'nombre'        => 'nombre',
    'exemple'       => 'exemple',
    'date'          => 'updated_at'
];

// Si le tri demandé n'est pas dans la liste, on utilise la date par défaut
$actualSort = $sortMapping[$params['sort']] ?? 'updated_at';

// Exécution de la recherche avec les nouveaux paramètres
$words = $engine->searchAdvanced([
    'q'     => $params['q'],
    'sort'  => $actualSort,
    'order' => $params['order'],
    'limit' => $params['limit']
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Amawal Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f8fafc; color: #1e293b; font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding-bottom: 50px; }

        /* --- EN-TÊTE --- */
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; padding: 0 15px; flex-wrap: wrap; gap: 15px; }
        .header-btns { display: flex; gap: 15px; }

        .btn-new { background: #27ae60 !important; color: white !important; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 900; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; box-shadow: 0 6px 15px rgba(39, 174, 96, 0.2); }
        .btn-new:hover { background: #219150 !important; transform: translateY(-3px); }

        .btn-members { background: #8e44ad !important; color: white !important; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 900; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; box-shadow: 0 6px 15px rgba(142, 68, 173, 0.2); }
        .btn-members:hover { background: #7d3c98 !important; transform: translateY(-3px); }

        /* --- BARRE DE FILTRES --- */
        .filter-row { 
            display: grid; grid-template-columns: 1.5fr 1fr 0.8fr 0.8fr; gap: 15px; 
            background: white; padding: 25px; border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin: 0 15px 30px 15px; border: 1px solid #eef2f6;
        }
        .filter-row label { display: block; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .filter-row select, .filter-row input { 
            width: 100%; padding: 12px 15px; border: 2px solid #f1f5f9; border-radius: 10px;
            font-size: 0.95rem; color: #475569; background: linear-gradient(to bottom right, #ffffff, #faf9f7);
            transition: 0.3s; box-sizing: border-box;
        }
        .filter-row select:focus, .filter-row input:focus { border-color: #e67e22; outline: none; background: #fff; box-shadow: inset 0 2px 4px rgba(230, 126, 34, 0.05); }

        /* --- TABLEAU --- */
        .table-wrapper { background: white; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.04); overflow: hidden; border: 1px solid #f1f5f9; margin: 0 15px; }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f1f5f9; color: #94a3b8; text-align: left; padding: 18px; font-weight: 800; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; }
        .styled-table td { padding: 18px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        .styled-table tr:hover { background-color: #fdfdfd; }

        /* --- ACTIONS --- */
        .actions-cell { text-align: right; white-space: nowrap; }
        .btn-action { padding: 10px 18px; border-radius: 10px; font-weight: 800; text-decoration: none; font-size: 0.8rem; display: inline-block; transition: 0.2s; cursor: pointer; border: none; }
        .btn-edit { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .btn-edit:hover { background: #0369a1; color: white !important; transform: scale(1.05); }
        .btn-delete { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; margin-left: 8px; }
        .btn-delete:hover { background: #b91c1c; color: white !important; transform: scale(1.05); }

        /* --- MODALE --- */
        #customModal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px); display: none; align-items: center; justify-content: center; z-index: 10000; }
        #customModal.active { display: flex; }
        .modal-card { background: white; padding: 45px; border-radius: 30px; max-width: 450px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .btn-modal { padding: 16px 32px; border-radius: 15px; font-weight: 900; font-size: 1rem; cursor: pointer; transition: 0.3s; border: none; width: 100%; margin-top: 10px; }
        .btn-modal-cancel { background: #f1f5f9; color: #475569; }

        .alert-box { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin: 0 15px 15px 15px; font-weight: 700; }
        
        @media (max-width: 850px) {
            .filter-row { grid-template-columns: 1fr; }
            .styled-table thead { display: none; }
            .styled-table tr { display: block; border-bottom: 5px solid #f1f5f9; padding: 15px; }
            .styled-table td { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #eee; text-align: right; }
            .styled-table td::before { content: attr(data-label); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; color: #94a3b8; }
            .actions-cell { text-align: center; display: block; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container" style="max-width: 1200px; margin: 0 auto;">
        <header class="admin-header">
            <div>
                <h1 style="font-weight: 900; color: #1e293b; font-size: 2.2rem; margin:0;">Dashboard</h1>
                <p style="color: #64748b; font-size: 0.9rem;">Gestion du dictionnaire • <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
            </div>
            <div class="header-btns">
                <?php if ($role === 'superadmin'): ?>
                    <a href="manage_users.php" class="btn-members"><span>👥</span> Membres</a>
                <?php endif; ?>
                <a href="add_word.php" class="btn-new"><span>➕</span> Nouveau Mot</a>
            </div>
        </header>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-box">
                <span><?= ($_GET['msg'] === 'deleted') ? "✅ Mot supprimé." : "✅ Action confirmée." ?></span>
                <span style="cursor:pointer;" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>

        <form method="GET" class="filter-row">
            <div>
                <label>Recherche rapide</label>
                <input type="text" name="q" placeholder="Mot, sens..." value="<?= htmlspecialchars($params['q']) ?>">
            </div>
            <div>
                <label>Trier par</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="date" <?= $params['sort'] == 'date' ? 'selected' : '' ?>>📅 Date (Dernière modif)</option>
                    <option value="mot" <?= $params['sort'] == 'mot' ? 'selected' : '' ?>>🔤 Mot (Kabyle)</option>
                    <option value="signification" <?= $params['sort'] == 'signification' ? 'selected' : '' ?>>📖 Signification</option>
                    <option value="lettre" <?= $params['sort'] == 'lettre' ? 'selected' : '' ?>>📍 Lettre Pivot</option>
                    <option value="rime" <?= $params['sort'] == 'rime' ? 'selected' : '' ?>>🎼 Rime (Terminaison)</option>
                    <option value="classe" <?= $params['sort'] == 'classe' ? 'selected' : '' ?>>🏷️ Classe grammaticale</option>
                    <option value="genre" <?= $params['sort'] == 'genre' ? 'selected' : '' ?>>🚻 Genre</option>
                    <option value="nombre" <?= $params['sort'] == 'nombre' ? 'selected' : '' ?>>🔢 Nombre</option>
                    <option value="exemple" <?= $params['sort'] == 'exemple' ? 'selected' : '' ?>>📝 Exemple</option>
                </select>
            </div>
            <div>
                <label>Ordre</label>
                <select name="order" onchange="this.form.submit()">
                    <option value="DESC" <?= $params['order'] == 'DESC' ? 'selected' : '' ?>>Décroissant ↓</option>
                    <option value="ASC" <?= $params['order'] == 'ASC' ? 'selected' : '' ?>>Croissant ↑</option>
                </select>
            </div>
            <div>
                <label>Affichage</label>
                <select name="limit" onchange="this.form.submit()">
                    <option value="5" <?= $params['limit'] == '5' ? 'selected' : '' ?>>5 lignes</option>
                    <option value="10" <?= $params['limit'] == '10' ? 'selected' : '' ?>>10 lignes</option>
                    <option value="20" <?= $params['limit'] == '20' ? 'selected' : '' ?>>20 lignes</option>
                    <option value="50" <?= $params['limit'] == '50' ? 'selected' : '' ?>>50 lignes</option>
                    <option value="100" <?= $params['limit'] == '100' ? 'selected' : '' ?>>100 lignes</option>
                    <option value="500" <?= $params['limit'] == '500' ? 'selected' : '' ?>>500 lignes</option>
                    <option value="all" <?= $params['limit'] == 'all' ? 'selected' : '' ?>>Tout afficher</option>
                </select>
            </div>
        </form>

        <div class="table-wrapper">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Mot</th>
                        <th>Type / Genre</th>
                        <th>Rime</th>
                        <th>Signification</th>
                        <th class="actions-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($words)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 40px; color: #94a3b8;">Aucun résultat trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($words as $word): ?>
                        <tr>
                            <td data-label="Mot">
                                <div style="font-weight: 900; color: #1e293b; font-size: 1.1rem;"><?= htmlspecialchars($word['mot']) ?></div>
                                <div style="font-size: 0.7rem; color: #94a3b8;">Pivot: <strong><?= htmlspecialchars($word['lettre']) ?></strong></div>
                            </td>
                            <td data-label="Type">
                                <span style="font-size: 0.65rem; background:#f1f5f9; padding:4px 8px; border-radius:5px; font-weight:900; color:#64748b; text-transform:uppercase;">
                                    <?= htmlspecialchars($word['classe_grammaticale']) ?>
                                </span>
                                <div style="font-size: 0.7rem; color: #94a3b8; margin-top:4px;"><?= $word['genre'] ?> / <?= $word['nombre'] ?></div>
                            </td>
                            <td data-label="Rime">
                                <span style="background: #fff7ed; color: #ea580c; padding: 5px 12px; border-radius: 8px; font-weight: 800; font-size: 0.8rem; border: 1px solid #ffedd5;">
                                    <?= htmlspecialchars($word['rime']) ?>
                                </span>
                            </td>
                            <td data-label="Sens" style="color: #475569; font-size: 0.9rem; max-width: 300px;">
                                <?= mb_strimwidth(htmlspecialchars($word['signification']), 0, 80, "...") ?>
                            </td>
                            <td class="actions-cell">
                                <button class="btn-action btn-edit" onclick="openModal('edit', '<?= $word['id'] ?>', '<?= addslashes(htmlspecialchars($word['mot'])) ?>')">Modifier</button>
                                <button class="btn-action btn-delete" onclick="openModal('delete', '<?= $word['id'] ?>', '<?= addslashes(htmlspecialchars($word['mot'])) ?>')">Supprimer</button>
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
            <p id="modalText" style="color: #64748b; margin-bottom: 30px; line-height: 1.5;"></p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button id="confirmBtn" class="btn-modal" style="color:white; border:none;">CONFIRMER</button>
                <button class="btn-modal btn-modal-cancel" onclick="closeModal()" style="border:none;">ANNULER</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('customModal');
        const confirmBtn = document.getElementById('confirmBtn');

        function openModal(type, id, word) {
            modal.classList.add('active');
            if (type === 'delete') {
                document.getElementById('modalIcon').textContent = "🗑️";
                document.getElementById('modalTitle').textContent = "SUPPRIMER";
                document.getElementById('modalText').innerHTML = `Voulez-vous supprimer définitivement <strong style="color: #1e293b;">"${word}"</strong> ?<br><small>Cette action est irréversible.</small>`;
                confirmBtn.style.background = "#dc2626";
                confirmBtn.textContent = "SUPPRIMER";
                confirmBtn.onclick = () => window.location.href = `admin.php?delete=${id}`;
            } else {
                document.getElementById('modalIcon').textContent = "📝";
                document.getElementById('modalTitle').textContent = "MODIFIER";
                document.getElementById('modalText').innerHTML = `Ouvrir l'éditeur pour <strong style="color: #1e293b;">"${word}"</strong> ?`;
                confirmBtn.style.background = "#2563eb";
                confirmBtn.textContent = "ÉDITER";
                confirmBtn.onclick = () => window.location.href = `edit_word.php?id=${id}`;
            }
        }
        function closeModal() { modal.classList.remove('active'); }
    </script>
</body>
</html>