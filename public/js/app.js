// public/js/app.js
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('input[name="q"]');
    const resultsContainer = document.querySelector('.grid');
    const statsContainer = document.querySelector('.stats');

    searchInput.addEventListener('input', async (e) => {
        const query = e.target.value;

        if (query.length < 1) {
            resultsContainer.innerHTML = '';
            statsContainer.textContent = '';
            return;
        }

        try {
            const response = await fetch(`api.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            // Mise à jour des stats
            statsContainer.textContent = `${data.length} résultat(s) pour "${query}"`;

            // Génération des cartes
            resultsContainer.innerHTML = data.map(item => `
                <div class="card">
                    <h3>${item.mot}</h3>
                    <p><strong>Signification :</strong> ${item.signification || 'N/A'}</p>
                    ${item.exemple ? `<small><em>Ex : ${item.exemple}</em></small>` : ''}
                </div>
            `).join('');

        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
        }
    });
});