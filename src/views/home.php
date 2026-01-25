<!DOCTYPE html>
<html lang="ber">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dico Kabyle des rimes</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Style spécifique pour la variante discrète */
        .variante-num {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: normal;
            margin-left: 5px;
            font-style: italic;
        }
        /* Ajustement de la grille pour l'affichage public */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--accent-color);
        }
        .rime {
            display: inline-block;
            background: #fdf2e9;
            color: var(--accent-color);
            padding: 2px 10px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container">
        <header class="text-center" style="margin-bottom: 40px;">
            <h1>Dico Kabyle des rimes</h1>
            
            <div class="search-container">
                <form action="index.php" method="GET" class="search-form">
                    <input type="text" 
                    name="q" 
                    placeholder="Rechercher une rime..." 
                    value="<?= htmlspecialchars($searchQuery ?? '') ?>"
                    autofocus>
                    <button type="submit">Rechercher</button>
                </form>
            </div>
        </header>

        <?php if (!empty($searchQuery) && !empty($results)): ?>
            <div class="actions" style="text-align: right; margin-bottom: 20px;">
                <a href="download_pdf.php?q=<?= urlencode($searchQuery) ?>" class="btn-pdf">
                    📄 Télécharger ces <?= count($results) ?> rimes en PDF
                </a>
            </div>
        <?php endif; ?>

        <main>
            <?php if (!empty($searchQuery)): ?>
                <p class="stats">
                    <strong><?= count($results) ?></strong> résultat(s) pour la rime "<strong><?= htmlspecialchars($searchQuery) ?></strong>"
                </p>

                <div class="grid">
                    <?php foreach ($results as $row): ?>
                        <div class="card">
                            <h3>
                                <?= htmlspecialchars($row['mot']) ?>
                                <?php if(isset($row['variante']) && $row['variante'] > 1): ?>
                                    <small class="variante-num">(v<?= $row['variante'] ?>)</small>
                                <?php endif; ?>
                            </h3>
                            
                            <span class="rime"><?= htmlspecialchars($row['rime']) ?></span>
                            
                            <p class="signification">
                                <strong>Signification :</strong><br>
                                <?= htmlspecialchars($row['signification'] ?? 'Aucune définition renseignée.') ?>
                            </p>

                            <?php if(!empty($row['exemple'])): ?>
                                <div class="word-example" style="margin-top: 15px; font-size: 0.9rem; border-left: 3px solid var(--accent-color); padding-left: 10px; background: #f9f9f9; padding: 10px;">
                                    <small><em><strong>Ex :</strong> <?= htmlspecialchars($row['exemple']) ?></em></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="welcome-box text-center">
                    <p class="welcome">Entrez une rime dans la barre ci-dessus pour explorer le dictionnaire.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="js/app.js"></script>
</body>
</html>