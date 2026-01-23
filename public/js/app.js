// public/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const resultsGrid = document.querySelector('.grid');
    const statsContainer = document.querySelector('.stats');

    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        // On ne déclenche la recherche qu'à partir de 1 caractère
        if (query.length >= 1) {
            fetch(`api.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    updateUI(data, query);
                })
                .catch(error => console.error('Erreur:', error));
        }
    });

    function updateUI(results, query) {
        // 1. Mise à jour des stats
        if (statsContainer) {
            statsContainer.innerHTML = `<strong>${results.length}</strong> résultat(s) pour la rime "<strong>${query}</strong>"`;
        }

        // 2. Génération des cartes
        if (results.length > 0) {
            resultsGrid.innerHTML = results.map(row => `
                <div class="card">
                    <h3>${escapeHtml(row.mot)}</h3>
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

    // Sécurité pour éviter les injections XSS dans le JS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});