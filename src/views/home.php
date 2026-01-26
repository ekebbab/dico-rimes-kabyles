<!DOCTYPE html>
<html lang="ber">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dico Kabyle des rimes</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* --- DESIGN DU MOTEUR DE RECHERCHE AVANCÉ --- */
        .search-area {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .special-chars {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .char-btn {
            padding: 8px 14px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.2s;
        }
        .char-btn:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .search-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .filter-group { display: flex; flex-direction: column; text-align: left; }
        .filter-group label { 
            font-size: 0.8rem; 
            font-weight: bold; 
            margin-bottom: 5px; 
            color: #666; 
            text-transform: uppercase;
        }
        .filter-group select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: #fff;
        }

        /* --- AFFICHAGE DES RÉSULTATS --- */
        .variante-num {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: normal;
            margin-left: 5px;
            font-style: italic;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border-top: 5px solid var(--accent-color);
            transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-5px); }
        .rime-badge {
            display: inline-block;
            background: #fdf2e9;
            color: var(--accent-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        .category-info {
            font-size: 0.75rem;
            color: #999;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        @media (max-width: 768px) {
            .search-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container">
        <header class="text-center" style="margin-top: 30px;">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Dico Kabyle des rimes</h1>
            <p style="color: #666; margin-bottom: 30px;">Explorez la richesse de la langue et de la poésie kabyle.</p>
            
            <div class="search-area">
                <div class="special-chars">
                    <?php 
                    $chars = ['č','ḍ','ḥ','ṣ','ṭ','z','ž','Ɛ','Č','Ḍ','Ḥ','Ṣ','Ṭ','Z','Ž'];
                    foreach($chars as $c): ?>
                        <button type="button" class="char-btn" onclick="addChar('<?= $c ?>')"><?= $c ?></button>
                    <?php endforeach; ?>
                </div>

                <form action="index.php" method="GET" id="searchForm">
                    <div class="search-form" style="display: flex; gap: 10px;">
                        <input type="text" 
                            id="mainInput"
                            name="q" 
                            placeholder="Saisissez un mot, une rime, un sens..." 
                            value="<?= htmlspecialchars($params['q'] ?? '') ?>"
                            style="flex-grow: 1; font-size: 1.2rem; padding: 15px;"
                            autofocus>
                        <button type="submit" style="padding: 0 30px;">Rechercher</button>
                    </div>

                    <div class="search-grid">
                        <div class="filter-group">
                            <label>Rechercher par :</label>
                            <select name="type">
                                <option value="all" <?= ($params['type'] ?? '') == 'all' ? 'selected' : '' ?>>Automatique (Tout)</option>
                                <option value="kabyle" <?= ($params['type'] ?? '') == 'kabyle' ? 'selected' : '' ?>>Kabyle (Taqbaylit)</option>
                                <option value="francais" <?= ($params['type'] ?? '') == 'francais' ? 'selected' : '' ?>>Français (Tafransiste)</option>
                                <option value="exemple" <?= ($params['type'] ?? '') == 'exemple' ? 'selected' : '' ?>>Exemple (Amedya)</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Famille :</label>
                            <select name="famille" id="familleSelect" onchange="updateSubCats()">
                                <option value="">-- Toutes --</option>
                                <option value="Nom" <?= ($params['famille'] ?? '') == 'Nom' ? 'selected' : '' ?>>Nom (Issem)</option>
                                <option value="Verbe" <?= ($params['famille'] ?? '') == 'Verbe' ? 'selected' : '' ?>>Verbe (Amyag)</option>
                                <option value="Adjectif" <?= ($params['famille'] ?? '') == 'Adjectif' ? 'selected' : '' ?>>Adjectif (Arbib)</option>
                                <option value="Autre" <?= ($params['famille'] ?? '') == 'Autre' ? 'selected' : '' ?>>Autre (Ayen nnidhen)</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Sous-famille :</label>
                            <select name="sous_famille" id="sousFamilleSelect">
                                <option value="">-- Toutes --</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </header>

        <main>
            <?php if (!empty($params['q']) || !empty($params['famille'])): ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <p class="stats">
                        <strong><?= count($results) ?></strong> résultat(s) trouvé(s).
                    </p>
                    <?php if (!empty($results)): ?>
                    <a href="export_pdf.php?<?= http_build_query($params) ?>" class="btn-pdf" target="_blank" style="text-decoration: none; font-size: 0.9rem;">
                        📄 Télécharger ces rimes en PDF
                    </a>
                    <?php endif; ?>
                </div>

                <div class="grid">
                    <?php foreach ($results as $row): ?>
                        <div class="card">
                            <div class="category-info">
                                <?= htmlspecialchars($row['famille']) ?> 
                                <?= !empty($row['sous_famille']) ? ' > '.htmlspecialchars($row['sous_famille']) : '' ?>
                            </div>

                            <h3>
                                <?= htmlspecialchars($row['mot']) ?>
                                <?php if(isset($row['variante']) && $row['variante'] > 1): ?>
                                    <span class="variante-num">(v<?= $row['variante'] ?>)</span>
                                <?php endif; ?>
                            </h3>
                            
                            <span class="rime-badge">Rime en : <?= htmlspecialchars($row['rime']) ?></span>
                            
                            <p class="signification">
                                <strong>Signification :</strong><br>
                                <?= nl2br(htmlspecialchars($row['signification'] ?? 'Aucune définition.')) ?>
                            </p>

                            <?php if(!empty($row['exemple'])): ?>
                                <div class="word-example" style="margin-top: 15px; font-size: 0.9rem; border-left: 3px solid var(--accent-color); padding: 10px; background: #fcfcfc;">
                                    <small><em><strong>Ex :</strong> <?= htmlspecialchars($row['exemple']) ?></em></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="welcome-box text-center" style="padding: 50px 0;">
                    <img src="img/search-illustration.svg" alt="" style="max-width: 200px; opacity: 0.5; margin-bottom: 20px;">
                    <p style="font-size: 1.2rem; color: #888;">Utilisez les filtres ou la barre de recherche pour commencer votre exploration.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // 1. Insertion des caractères spéciaux
        function addChar(c) {
            const input = document.getElementById('mainInput');
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const text = input.value;
            input.value = text.substring(0, start) + c + text.substring(end);
            input.focus();
            input.setSelectionRange(start + 1, start + 1);
        }

        // 2. Gestion dynamique des sous-familles
        const subCats = {
            'Nom': ['Masculin', 'Féminin', 'Pluriel'],
            'Verbe': ['État', 'Action', 'Qualité'],
            'Adjectif': ['Simple', 'Dérivé'],
            'Autre': ['Adverbe', 'Particule', 'Préposition']
        };

        function updateSubCats() {
            const fam = document.getElementById('familleSelect').value;
            const sfSelect = document.getElementById('sousFamilleSelect');
            const currentSf = "<?= $params['sous_famille'] ?? '' ?>";

            sfSelect.innerHTML = '<option value="">-- Toutes --</option>';
            
            if (subCats[fam]) {
                subCats[fam].forEach(sf => {
                    const isSelected = (sf === currentSf) ? 'selected' : '';
                    sfSelect.innerHTML += `<option value="${sf}" ${isSelected}>${sf}</option>`;
                });
            }
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', updateSubCats);
    </script>
</body>
</html>