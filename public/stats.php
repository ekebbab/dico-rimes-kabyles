<?php
/**
 * PAGE DE STATISTIQUES MULTI-NIVEAUX - VERSION ULTIME
 * Sécurisation des données utilisateurs et visualisation avancée.
 */
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
$engine = new RhymeEngine();
$db = $engine->getPDO();

$role = Auth::getRole();
$userId = Auth::getUserId();

// --- 1. LOGIQUE DU FILTRE CHRONOLOGIQUE ---
$range = $_GET['range'] ?? 'week';
$dateFilter = match($range) {
    'month' => "start of month",
    'year'  => "start of year",
    'all'   => "-10 years",
    default => "-7 days"
};

// Requête pour le graphique linéaire
$sqlHistory = "SELECT date(created_at) as day, COUNT(*) as nb FROM rimes WHERE created_at >= datetime('now', '$dateFilter') GROUP BY day ORDER BY day ASC";
$historyData = $db->query($sqlHistory)->fetchAll(PDO::FETCH_ASSOC);

// --- 2. DONNÉES PUBLIQUES (LINGUISTIQUE UNIQUEMENT) ---
$totalRimes = $db->query("SELECT COUNT(*) FROM rimes")->fetchColumn();
$thisWeek = $db->query("SELECT COUNT(*) FROM rimes WHERE created_at >= datetime('now', '-7 days')")->fetchColumn();
$thisMonth = $db->query("SELECT COUNT(*) FROM rimes WHERE created_at >= datetime('now', 'start of month')")->fetchColumn();

// Podium des 5 rimes les plus répandues
$podiumRimes = $db->query("SELECT rime, COUNT(*) AS total FROM rimes GROUP BY rime ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Répartition par Famille
$statsFam = $db->query("SELECT famille, COUNT(*) AS total FROM rimes GROUP BY famille ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

// Dernières rimes ajoutées (Détail limité selon le rôle)
$limitDetails = ($role === 'superadmin') ? 15 : 8;
$lastWords = $db->query("SELECT mot, rime, created_at FROM rimes ORDER BY created_at DESC LIMIT $limitDetails")->fetchAll(PDO::FETCH_ASSOC);

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
        // Actions en cours : Utilisateurs récemment inscrits ou modifiés
        $adminStats['recent_users'] = $db->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    }
}

// JSON pour Chart.js
$chartHistoryLabels = json_encode(array_column($historyData, 'day'));
$chartHistoryValues = json_encode(array_column($historyData, 'nb'));
$chartFamLabels = json_encode(array_column($statsFam, 'famille'));
$chartFamValues = json_encode(array_column($statsFam, 'total'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-top: 5px solid var(--primary-color); }
        .card.accent { border-top-color: var(--accent-color); }
        .card.admin { border-top-color: #9b59b6; background: #fdfaff; }
        
        .val { font-size: 2.2rem; font-weight: bold; color: var(--primary-color); display: block; margin: 10px 0; }
        .label { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: bold; }
        
        .dashboard-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; }
        
        /* Podium Style */
        .podium-item { display: flex; align-items: center; padding: 12px; margin-bottom: 8px; border-radius: 8px; background: #f8f9fa; }
        .rank { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; color: white; }
        .rank-1 { background: #FFD700; box-shadow: 0 0 10px rgba(255,215,0,0.5); }
        .rank-2 { background: #C0C0C0; }
        .rank-3 { background: #CD7F32; }
        .rank-others { background: #ced4da; }

        .chart-filter { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .badge-alert { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        
        .last-rimes-list { font-size: 0.9rem; list-style: none; padding: 0; }
        .last-rimes-list li { padding: 8px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        
        @media (max-width: 768px) { .dashboard-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <header class="admin-header">
            <div>
                <h1>Observatoire du Dictionnaire</h1>
                <p>Analyse et suivi du projet communautaire.</p>
            </div>
        </header>

        <div class="stats-grid">
            <div class="card">
                <span class="label">Total Dictionnaire</span>
                <span class="val"><?= number_format($totalRimes, 0, ',', ' ') ?></span>
                <small>Rimes enregistrées</small>
            </div>
            <div class="card accent">
                <span class="label">Ajouts ce mois</span>
                <span class="val">+<?= $thisMonth ?></span>
                <small>Contribution mensuelle</small>
            </div>
            <?php if ($persoStats): ?>
            <div class="card accent" style="background: #fff9f4;">
                <span class="label">Ma Contribution</span>
                <span class="val"><?= $persoStats['total'] ?></span>
                <small>Mots ajoutés par vous</small>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($adminStats): ?>
        <div class="dashboard-row">
            <div class="card admin">
                <h3>👥 État de la Communauté</h3>
                <div style="display: flex; justify-content: space-around; text-align: center; margin-top:20px;">
                    <div><span class="label">Membres</span><span class="val" style="font-size:1.8rem"><?= $adminStats['total_users'] ?></span></div>
                    <div><span class="label">Connectés</span><span class="val" style="font-size:1.8rem; color:#27ae60"><?= $adminStats['online'] ?></span></div>
                    <?php if($role === 'superadmin'): ?>
                    <div><span class="label">En attente</span><span class="val" style="font-size:1.8rem; color:#e74c3c"><?= $adminStats['pending'] ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($role === 'superadmin'): ?>
            <div class="card admin">
                <h3>⚡ Actions en cours</h3>
                <ul class="last-rimes-list">
                    <?php foreach($adminStats['recent_users'] as $ru): ?>
                        <li>
                            <span>👤 <?= htmlspecialchars($ru['username']) ?></span>
                            <small class="text-muted"><?= date('d/m', strtotime($ru['created_at'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card" style="margin-top: 20px;">
            <div class="chart-filter">
                <h3>📈 Courbe de croissance</h3>
                <form method="GET">
                    <select name="range" onchange="this.form.submit()" style="padding: 5px; border-radius: 5px;">
                        <option value="week" <?= $range == 'week' ? 'selected' : '' ?>>7 derniers jours</option>
                        <option value="month" <?= $range == 'month' ? 'selected' : '' ?>>Ce mois-ci</option>
                        <option value="year" <?= $range == 'year' ? 'selected' : '' ?>>Cette année</option>
                        <option value="all" <?= $range == 'all' ? 'selected' : '' ?>>Tout (Historique)</option>
                    </select>
                </form>
            </div>
            <div style="height: 300px;"><canvas id="chartHistory"></canvas></div>
        </div>

        <div class="dashboard-row">
            <div class="card">
                <h3>🏆 Podium des Rimes</h3>
                <div style="margin-top: 15px;">
                    <?php foreach($podiumRimes as $i => $r): ?>
                    <div class="podium-item">
                        <div class="rank rank-<?= ($i < 3) ? ($i+1) : 'others' ?>"><?= $i+1 ?></div>
                        <div style="flex-grow:1;">
                            <strong><?= htmlspecialchars($r['rime']) ?></strong>
                        </div>
                        <span class="badge"><?= $r['total'] ?> mots</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h3>🕒 Dernières rimes</h3>
                <ul class="last-rimes-list">
                    <?php foreach($lastWords as $lw): ?>
                    <li>
                        <span><strong><?= htmlspecialchars($lw['mot']) ?></strong> (<?= htmlspecialchars($lw['rime']) ?>)</span>
                        <small class="text-muted"><?= date('d/m H:i', strtotime($lw['created_at'])) ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card" style="margin-top:20px;">
            <h3>📊 Répartition par Famille</h3>
            <div style="height: 300px;"><canvas id="chartFamilles"></canvas></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Graphique Historique (Courbe)
        new Chart(document.getElementById('chartHistory'), {
            type: 'line',
            data: {
                labels: <?= $chartHistoryLabels ?>,
                datasets: [{
                    label: 'Nouveaux mots',
                    data: <?= $chartHistoryValues ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Graphique Familles
        new Chart(document.getElementById('chartFamilles'), {
            type: 'bar',
            data: {
                labels: <?= $chartFamLabels ?>,
                datasets: [{
                    label: 'Nombre de mots',
                    data: <?= $chartFamValues ?>,
                    backgroundColor: '#2c3e50',
                    borderRadius: 5
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    </script>
</body>
</html>