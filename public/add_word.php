<?php
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

$engine = new RhymeEngine();
if (!Auth::isLogged()) { header('Location: login.php'); exit; }

$admin = new AdminEngine($engine->getPDO());
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'mot'           => $_POST['mot'],
        'rime'          => $_POST['rime'],
        'signification' => $_POST['signification'],
        'exemple'       => $_POST['exemple'],
        'famille'       => $_POST['famille']
    ];

    if ($admin->addWord($data)) {
        $message = "<p style='color:green;'>Le mot a été ajouté avec succès !</p>";
    } else {
        $message = "<p style='color:red;'>Erreur lors de l'ajout.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un mot - Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include __DIR__ . '/../src/views/navbar.php'; ?>
    <div class="container">
        <a href="admin.php">← Retour au tableau de bord</a>
        <h1>Ajouter un nouveau mot</h1>
        <?= $message ?>
        <form method="POST" style="background: white; padding: 20px; border-radius: 8px;">
            <label>Mot (Kabyle) :</label><br>
            <input type="text" name="mot" required style="width:100%;"><br><br>
            
            <label>Rime (Terminaison) :</label><br>
            <input type="text" name="rime" required style="width:100%;"><br><br>
            
            <label>Signification :</label><br>
            <textarea name="signification" style="width:100%; height:100px;"></textarea><br><br>
            
            <label>Exemple :</label><br>
            <input type="text" name="exemple" style="width:100%;"><br><br>

            <label>Famille :</label><br>
            <input type="text" name="famille" style="width:100%;"><br><br>
            
            <button type="submit">Enregistrer le mot</button>
        </form>
    </div>
</body>
</html>