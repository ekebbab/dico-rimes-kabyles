<?php
/**
 * PAGE DE STATISTIQUES MULTI-NIVEAUX - VERSION LINGUISTIQUE 2026
 * Analyse approfondie de la base de données : Lettres, Grammaire et Activité.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
$engine = new RhymeEngine();
$db = $engine->getPDO();

$role = Auth::getRole();
$userId = Auth::getUserId();

// --- 1. LOGIQUE DU FILTRE CHRONOLOGIQUE ---
$range = $_GET['range'] ?? 'month';
$dateFilter = match($range) {
    'month' => "start of month",
    'year'  => "start of year",
    'all'   => "-10 years",
    default => "-7 days"
};

// Requête pour le graphique d'activité (Croissance)
$sqlHistory = "SELECT date(created_at) as day, COUNT(*) as nb FROM rimes WHERE created_at >= datetime('now', '$dateFilter') GROUP BY day ORDER BY day ASC";
$historyData = $db->query($sqlHistory)->fetchAll(PDO::FETCH_ASSOC);

// --- 2. DONNÉES LINGUISTIQUES PUBLIQUES ---
$totalRimes = $db->query("SELECT COUNT(*) FROM rimes")->fetchColumn();
$thisMonth = $db->query("SELECT COUNT(*) FROM rimes WHERE created_at >= datetime('now', 'start of month')")->fetchColumn();

// Répartition par Classe Grammaticale (Nouveau)
$statsClasse = $db->query("SELECT classe_grammaticale as label, COUNT(*) as total FROM rimes GROUP BY label ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

// Répartition par Lettre Pivot (Remplace Famille)
$statsLettre = $db->query("SELECT lettre, COUNT(*) AS total FROM rimes GROUP BY lettre ORDER BY total DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

// Podium des Rimes les plus fréquentes
$podiumRimes = $db->query("SELECT rime, COUNT(*) AS total FROM rimes GROUP BY rime ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Dernières rimes ajoutées
$limitDetails = ($role === 'superadmin') ? 12 : 6;
$lastWords = $db->query("SELECT mot, rime, lettre, created_at FROM rimes ORDER BY created_at DESC LIMIT $limitDetails")->fetchAll(PDO::FETCH_ASSOC);

// --- 3. DONNÉES PRIVÉES (MEMBRES) ---
$persoStats = null;
if (Auth::isLogged()) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM rimes WHERE auteur_id = ?");
    $stmt->execute([$userId]);
    $persoStats['total'] = $stmt->fetchColumn();
}

// --- 4. DONNÉES STAFF (ADMIN/SUPERADMIN) ---
$adminStats = null;
if ($role === 'admin' || $role === 'superadmin') {
    $adminStats['total_users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $adminStats['online'] = $db->query("SELECT COUNT(*) FROM users WHERE last_seen > datetime('now', '-10 minutes')")->fetchColumn();
    
    if ($role === 'superadmin') {
        $adminStats['pending'] = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn();
        $adminStats['recent_users'] = $db->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Encodage JSON pour les graphiques
$chartHistoryLabels = json_encode(array_column($historyData, 'day'));
$chartHistoryValues = json_encode(array_column($historyData, 'nb'));
$chartClasseLabels  = json_encode(array_column($statsClasse, 'label'));
$chartClasseValues  = json_encode(array_column($statsClasse, 'total'));
$chartLettreLabels  = json_encode(array_column($statsLettre, 'lettre'));
$chartLettreValues  = json_encode(array_column($statsLettre, 'total'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Observatoire - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: var(--shadow); border-top: 5px solid var(--primary-color); }
        .card.accent { border-top-color: var(--accent-color); }
        .card.admin { border-top-color: #9b59b6; background: #fdfaff; }
        
        .val { font-size: 2.2rem; font-weight: bold; color: var(--primary-color); display: block; margin: 10px 0; }
        .label { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: bold; }
        
        .dashboard-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; }
        
        .podium-item { display: flex; align-items: center; padding: 10px; margin-bottom: 8px; border-radius: 8px; background: #f8f9fa; border-left: 4px solid #eee; }
        .rank { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; color: white; font-size: 0.8rem; }
        .rank-1 { background: #FFD700; border: 2px solid #E6BE00; }
        .rank-2 { background: #C0C0C0; }
        .rank-3 { background: #CD7F32; }
        .rank-others { background: #ced4da; }

        .chart-container { height: 300px; position: relative; margin-top: 15px; }
        .last-rimes-list { font-size: 0.85rem; list-style: none; padding: 0; }
        .last-rimes-list li { padding: 10px 0; border-bottom: 1px solid #f1f1f1; display: flex; justify-content: space-between; align-items: center; }
        
        @media (max-width: 900px) { .dashboard-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <header class="admin-header">
            <div>
                <h1>Observatoire Linguistique</h1>
                <p>Analyse de la base de données et activité de la communauté.</p>
            </div>
        </header>

        <div class="stats-grid">
            <div class="card">
                <span class="label">Total Dictionnaire</span>
                <span class="val"><?= number_format($totalRimes, 0, ',', ' ') ?></span>
                <small>Rimes et variantes</small>
            </div>
            <div class="card accent">
                <span class="label">Activité Mensuelle</span>
                <span class="val">+<?= $thisMonth ?></span>
                <small>Nouveaux mots ce mois</small>
            </div>
            <?php if ($persoStats): ?>
            <div class="card" style="border-top-color: #27ae60; background: #f4fff8;">
                <span class="label">Ma Contribution</span>
                <span class="val"><?= $persoStats['total'] ?></span>
                <small>Mots soumis par vous</small>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($adminStats): ?>
        <div class="dashboard-row">
            <div class="card admin">
                <h3>👥 État du Réseau</h3>
                <div style="display: flex; justify-content: space-around; text-align: center; margin-top:20px;">
                    <div><span class="label">Membres</span><span class="val" style="font-size:1.8rem"><?= $adminStats['total_users'] ?></span></div>
                    <div><span class="label">En ligne</span><span class="val" style="font-size:1.8rem; color:#27ae60"><?= $adminStats['online'] ?></span></div>
                    <?php if($role === 'superadmin'): ?>
                    <div><span class="label">À valider</span><span class="val" style="font-size:1.8rem; color:#e74c3c"><?= $adminStats['pending'] ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($role === 'superadmin'): ?>
            <div class="card admin">
                <h3>⚡ Nouveaux Inscrits</h3>
                <ul class="last-rimes-list">
                    <?php foreach($adminStats['recent_users'] as $ru): ?>
                        <li>
                            <span>👤 <strong><?= htmlspecialchars($ru['username']) ?></strong></span>
                            <small class="text-muted"><?= date('d/m', strtotime($ru['created_at'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card" style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>📈 Croissance du dictionnaire</h3>
                <form method="GET">
                    <select name="range" onchange="this.form.submit()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
                        <option value="week" <?= $range == 'week' ? 'selected' : '' ?>>7 derniers jours</option>
                        <option value="month" <?= $range == 'month' ? 'selected' : '' ?>>Ce mois-ci</option>
                        <option value="year" <?= $range == 'year' ? 'selected' : '' ?>>Cette année</option>
                        <option value="all" <?= $range == 'all' ? 'selected' : '' ?>>Historique complet</option>
                    </select>
                </form>
            </div>
            <div class="chart-container"><canvas id="chartHistory"></canvas></div>
        </div>

        <div class="dashboard-row">
            <div class="card">
                <h3>🏷️ Répartition par Nature</h3>
                <div class="chart-container"><canvas id="chartClasse"></canvas></div>
            </div>

            <div class="card">
                <h3>🕒 Flux récent</h3>
                <ul class="last-rimes-list">
                    <?php foreach($lastWords as $lw): ?>
                    <li>
                        <div>
                            <strong><?= htmlspecialchars($lw['mot']) ?></strong> 
                            <span class="badge" style="font-size: 0.7rem;"><?= htmlspecialchars($lw['lettre']) ?></span>
                        </div>
                        <small class="text-muted"><?= date('H:i', strtotime($lw['created_at'])) ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="dashboard-row">
            <div class="card">
                <h3>🏆 Rimes les plus fréquentes</h3>
                <div style="margin-top: 15px;">
                    <?php foreach($podiumRimes as $i => $r): ?>
                    <div class="podium-item">
                        <div class="rank rank-<?= ($i < 3) ? ($i+1) : 'others' ?>"><?= $i+1 ?></div>
                        <div style="flex-grow:1;">
                            <strong><?= htmlspecialchars($r['rime']) ?></strong>
                        </div>
                        <span class="badge" style="background: var(--primary-color); color:white;"><?= $r['total'] ?> mots</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h3>🔤 Densité par Lettre Pivot</h3>
                <div class="chart-container"><canvas id="chartLettres"></canvas></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const commonOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };

        // 1. Graphique Croissance (Ligne)
        new Chart(document.getElementById('chartHistory'), {
            type: 'line',
            data: {
                labels: <?= $chartHistoryLabels ?>,
                datasets: [{
                    label: 'Mots ajoutés',
                    data: <?= $chartHistoryValues ?>,
                    borderColor: '#e67e22',
                    backgroundColor: 'rgba(230, 126, 34, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: commonOptions
        });

        // 2. Graphique Nature (Doughnut)
        new Chart(document.getElementById('chartClasse'), {
            type: 'doughnut',
            data: {
                labels: <?= $chartClasseLabels ?>,
                datasets: [{
                    data: <?= $chartClasseValues ?>,
                    backgroundColor: ['#2c3e50', '#e67e22', '#27ae60', '#3498db', '#9b59b6']
                }]
            },
            options: { ...commonOptions, plugins: { legend: { display: true, position: 'bottom' } } }
        });

        // 3. Graphique Lettres (Barres horizontales)
        new Chart(document.getElementById('chartLettres'), {
            type: 'bar',
            data: {
                labels: <?= $chartLettreLabels ?>,
                datasets: [{
                    data: <?= $chartLettreValues ?>,
                    backgroundColor: '#2c3e50',
                    borderRadius: 5
                }]
            },
            options: { ...commonOptions, indexAxis: 'y' }
        });
    </script>
</body>
</html>