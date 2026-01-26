<?php
/**
 * BARRE DE NAVIGATION
 * Gère l'affichage dynamique selon le rôle et l'état de connexion.
 */
require_once __DIR__ . '/../Auth.php';

// On s'assure que la session est initialisée pour récupérer le username
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav style="background: #2c3e50; padding: 1rem; margin-bottom: 2rem; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; padding: 0;">
        
        <div style="display: flex; align-items: center;">
            <a href="index.php" style="color: white; font-weight: bold; text-decoration: none; margin-right: 25px; font-size: 1.1rem;">🏠 Accueil</a>
            
            <a href="stats.php" style="color: #ecf0f1; text-decoration: none; margin-right: 20px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">📉 Statistiques</a>

            <?php if (Auth::isLogged()): ?>
                <a href="admin.php" style="color: #ecf0f1; text-decoration: none; margin-right: 20px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">📊 Dashboard</a>
                <a href="add_word.php" style="color: #ecf0f1; text-decoration: none; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">➕ Ajouter</a>
            <?php endif; ?>
        </div>

        <div style="display: flex; align-items: center;">
            <?php if (Auth::isLogged()): ?>
                <a href="profile.php" style="color: #bdc3c7; margin-right: 20px; text-decoration: none; display: flex; align-items: center; transition: color 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='#bdc3c7'">
                    <span style="margin-right: 5px;">👤</span> 
                    <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Mon Compte') ?></strong>
                </a>
                
                <a href="logout.php" style="color: #e74c3c; text-decoration: none; font-weight: bold; padding: 5px 12px; border: 1px solid #e74c3c; border-radius: 20px; transition: all 0.2s;" onmouseover="this.style.background='#e74c3c'; this.style.color='white'" onmouseout="this.style.background='transparent'; this.style.color='#e74c3c'">Déconnexion</a>
            <?php else: ?>
                <a href="register.php" style="color: #27ae60; text-decoration: none; font-weight: bold; margin-right: 20px; transition: color 0.2s;" onmouseover="this.style.color='#2ecc71'" onmouseout="this.style.color='#27ae60'">✨ S'inscrire</a>
                
                <a href="login.php" style="color: #3498db; text-decoration: none; font-weight: bold; padding: 5px 15px; background: rgba(52, 152, 219, 0.1); border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(52, 152, 219, 0.2)'" onmouseout="this.style.background='rgba(52, 152, 219, 0.1)'">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</nav>