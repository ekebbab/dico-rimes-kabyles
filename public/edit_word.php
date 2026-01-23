<?php
require_once __DIR__ . '/../src/RhymeEngine.php';
require_once __DIR__ . '/../src/AdminEngine.php';
require_once __DIR__ . '/../src/Auth.php';

$engine = new RhymeEngine();
if (!Auth::isLogged()) { header('Location: login.php'); exit; }

$admin = new AdminEngine($engine->getPDO());
$id = $_GET['id'] ?? null;
$word = $admin->getWordById($id);

if (!$word) { die("Mot non trouvé."); }

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id'            => $id,
        'mot'           => $_POST['mot'],
        'rime'          => $_POST['rime'],
        'signification' => $_POST['signification'],
        'exemple'       => $_POST['exemple'],
        'famille'       => $_POST['famille']
    ];

    // On va devoir ajouter updateWord dans AdminEngine
    if ($admin->updateWord($id, $data)) {
        $message = "<p style='color:green;'>Modifications enregistrées !</p>";
        $word = $admin->getWordById($id); // Rafraîchir les données
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le mot - Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>
    <div class="container">
        <h1>Modifier : <?= htmlspecialchars($word['mot']) ?></h1>
        <?= $message ?>
        <form method="POST">
            <label>Mot :</label>
            <input type="text" name="mot" value="<?= htmlspecialchars($word['mot']) ?>" required style="width:100%;">
            
            <label>Rime :</label>
            <input type="text" name="rime" value="<?= htmlspecialchars($word['rime']) ?>" required style="width:100%;">
            
            <label>Signification :</label>
            <textarea name="signification" style="width:100%; height:100px;"><?= htmlspecialchars($word['signification']) ?></textarea>
            
            <label>Exemple :</label>
            <input type="text" name="exemple" value="<?= htmlspecialchars($word['exemple']) ?>" style="width:100%;">

            <label>Famille :</label>
            <input type="text" name="famille" value="<?= htmlspecialchars($word['famille']) ?>" style="width:100%;">
            
            <button type="submit">Mettre à jour</button>
        </form>
    </div>
</body>
</html>