<?php
/**
 * PAGE DE PROFIL UTILISATEUR
 * Permet de modifier ses informations personnelles et son mot de passe.
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
$userId = Auth::getUserId();
$message = "";

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'prenom'   => trim($_POST['prenom']),
        'nom'      => trim($_POST['nom']),
        'email'    => trim($_POST['email']),
        'password' => $_POST['new_password'] // Sera haché dans l'Engine si rempli
    ];

    if ($admin->updateUser($userId, $data)) {
        $message = "<p class='success-msg'>✅ Profil mis à jour avec succès !</p>";
    } else {
        $message = "<p class='error-msg'>❌ Erreur lors de la mise à jour.</p>";
    }
}

// Récupération des infos fraîches de l'utilisateur
$user = $admin->getUserById($userId);

// Récupération du nombre de rimes postées par cet utilisateur
$stmtStats = $engine->getPDO()->prepare("SELECT COUNT(*) FROM rimes WHERE auteur_id = ?");
$stmtStats->execute([$userId]);
$totalRimes = $stmtStats->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <div class="container">
        <header class="admin-header">
            <div>
                <h1>Mon Profil</h1>
                <p>Bienvenue, <strong><?= htmlspecialchars($user['username']) ?></strong></p>
            </div>
            <a href="admin.php" class="btn-primary">← Retour Dashboard</a>
        </header>

        <?= $message ?>

        <div class="admin-grid">
            <form method="POST" class="admin-form full-width" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>">
                </div>
                <div class="form-group full-width">
                    <label>Adresse Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group full-width" style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 20px;">
                    <label>Changer le mot de passe (laisser vide pour ne pas modifier)</label>
                    <input type="password" name="new_password" placeholder="Nouveau mot de passe">
                </div>

                <div class="full-width">
                    <button type="submit" class="btn-submit btn-full">Enregistrer les modifications</button>
                </div>
            </form>

            <div class="filter-card text-center" style="margin-top: 20px;">
                <h3>Mes Statistiques</h3>
                <div style="font-size: 2rem; color: var(--accent-color); font-weight: bold;">
                    <?= $totalRimes ?>
                </div>
                <p>Rimes ajoutées au dictionnaire</p>
                <hr>
                <p><small>Compte créé le : <?= $user['created_at'] ?? 'Inconnue' ?></small></p>
                <p><span class="badge">Rôle : <?= ucfirst($user['role']) ?></span></p>
            </div>
        </div>
    </div>
</body>
</html>