<?php
/**
 * DASHBOARD ADMINISTRATEUR - VERSION LINGUISTIQUE AVANCÉE
 * Gestion des rimes, variantes et export PDF synchronisé.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();

$engine = new RhymeEngine();

// Sécurité : Redirection si non connecté
if (!Auth::isLogged($engine->getPDO())) { 
    header('Location: login.php'); 
    exit; 
}

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
        $admin->deleteWord($id);
        header('Location: admin.php?msg=deleted');
    } else {
        header('Location: admin.php?msg=denied');
    }
    exit;
}

// Paramètres de filtrage et tri (Alignés sur RhymeEngine)
$params = [
    'q'     => $_GET['q'] ?? '',
    'sort'  => $_GET['sort'] ?? 'updated_at',
    'order' => $_GET['order'] ?? 'desc',
    'limit' => $_GET['limit'] ?? '50'
];

// Appel du moteur de recherche pour récupérer les données triées
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
        .badge-variante {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: normal;
            margin-left: 6px;
            font-style: italic;
        }

        .meta-info {
            font-size: 0.8rem;
            color: var(--text-muted);
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
        
        .btn-confirm { background: #e74c3c; color: white; } 
        .btn-confirm.edit { background: var(--primary-color); } 
        .btn-cancel { background: #dfe6e9; color: #2d3436; }
        
        .btn-modal:hover { opacity: 0.85; transform: translateY(-2px); }

        .export-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid #eee;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <div class="admin-header">
            <div>
                <h1>Gestion du Dictionnaire</h1>
                <p>Session : <span class="badge"><?= ucfirst(Auth::getRole()) ?></span></p>
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
                <input type="text" name="q" placeholder="Rechercher mot, sens..." value="<?= htmlspecialchars($params['q']) ?>">
                
                <select name="sort">
                    <option value="updated_at" <?= $params['sort'] == 'updated_at' ? 'selected' : '' ?>>Date Modif.</option>
                    <option value="mot" <?= $params['sort'] == 'mot' ? 'selected' : '' ?>>Ordre Alphabétique</option>
                    <option value="lettre" <?= $params['sort'] == 'lettre' ? 'selected' : '' ?>>Lettre Pivot</option>
                    <option value="rime" <?= $params['sort'] == 'rime' ? 'selected' : '' ?>>Rime</option>
                </select>

                <select name="order">
                    <option value="DESC" <?= $params['order'] == 'DESC' ? 'selected' : '' ?>>Décroissant ↓</option>
                    <option value="ASC" <?= $params['order'] == 'ASC' ? 'selected' : '' ?>>Croissant ↑</option>
                </select>

                <select name="limit">
                    <option value="10" <?= $params['limit'] == '10' ? 'selected' : '' ?>>10 lignes</option>
                    <option value="50" <?= $params['limit'] == '50' ? 'selected' : '' ?>>50 lignes</option>
                    <option value="100" <?= $params['limit'] == '100' ? 'selected' : '' ?>>100 lignes</option>
                    <option value="500" <?= $params['limit'] == '500' ? 'selected' : '' ?>>500 lignes</option>
                    <option value="all" <?= $params['limit'] == 'all' ? 'selected' : '' ?>>Tout afficher</option>
                </select>
            </form>
        </div>

        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Mot & Grammaire</th>
                        <th>Phonétique</th>
                        <th>Signification</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($words)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 30px;">Aucune donnée ne correspond à vos filtres.</td></tr>
                    <?php else: ?>
                        <?php foreach ($words as $word): ?>
                        <tr>
                            <td>
                                <div class="bold">
                                    <?= htmlspecialchars($word['mot']) ?>
                                    <?php if($word['variante'] > 1): ?>
                                        <span class="badge-variante">(v<?= $word['variante'] ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="meta-info">
                                    <?= htmlspecialchars($word['classe_grammaticale']) ?> 
                                    (<?= htmlspecialchars($word['genre']) ?> / <?= htmlspecialchars($word['nombre']) ?>)
                                </div>
                            </td>
                            <td>
                                <span class="badge">L: <?= htmlspecialchars($word['lettre']) ?></span><br>
                                <span class="badge badge-info">R: <?= htmlspecialchars($word['rime']) ?></span>
                            </td>
                            <td>
                                <div style="max-width: 300px; font-size: 0.9rem;">
                                    <?= mb_strimwidth(htmlspecialchars($word['signification']), 0, 80, "...") ?>
                                </div>
                            </td>
                            <td class="actions">
                                <a href="#" class="link-edit" onclick="openModal('edit', '<?= $word['id'] ?>', '<?= addslashes(htmlspecialchars($word['mot'])) ?>')">Modifier</a>
                                <a href="#" class="link-delete" onclick="openModal('delete', '<?= $word['id'] ?>', '<?= addslashes(htmlspecialchars($word['mot'])) ?>')">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="export-card">
            <h3>Exportation du Catalogue</h3>
            <p style="color: var(--text-muted); margin-bottom: 15px;">Générez un document PDF professionnel basé sur la structure linguistique actuelle.</p>
            <a href="export_pdf.php?<?= http_build_query($params) ?>" class="btn-primary" target="_blank" style="background:#e67e22; border:none; padding:12px 25px; text-decoration: none; display: inline-block; border-radius: 8px; color: white; font-weight: bold;">
                📥 Télécharger le Catalogue PDF
            </a>
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

        function openModal(type, id, word) {
            modal.style.display = 'flex';
            
            if (type === 'delete') {
                modalIcon.textContent = "🗑️";
                modalTitle.textContent = "Supprimer l'entrée";
                modalText.innerHTML = `Voulez-vous supprimer définitivement <strong>"${word}"</strong> ?<br>Les variantes seront réorganisées automatiquement.`;
                confirmBtn.textContent = "Confirmer la suppression";
                confirmBtn.className = "btn-modal btn-confirm";
                confirmBtn.onclick = function() {
                    window.location.href = `admin.php?delete=${id}`;
                };
            } 
            else if (type === 'edit') {
                modalIcon.textContent = "✏️";
                modalTitle.textContent = "Modifier l'entrée";
                modalText.innerHTML = `Ouvrir l'éditeur pour le mot <strong>"${word}"</strong> ?`;
                confirmBtn.textContent = "Éditer";
                confirmBtn.className = "btn-modal btn-confirm edit";
                confirmBtn.onclick = function() {
                    window.location.href = `edit_word.php?id=${id}`;
                };
            }
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }

        // Auto-soumission des filtres lors du changement des sélecteurs
        document.querySelectorAll('#autoFilterForm select').forEach(select => {
            select.addEventListener('change', () => {
                document.getElementById('autoFilterForm').submit();
            });
        });
    </script>
</body>
</html>