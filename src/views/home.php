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
            
            <form action="index.php" method="GET" class="search-form">
                <input type="text" 
                       name="q" 
                       id="searchInput"
                       placeholder="Entrez une terminaison (ex: 'un')..." 
                       value="<?= htmlspecialchars($searchQuery) ?>"
                       autocomplete="off">
                <button type="submit">Rechercher</button>
            </form>
        </header>

        <?php if (!empty($searchQuery) && !empty($results)): ?>
            <div class="actions" style="text-align: right; margin-bottom: 20px;">
                <a href="download_pdf.php?q=<?= urlencode($searchQuery) ?>" class="btn-pdf" style="text-decoration: none; color: var(--primary-color); font-weight: bold;">
                    📄 Télécharger ces <?= count($results) ?> rimes en PDF
                </a>
            </div>
        <?php endif; ?>

        <main>
            <?php if (!empty($searchQuery)): ?>
                <p class="stats" style="margin-bottom: 20px; color: var(--text-muted);">
                    <strong><?= count($results) ?></strong> résultat(s) pour la rime "<strong><?= htmlspecialchars($searchQuery) ?></strong>"
                </p>

                <div class="grid">
                    <?php foreach ($results as $row): ?>
                        <div class="card">
                            <h3><?= htmlspecialchars($row['mot']) ?></h3>
                            
                            <span class="rime"><?= htmlspecialchars($row['rime']) ?></span>
                            
                            <p class="signification">
                                <strong>Signification :</strong><br>
                                <?= htmlspecialchars($row['signification'] ?? 'Aucune définition renseignée.') ?>
                            </p>

                            <?php if(!empty($row['exemple'])): ?>
                                <div class="word-example" style="margin-top: 15px; font-size: 0.9rem; border-left: 2px solid var(--accent-color); padding-left: 10px;">
                                    <small><em><strong>Ex :</strong> <?= htmlspecialchars($row['exemple']) ?></em></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="welcome-box" style="text-align: center; padding: 40px; background: white; border-radius: 12px; box-shadow: var(--shadow);">
                    <p class="welcome">Entrez une rime dans la barre ci-dessus pour explorer le dictionnaire.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="js/app.js"></script>
</body>
</html>