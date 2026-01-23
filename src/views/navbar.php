<?php
// src/views/navbar.php
require_once __DIR__ . '/../Auth.php';
?>
<nav style="background: #2c3e50; padding: 1rem; margin-bottom: 2rem; border-radius: 4px;">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; padding: 0;">
        <div>
            <a href="index.php" style="color: white; font-weight: bold; text-decoration: none; margin-right: 20px;">🏠 Accueil</a>
            
            <?php if (Auth::isLogged()): ?>
                <a href="admin.php" style="color: #ecf0f1; text-decoration: none; margin-right: 15px;">📊 Dashboard</a>
                <a href="add_word.php" style="color: #ecf0f1; text-decoration: none;">➕ Ajouter</a>
            <?php endif; ?>
        </div>

        <div>
            <?php if (Auth::isLogged()): ?>
                <span style="color: #bdc3c7; margin-right: 15px;">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Déconnexion</a>
            <?php else: ?>
                <a href="login.php" style="color: #3498db; text-decoration: none;">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</nav>