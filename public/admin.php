<?php
/**
 * DASHBOARD ADMINISTRATEUR - VERSION DESIGN PREMIUM 2026
 * Harmonisé avec l'esthétique Amawal (dégradés, ombres portées, typographie grasse).
 */
require_once __DIR__ . '/../src/Auth.php';
Auth::init();

require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';

$engine = new RhymeEngine();
$db = $engine->getPDO();

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

// --- PARAMÈTRES ---
$params = [
    'q'      => $_GET['q'] ?? '',
    'sort'   => $_GET['sort'] ?? 'updated_at',
    'order'  => $_GET['order'] ?? 'desc',
    'limit'  => $_GET['limit'] ?? '50'
];

$words = $engine->searchAdvanced($params);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f8fafc; color: #1e293b; font-family: 'Segoe UI', system-ui, sans-serif; }

        /* --- EN-TÊTE ET BOUTONS --- */
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; margin-top: 20px; }
        .header-btns { display: flex; gap: 15px; }

        .btn-new { 
            background: #27ae60 !important; 
            color: white !important; 
            padding: 14px 28px; 
            border-radius: 12px; 
            text-decoration: none; 
            font-weight: 900; 
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 6px 15px rgba(39, 174, 96, 0.25);
        }
        .btn-new:hover { background: #219150 !important; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(39, 174, 96, 0.35); }

        .btn-members {
            background: #8e44ad !important;
            color: white !important;
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 6px 15px rgba(142, 68, 173, 0.25);
        }
        .btn-members:hover { background: #7d3c98 !important; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(142, 68, 173, 0.35); }

        /* --- FILTRES (Style profile.php) --- */
        .filter-row { 
            display: grid; 
            grid-template-columns: 2fr 1fr 1fr 1fr; 
            gap: 20px; 
            background: white; 
            padding: 25px; 
            border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 40px;
            border: 1px solid #eef2f6;
        }
        .filter-row label {
            display: block;
            font-size: 0.7rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .filter-row select, .filter-row input { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #f1f5f9; 
            border-radius: 10px;
            font-size: 0.95rem;
            color: #475569;
            background: linear-gradient(to bottom right, #ffffff, #faf9f7);
            transition: 0.3s;
        }
        .filter-row select:focus, .filter-row input:focus {
            border-color: #e67e22;
            outline: none;
            background: #ffffff;
            box-shadow: inset 0 2px 4px rgba(230, 126, 34, 0.05);
        }

        /* --- TABLE --- */
        .table-wrapper { 
            background: white; 
            border-radius: 24px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.04); 
            overflow: hidden; 
            border: 1px solid #f1f5f9; 
        }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #fafbfc; color: #94a3b8; text-align: left; padding: 20px; font-weight: 800; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; border-bottom: 2px solid #f8fafc; }
        .styled-table td { padding: 20px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        .styled-table tr:hover { background-color: #fdfdfd; }

        /* --- ACTIONS --- */
        .action-column { text-align: right !important; }
        .btn-action { padding: 10px 18px; border-radius: 10px; font-weight: 800; text-decoration: none; font-size: 0.8rem; transition: 0.2s; display: inline-block; }
        .btn-edit { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .btn-edit:hover { background: #0369a1; color: white; transform: scale(1.05); }
        .btn-delete { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; margin-left: 8px; }
        .btn-delete:hover { background: #b91c1c; color: white; transform: scale(1.05); }

        /* --- MODALE --- */
        #customModal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px);
            display: none; align-items: center; justify-content: center; z-index: 10000;
        }
        #customModal.active { display: flex; }
        .modal-card {
            background: white; padding: 45px; border-radius: 30px; max-width: 450px; width: 90%;
            text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .btn-modal { padding: 16px 32px; border-radius: 15px; font-weight: 900; font-size: 1rem; cursor: pointer; transition: 0.3s; border: none; }
        .btn-modal-cancel { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-modal-cancel:hover { background: #e2e8f0; color: #1e293b; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container" style="max-width: 1100px;">
        <header class="admin-header">
            <div>
                <h1 style="font-weight: 900; color: #1e293b; font-size: 2.5rem; margin:0;">Dashboard</h1>
                <p style="color: #64748b; margin-top:8px; font-size: 1.1rem;">Gestion du dictionnaire • <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
            </div>
            <div class="header-btns">
                <?php if ($role === 'superadmin'): ?>
                    <a href="manage_users.php" class="btn-members">
                        <span>👥</span> MEMBRES
                    </a>
                <?php endif; ?>
                <a href="add_word.php" class="btn-new">
                    <span>➕</span> NOUVEAU MOT
                </a>
            </div>
        </header>

        <form method="GET" id="filterForm" class="filter-row">
            <div>
                <label>Recherche</label>
                <input type="text" name="q" placeholder="Rechercher un mot..." value="<?= htmlspecialchars($params['q']) ?>">
            </div>
            <div>
                <label>Trier par</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="updated_at" <?= $params['sort'] == 'updated_at' ? 'selected' : '' ?>>Date de modification</option>
                    <option value="mot" <?= $params['sort'] == 'mot' ? 'selected' : '' ?>>Ordre alphabétique</option>
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
                    <option value="50" <?= $params['limit'] == '50' ? 'selected' : '' ?>>50 lignes</option>
                    <option value="100" <?= $params['limit'] == '100' ? 'selected' : '' ?>>100 lignes</option>
                    <option value="all" <?= $params['limit'] == 'all' ? 'selected' : '' ?>>Tout afficher</option>
                </select>
            </div>
        </form>

        <div class="table-wrapper">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Mot & Grammaire</th>
                        <th>Phonétique / Rime</th>
                        <th>Signification</th>
                        <th class="action-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($words)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: #94a3b8;">Aucun résultat trouvé pour votre recherche.</td></tr>
                    <?php else: ?>
                        <?php foreach ($words as $word): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 900; color: #1e293b; font-size: 1.2rem;"><?= htmlspecialchars($word['mot']) ?></div>
                                <span style="font-size: 0.65rem; background:#f1f5f9; padding:4px 10px; border-radius:6px; font-weight:900; color:#64748b; text-transform:uppercase; margin-top:5px; display:inline-block;">
                                    <?= htmlspecialchars($word['classe_grammaticale']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="background: #fff7ed; color: #ea580c; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; border: 1px solid #ffedd5;">
                                    <?= htmlspecialchars($word['rime']) ?>
                                </span>
                            </td>
                            <td style="color: #475569; font-size: 0.95rem; line-height: 1.4;">
                                <?= mb_strimwidth(htmlspecialchars($word['signification']), 0, 70, "...") ?>
                            </td>
                            <td class="action-column">
                                <a href="javascript:void(0)" class="btn-action btn-edit" onclick="openModal('edit', '<?= $word['id'] ?>', '<?= addslashes(htmlspecialchars($word['mot'])) ?>')">Modifier</a>
                                <a href="javascript:void(0)" class="btn-action btn-delete" onclick="openModal('delete', '<?= $word['id'] ?>', '<?= addslashes(htmlspecialchars($word['mot'])) ?>')">Supprimer</a>
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
            <div id="modalIcon" style="font-size: 5rem; margin-bottom: 25px;">⚠️</div>
            <h2 id="modalTitle" style="font-weight: 900; color: #1e293b; margin-bottom: 15px; font-size: 1.8rem;">Confirmation</h2>
            <p id="modalText" style="color: #64748b; margin-bottom: 35px; line-height: 1.6; font-size: 1.1rem;">Action en cours...</p>
            
            <div style="display: flex; gap: 20px; justify-content: center;">
                <button class="btn-modal btn-modal-cancel" onclick="closeModal()">ANNULER</button>
                <button id="confirmBtn" class="btn-modal" style="color:white; font-weight:900;">CONFIRMER</button>
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
                document.getElementById('modalText').innerHTML = `Voulez-vous supprimer définitivement le mot <strong style="color: #1e293b;">"${word}"</strong> ?<br><small>Cette action est irréversible.</small>`;
                confirmBtn.style.background = "#dc2626";
                confirmBtn.style.boxShadow = "0 8px 15px rgba(220, 38, 38, 0.3)";
                confirmBtn.textContent = "SUPPRIMER";
                confirmBtn.onclick = () => window.location.href = `admin.php?delete=${id}`;
            } else {
                document.getElementById('modalIcon').textContent = "✏️";
                document.getElementById('modalTitle').textContent = "MODIFIER";
                document.getElementById('modalText').innerHTML = `Voulez-vous ouvrir l'éditeur pour le mot <strong style="color: #1e293b;">"${word}"</strong> ?`;
                confirmBtn.style.background = "#2563eb";
                confirmBtn.style.boxShadow = "0 8px 15px rgba(37, 99, 235, 0.3)";
                confirmBtn.textContent = "ÉDITER";
                confirmBtn.onclick = () => window.location.href = `edit_word.php?id=${id}`;
            }
        }

        function closeModal() { modal.classList.remove('active'); }
    </script>
</body>
</html>