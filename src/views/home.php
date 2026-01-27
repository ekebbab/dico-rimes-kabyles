<!DOCTYPE html>
<html lang="ber">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amawal - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --amawal-blue: #2c3e50;
            --amawal-orange: #e67e22;
            --amawal-bg: #f4f7f6;
            --char-bg: #374151;
            --char-text: #ffee58;
            --char-border: #525c69;
        }

        body { background-color: var(--amawal-bg); color: var(--amawal-blue); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.5; }

        .search-area { background: #ffffff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 20px; }

        /* --- BLOC RECHERCHE --- */
        .search-form-container { display: flex; width: 100%; margin: 0 auto; align-items: stretch; }
        
        /* Conteneur pour Input + Clavier (Flex vertical pour pousser le contenu) */
        .input-wrapper { 
            flex-grow: 1; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }

        .input-wrapper input { 
            width: 100%; padding: 15px 20px; border: 2px solid #ddd; 
            border-radius: 10px 0 0 10px; font-size: 1.1rem; outline: none; 
            box-sizing: border-box;
        }

        /* Bouton Effacer Carré */
        .btn-clear {
            background: #f1f5f9; border: 2px solid #ddd; border-left: none;
            width: 44px; height: 56px; /* Aligné sur la hauteur de l'input */
            margin: 0 10px; border-radius: 8px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: all 0.2s ease-in-out;
        }
        .btn-clear:hover { transform: scale(1.05); background: #fff1f2; border-color: #fecaca; }

        /* CROIX STYLISÉE */
        .icon-cross { position: relative; width: 20px; height: 20px; display: block; }
        .cross-line1, .cross-line2 {
            position: absolute; top: 50%; left: 50%; width: 20px; height: 4px;
            background-color: #7f8c8d; border-radius: 10px;
        }
        .cross-line1 { transform: translate(-50%, -50%) rotate(45deg); }
        .cross-line2 { transform: translate(-50%, -50%) rotate(-45deg); }
        .btn-clear:hover .cross-line1, .btn-clear:hover .cross-line2 { background-color: #e11d48; }

        /* Bouton Chercher */
        .btn-search-group {
            background: var(--amawal-orange); border: 2px solid var(--amawal-orange); border-radius: 10px;
            padding: 8px 25px; cursor: pointer; display: flex; flex-direction: column; 
            align-items: center; justify-content: center; min-width: 140px; height: 56px;
            transition: all 0.2s ease-in-out;
        }
        .btn-search-group:hover { transform: scale(1.02); background: #d35400; }
        .btn-text-main { color: white; font-weight: 700; font-size: 1rem; }
        .btn-text-sub { color: #fdf2f2; font-style: italic; font-size: 0.8rem; font-weight: 400; }

        /* --- CLAVIER RESPONSIVE (Pousse le contenu vers le bas) --- */
        .special-chars-keyboard {
            display: flex; flex-wrap: wrap; justify-content: center;
            background: #fff; border: 1px solid #ddd; border-top: none; 
            border-radius: 0 0 8px 8px; padding: 2px; gap: 1px;
            width: fit-content; 
            z-index: 10;
        }

        .char-column { display: flex; flex-direction: column; gap: 1px; }
        
        .char-key {
            background: var(--char-bg); color: var(--char-text); 
            border: 0.5px solid var(--char-border); 
            width: 22px !important; height: 22px !important; min-width: 22px !important;
            padding: 0 !important; margin: 0 !important;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 900; cursor: pointer; 
            border-radius: 2px; box-sizing: border-box;
            transition: all 0.1s ease-in-out;
        }
        .char-key:hover { background: #000; color: #fff; transform: scale(1.1); }

        /* --- GRILLES (Toujours visibles, ne se cachent plus) --- */
        .search-grid { 
            display: grid; grid-template-columns: repeat(3, 1fr); 
            gap: 20px; margin-top: 25px; width: 100%; 
        }

        .sort-engine { 
            display: grid; grid-template-columns: 1.5fr 1fr 1fr 0.8fr; 
            background: #e2e8f0; padding: 15px 20px; border-radius: 12px; margin: 25px 0;
            gap: 20px; align-items: end; width: 100%; 
        }

        .filter-group label, .sort-engine label { font-size: 0.7rem; font-weight: 800; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .filter-group select, .sort-engine select { width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #edf2f7; background: #f8fafc; font-size: 0.9rem; }

        @media (max-width: 768px) {
            .search-grid, .sort-engine { grid-template-columns: 1fr; }
            .search-form-container { flex-wrap: wrap; gap: 10px; }
            .btn-search-group { width: 100%; border-radius: 10px; }
            .input-wrapper input { border-radius: 10px; }
            .btn-clear { margin: 0; border-left: 2px solid #ddd; border-radius: 10px; }
        }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 15px; border: 1px solid #eef2f7; transition: 0.3s; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.06); }
        .word-title { font-size: 1.4rem; font-weight: 800; color: var(--amawal-blue); margin: 0; }
        .rime-tag { background: #fff3e0; color: #e65100; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container" style="max-width: 1100px;">
        <header class="text-center" style="margin: 40px 0 30px;">
            <h1 style="font-size: 3rem; font-weight: 900; color: var(--amawal-blue); letter-spacing: -2px; margin:0;">AMAWAL</h1>
            <p style="opacity: 0.7; font-size: 1.1rem; font-weight: 500;">Dictionnaire linguistique Taqbaylit</p>
        </header>

        <section class="search-area">
            <form action="index.php" method="GET" id="searchForm">
                <div class="search-form-container">
                    <div class="input-wrapper">
                        <input type="text" id="mainInput" name="q" value="<?= htmlspecialchars($params['q'] ?? '') ?>" autocomplete="off" autofocus>
                        
                        <div class="special-chars-keyboard">
                            <?php
                            $pairs = [['Č', 'č'], ['Ḍ', 'ḍ'], ['Ɛ', 'ɛ'], ['Ǧ', 'ǧ'], ['Ḥ', 'ḥ'], ['Ɣ', 'ɣ'], ['Ṛ', 'ṛ'], ['Ṣ', 'ṣ'], ['Ṭ', 'ṭ'], ['Ẓ', 'ẓ']];
                            foreach ($pairs as $pair): ?>
                                <div class="char-column">
                                    <button type="button" class="char-key" onclick="insertChar('<?= $pair[0] ?>')"><?= $pair[0] ?></button>
                                    <button type="button" class="char-key" onclick="insertChar('<?= $pair[1] ?>')"><?= $pair[1] ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-clear" onclick="clearSearch()" title="Effacer">
                        <div class="icon-cross">
                            <div class="cross-line1"></div>
                            <div class="cross-line2"></div>
                        </div>
                    </button>

                    <button type="submit" class="btn-search-group">
                        <span class="btn-text-main">Chercher</span>
                        <span class="btn-text-sub">Nnadi</span>
                    </button>
                </div>

                <div class="search-grid">
                    <div class="filter-group">
                        <label>Cible</label>
                        <select name="type">
                            <option value="all" <?= ($params['type'] ?? '') == 'all' ? 'selected' : '' ?>>Tout le dictionnaire</option>
                            <option value="kabyle" <?= ($params['type'] ?? '') == 'kabyle' ? 'selected' : '' ?>>Mots & Rimes</option>
                            <option value="francais" <?= ($params['type'] ?? '') == 'francais' ? 'selected' : '' ?>>Définitions</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Lettre Pivot</label>
                        <select name="lettre" id="familleSelect" onchange="updateSubCats()">
                            <option value="">-- Toutes --</option>
                            <?php foreach(array_keys($familles) as $f): ?>
                                <option value="<?= $f ?>" <?= ($params['lettre'] ?? '') == $f ? 'selected' : '' ?>><?= $f ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Terminaison</label>
                        <select name="rime" id="sousFamilleSelect">
                            <option value="">-- Toutes les rimes --</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($results)): ?>
                <div class="results-header">
                    <div class="results-count">Résultats (<?= count($results) ?>)</div>
                </div>

                <div class="sort-engine">
                    <div>
                        <label>Trier par</label>
                        <select name="sort" onchange="this.form.submit()">
                            <option value="mot" <?= $params['sort'] == 'mot' ? 'selected' : '' ?>>Mot (Alphabet)</option>
                            <option value="signification" <?= $params['sort'] == 'signification' ? 'selected' : '' ?>>Signification</option>
                            <option value="lettre" <?= $params['sort'] == 'lettre' ? 'selected' : '' ?>>Lettre Pivot</option>
                            <option value="rime" <?= $params['sort'] == 'rime' ? 'selected' : '' ?>>Rime</option>
                            <option value="classe_grammaticale" <?= $params['sort'] == 'classe_grammaticale' ? 'selected' : '' ?>>Classe Grammaticale</option>
                            <option value="genre" <?= $params['sort'] == 'genre' ? 'selected' : '' ?>>Genre</option>
                            <option value="nombre" <?= $params['sort'] == 'nombre' ? 'selected' : '' ?>>Nombre</option>
                            <option value="updated_at" <?= $params['sort'] == 'updated_at' ? 'selected' : '' ?>>Date</option>
                        </select>
                    </div>
                    <div>
                        <label>Ordre</label>
                        <select name="order" onchange="this.form.submit()">
                            <option value="asc" <?= $params['order'] == 'asc' ? 'selected' : '' ?>>Croissant ↑</option>
                            <option value="desc" <?= $params['order'] == 'desc' ? 'selected' : '' ?>>Décroissant ↓</option>
                        </select>
                    </div>
                    <div>
                        <label>Affichage</label>
                        <select name="limit" onchange="this.form.submit()">
                            <option value="10" <?= $params['limit'] == '10' ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= $params['limit'] == '20' ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= $params['limit'] == '50' ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $params['limit'] == '100' ? 'selected' : '' ?>>100</option>
                            <option value="all" <?= $params['limit'] == 'all' ? 'selected' : '' ?>>Tout</option>
                        </select>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" style="background:var(--amawal-blue); color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:800; font-size:0.8rem;">Actualiser</button>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </section>

        <main>
            <?php if (!empty($results)): ?>
                <div class="grid">
                    <?php foreach ($results as $row): ?>
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                <h3 class="word-title"><?= htmlspecialchars($row['mot']) ?></h3>
                                <span class="rime-tag"><?= htmlspecialchars($row['rime']) ?></span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <span class="badge" style="background:#ebf4ff; color:#3182ce; padding:4px 10px; border-radius:5px; text-transform:uppercase; font-weight:800; font-size:0.65rem; margin-right:5px;"><?= htmlspecialchars($row['classe_grammaticale']) ?></span>
                                <?php if($row['genre'] && $row['genre'] !== 'N/A'): ?>
                                    <span class="badge" style="background:#faf5ff; color:#805ad5; padding:4px 10px; border-radius:5px; text-transform:uppercase; font-weight:800; font-size:0.65rem;"><?= htmlspecialchars($row['genre']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="color: #4a5568; font-size: 1rem; line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($row['signification'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function insertChar(char) {
            const input = document.getElementById('mainInput');
            const start = input.selectionStart, end = input.selectionEnd, text = input.value;
            input.value = text.substring(0, start) + char + text.substring(end);
            input.focus();
            input.selectionStart = input.selectionEnd = start + 1;
        }
        function clearSearch() {
            const input = document.getElementById('mainInput');
            input.value = '';
            input.focus();
        }
        const rimesData = <?= getRimesJson($familles) ?>;
        const currentRime = "<?= $params['rime'] ?? '' ?>";
        function updateSubCats() {
            const lettre = document.getElementById('familleSelect').value;
            const sfSelect = document.getElementById('sousFamilleSelect');
            if(!sfSelect) return;
            sfSelect.innerHTML = '<option value="">-- Toutes les rimes --</option>';
            if (lettre && rimesData[lettre]) {
                rimesData[lettre].forEach(r => {
                    const selected = (r === currentRime) ? 'selected' : '';
                    sfSelect.innerHTML += `<option value="${r}" ${selected}>${r}</option>`;
                });
            }
        }
        document.addEventListener('DOMContentLoaded', updateSubCats);
    </script>
</body>
</html>