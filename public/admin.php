<?php
/**
 * DASHBOARD ADMINISTRATEUR - VERSION AVANCÉE
 * Gestion des rimes avec filtrage automatique, sécurité par rôles et variantes.
 * Intègre l'agent de communication moderne (Modale de confirmation).
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

// Sécurité : Redirection vers login si non connecté
if (!Auth::isLogged()) {
    header('Location: login.php');
    exit;
}

$engine = new RhymeEngine();
$admin = new AdminEngine($engine->getPDO());

// --- LOGIQUE DE SUPPRESSION SÉCURISÉE ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Récupération des infos pour vérification des droits
    $stmt = $engine->getPDO()->prepare("
        SELECT r.auteur_id, u.role as author_role 
        FROM rimes r 
        LEFT JOIN users u ON r.auteur_id = u.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $info = $stmt->fetch();

    if ($info && Auth::canManage($info['auteur_id'], $info['author_role'])) {
        // L'appel à deleteWord gère maintenant la renumérotation des variantes
        $admin->deleteWord($id);
        header('Location: admin.php?msg=deleted');
    } else {
        header('Location: admin.php?msg=denied');
    }
    exit;
}

// Paramètres de filtrage et tri
$params = [
    'q'     => $_GET['q'] ?? '',
    'sort'  => $_GET['sort'] ?? 'created_at',
    'order' => $_GET['order'] ?? 'desc',
    'limit' => $_GET['limit'] ?? '50'
];

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
    <style>
        .badge-variante {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: normal;
            margin-left: 6px;
            font-style: italic;
        }

        /* --- STYLE DE LA MODALE MODERNE --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none; align-items: center; justify-content: center;
            z-index: 1000; backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal-card {
            background: white; width: 90%; max-width: 450px;
            padding: 30px; border-radius: 20px; text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            transform: translateY(-20px); transition: 0.3s ease;
        }

        .modal-icon { font-size: 3.5rem; margin-bottom: 15px; display: block; }
        .modal-card h2 { color: #2d3436; margin-bottom: 15px; }
        .modal-card p { color: #636e72; line-height: 1.6; margin-bottom: 25px; }

        .modal-buttons { display: flex; gap: 12px; justify-content: center; }
        
        .btn-modal { 
            padding: 12px 24px; border-radius: 10px; border: none; 
            font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 1rem;
        }
        
        .btn-confirm { background: #e74c3c; color: white; } /* Rouge Danger */
        .btn-confirm.edit { background: var(--primary-color); } /* Couleur site pour Edition */
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
                <h1>Gestion des Rimes</h1>
                <p>Rôle : <span class="badge"><?= ucfirst(Auth::getRole()) ?></span></p>
            </div>
            <div class="admin-actions">
                <a href="add_word.php" class="btn-add">+ Nouveau Mot</a>
                <?php if(Auth::getRole() === 'superadmin'): ?>
                    <a href="manage_users.php" class="btn-primary" style="margin-left:10px;">Membres</a>
                <?php endif; ?>
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

                <select name="limit">
                    <option value="5" <?= $params['limit'] == '5' ? 'selected' : '' ?>>5 lignes</option>
                    <option value="10" <?= $params['limit'] == '10' ? 'selected' : '' ?>>10 lignes</option>
                    <option value="20" <?= $params['limit'] == '20' ? 'selected' : '' ?>>20 lignes</option>
                    <option value="50" <?= $params['limit'] == '50' ? 'selected' : '' ?>>50 lignes</option>
                    <option value="100" <?= $params['limit'] == '100' ? 'selected' : '' ?>>100 lignes</option>
                    <option value="500" <?= $params['limit'] == '500' ? 'selected' : '' ?>>500 lignes</option>
                    <option value="all" <?= $params['limit'] == 'all' ? 'selected' : '' ?>>Tout</option>
                </select>
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
                    <?php if (empty($words)): ?>
                        <tr><td colspan="4" style="text-align:center;">Aucune rime trouvée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($words as $word): ?>
                        <tr>
                            <td class="bold">
                                <?= htmlspecialchars($word['mot']) ?>
                                <?php if($word['variante'] > 1): ?>
                                    <span class="badge-variante">(v<?= $word['variante'] ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge"><?= htmlspecialchars($word['rime']) ?></span></td>
                            <td><small><?= htmlspecialchars($word['auteur_nom'] ?? 'Inconnu') ?></small></td>
                            <td class="actions">
                                <?php if (Auth::canManage($word['auteur_id'], $word['author_role'])): ?>
                                    <a href="#" class="link-edit" onclick="openModal('edit', '<?= $word['id'] ?>', '<?= addslashes(htmlspecialchars($word['mot'])) ?>')">Modifier</a>
                                    <a href="#" class="link-delete" onclick="openModal('delete', '<?= $word['id'] ?>', '<?= addslashes(htmlspecialchars($word['mot'])) ?>')">Supprimer</a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.8rem;">Lecture seule</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="customModal" class="modal-overlay">
        <div class="modal-card">
            <span id="modalIcon" class="modal-icon">⚠️</span>
            <h2 id="modalTitle">Confirmation</h2>
            <p id="modalText">Voulez-vous effectuer cette action ?</p>
            
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

        /**
         * Ouvre la modale avec le descriptif correspondant
         */
        function openModal(type, id, word) {
            modal.style.display = 'flex';
            
            if (type === 'delete') {
                modalIcon.textContent = "🗑️";
                modalTitle.textContent = "Supprimer la rime";
                modalText.innerHTML = `Vous êtes sur le point de supprimer définitivement le mot <strong>"${word}"</strong>.<br><br>Cette opération est <strong>irréversible</strong>. Les variantes suivantes seront automatiquement renumérotées pour maintenir la cohérence du dictionnaire.`;
                confirmBtn.textContent = "Supprimer la rime";
                confirmBtn.className = "btn-modal btn-confirm";
                confirmBtn.onclick = function() {
                    window.location.href = `admin.php?delete=${id}`;
                };
            } 
            else if (type === 'edit') {
                modalIcon.textContent = "✏️";
                modalTitle.textContent = "Modifier l'entrée";
                modalText.innerHTML = `Souhaitez-vous ouvrir l'éditeur pour modifier les informations liées au mot <strong>"${word}"</strong> ?`;
                confirmBtn.textContent = "Ouvrir l'éditeur";
                confirmBtn.className = "btn-modal btn-confirm edit";
                confirmBtn.onclick = function() {
                    window.location.href = `edit_word.php?id=${id}`;
                };
            }
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Fermer la modale si on clique à l'extérieur de la carte
        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }

        // Auto-soumission des filtres
        document.querySelectorAll('#autoFilterForm select').forEach(select => {
            select.addEventListener('change', () => {
                document.getElementById('autoFilterForm').submit();
            });
        });
    </script>
</body>
</html>