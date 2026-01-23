<!DOCTYPE html>
<html lang="ber">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dico Kabyle des rimes</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
    <div class="container">
        <header>
            <h1>Dico Kabyle des rimes</h1>
            <form action="index.php" method="GET">
                <input type="text" name="q" placeholder="Entrez une terminaison..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit">Rechercher</button>
            </form>
        </header>
		<?php if (!empty($searchQuery)): ?>
			<div class="actions">
				<a href="download_pdf.php?q=<?= urlencode($searchQuery) ?>" class="btn-pdf">
					📄 Télécharger les résultats en PDF
				</a>
			</div>
		<?php endif; ?>
        <main>
            <?php if (!empty($searchQuery)): ?>
                <p class="stats"><?= count($results) ?> résultat(s) pour "<?= htmlspecialchars($searchQuery) ?>"</p>
                <div class="grid">
                    <?php foreach ($results as $row): ?>
                        <div class="card">
                            <h3><?= htmlspecialchars($row['mot']) ?></h3>
                            <p><strong>Signification :</strong> <?= htmlspecialchars($row['signification'] ?? 'N/A') ?></p>
                            <?php if($row['exemple']): ?>
                                <small><em>Ex : <?= htmlspecialchars($row['exemple']) ?></em></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="welcome">Entrez une rime pour commencer la recherche.</p>
            <?php endif; ?>
        </main>
    </div>
	<script src="js/app.js"></script>
</body>
</html>