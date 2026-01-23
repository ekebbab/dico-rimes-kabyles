// public/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const resultsGrid = document.querySelector('.grid');
    const statsContainer = document.querySelector('.stats');

    if (!searchInput) return;

    // --- 1. Écouteur pour la recherche Live ---
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        if (query.length >= 1) {
            fetch(`api.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    updateUI(data, query);
                })
                .catch(error => console.error('Erreur:', error));
        }
    });

    // --- 2. Écouteur pour le clic sur les boutons de copie (Délégation d'événement) ---
    document.addEventListener('click', (e) => {
        // On vérifie si on a cliqué sur le bouton ou sur l'icône à l'intérieur
        const copyBtn = e.target.closest('.btn-copy');
        if (copyBtn) {
            const word = copyBtn.getAttribute('data-word');
            copyToClipboard(word, copyBtn);
        }
    });

    // --- 3. Logique de copie ---
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(() => {
            const originalContent = button.innerHTML;
            button.innerHTML = "✅";
            button.classList.add('copied');
            
            setTimeout(() => {
                button.innerHTML = originalContent;
                button.classList.remove('copied');
            }, 1500);
        });
    }

    // --- 4. Mise à jour de l'interface ---
    function updateUI(results, query) {
        if (statsContainer) {
            statsContainer.innerHTML = `<strong>${results.length}</strong> résultat(s) pour la rime "<strong>${query}</strong>"`;
        }

        if (results.length > 0) {
            resultsGrid.innerHTML = results.map(row => `
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h3 style="margin:0;">${escapeHtml(row.mot)}</h3>
                        <button class="btn-copy" data-word="${escapeHtml(row.mot)}" title="Copier le mot">
                            📋
                        </button>
                    </div>
                    
                    <span class="rime">${escapeHtml(row.rime)}</span>
                    
                    <p class="signification">
                        <strong>Signification :</strong><br>
                        ${escapeHtml(row.signification || 'Aucune définition renseignée.')}
                    </p>
                    
                    ${row.exemple ? `
                        <div class="word-example" style="margin-top: 15px; font-size: 0.9rem; border-left: 2px solid var(--accent-color); padding-left: 10px;">
                            <small><em><strong>Ex :</strong> ${escapeHtml(row.exemple)}</em></small>
                        </div>
                    ` : ''}
                </div>
            `).join('');
        } else {
            resultsGrid.innerHTML = `<p>Aucun résultat pour "${escapeHtml(query)}".</p>`;
        }
    }

    function escapeHtml(text) {
        if (!text) return "";
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});