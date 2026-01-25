<?php
/**
 * SCRIPT DE GÉNÉRATION DES UTILISATEURS DE TEST
 * À placer dans le dossier /public/
 */

// Correction du chemin : on remonte d'un cran pour trouver /src/
require_once __DIR__ . '/../src/RhymeEngine.php';

$engine = new RhymeEngine();
$db = $engine->getPDO();

// Nettoyage de la table pour éviter les doublons lors des tests
try {
    $db->exec("DELETE FROM users");
    // Optionnel : réinitialiser l'auto-incrément pour SQLite
    $db->exec("DELETE FROM sqlite_sequence WHERE name='users'");
} catch (PDOException $e) {
    // Si la table est vide ou erreur mineure, on continue
}

/**
 * Données de test
 * Structure : [username, prenom, nom, email, role, password]
 */
$users = [
    // USERS (Niveau 1)
    ['user1', 'Utilisateur', 'Un', 'user1@exemple.com', 'user', 'pass123'],
    ['user2', 'Utilisateur', 'Deux', 'user2@exemple.com', 'user', 'pass123'],
    
    // ADMINS (Niveau 2 - Modérateurs)
    ['admin1', 'Admin', 'Alpha', 'admin1@exemple.com', 'admin', 'admin123'],
    ['admin2', 'Admin', 'Beta', 'admin2@exemple.com', 'admin', 'admin123'],
    
    // SUPERADMINS (Niveau 3 - Autorité suprême)
    ['spr1', 'Super', 'Boss', 'spr1@exemple.com', 'superadmin', 'root123'],
    ['spr2', 'Super', 'Chef', 'spr2@exemple.com', 'superadmin', 'root123']
];

$sql = "INSERT INTO users (username, prenom, nom, email, role, password, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, datetime('now'))";
$stmt = $db->prepare($sql);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Setup Users - Dico Kabyle</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; background: #f4f7f6; }
        .success { color: #27ae60; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .error { color: #e74c3c; background: #fdedec; padding: 10px; border-radius: 5px; }
        .warning { color: #e67e22; font-weight: bold; border: 2px solid #e67e22; padding: 15px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Initialisation des comptes de test</h1>
    <div id="results">
        <?php
        foreach ($users as $u) {
            // Hachage sécurisé du mot de passe
            $hashedPassword = password_hash($u[5], PASSWORD_DEFAULT);
            
            try {
                $stmt->execute([$u[0], $u[1], $u[2], $u[3], $u[4], $hashedPassword]);
                echo "<div class='success'>✅ Utilisateur <strong>{$u[0]}</strong> ({$u[4]}) créé avec succès.</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Erreur pour {$u[0]} : " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        ?>
    </div>

    <div class="warning">
        ⚠️ ATTENTION : Par mesure de sécurité, supprimez ce fichier (setup_test_users.php) de votre serveur XAMPP immédiatement après l'exécution.
    </div>
    
    <p><a href="login.php">Vers la page de connexion →</a></p>
</body>
</html>