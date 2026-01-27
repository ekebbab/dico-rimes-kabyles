<?php
/**
 * BARRE DE NAVIGATION - VERSION LINGUISTIQUE 2026 RESPONSIVE
 * Correction : Affichage intégral de tous les liens sur mobile
 */
require_once __DIR__ . '/../Auth.php';

// Couleurs thématiques
$navBg = '#2c3e50';
$accent = '#e67e22';
?>
<style>
    .nav-main { 
        background: <?= $navBg ?>; 
        padding: 0.8rem 0; 
        margin-bottom: 2rem; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        border-bottom: 3px solid <?= $accent ?>; 
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    .nav-container { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        max-width: 1100px; 
        margin: 0 auto; 
        padding: 0 20px; 
    }
    
    .nav-brand { font-size: 1.2rem; font-weight: bold; color: white !important; text-decoration: none; display: flex; align-items: center; gap: 8px; }
    
    /* Conteneur global des menus */
    .nav-menu-wrapper { display: flex; align-items: center; gap: 30px; }
    .nav-group { display: flex; align-items: center; gap: 20px; }
    
    .nav-item { color: #ecf0f1; text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
    .nav-item:hover { color: <?= $accent ?>; }
    
    .online-indicator { width: 8px; height: 8px; background: #27ae60; border-radius: 50%; display: inline-block; border: 2px solid white; }
    
    .btn-nav-auth { padding: 6px 16px; border-radius: 20px; font-weight: bold; text-decoration: none; font-size: 0.9rem; transition: all 0.2s; white-space: nowrap; }
    .btn-login { color: white; background: <?= $accent ?>; }
    .btn-login:hover { background: #d35400; transform: translateY(-1px); }
    .btn-logout { color: #e74c3c; border: 1px solid #e74c3c; }
    .btn-logout:hover { background: #e74c3c; color: white; }
    
    .user-profile-link { background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 8px; }

    /* --- RESPONSIVE : HAMBURGER --- */
    .menu-toggle {
        display: none;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        background: none;
        border: none;
    }
    .menu-toggle span { display: block; width: 25px; height: 3px; background: white; border-radius: 2px; transition: 0.3s; }

    @media (max-width: 950px) {
        .menu-toggle { display: flex; }
        
        /* On transforme le wrapper en menu déroulant */
        .nav-menu-wrapper {
            display: none; /* Masqué par défaut */
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: <?= $navBg ?>;
            padding: 20px;
            gap: 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            box-sizing: border-box;
        }

        .nav-menu-wrapper.active { display: flex; }

        .nav-group { flex-direction: column; width: 100%; gap: 15px; }
        .nav-item { font-size: 1.1rem; width: 100%; justify-content: center; }
        .user-profile-link { width: auto; }
        .btn-nav-auth { width: 100%; text-align: center; }
    }
</style>

<nav class="nav-main">
    <div class="nav-container">
        
        <a href="index.php" class="nav-brand">
            <span style="font-size: 1.4rem;">📚</span> Dico Kabyle
        </a>

        <button class="menu-toggle" onclick="toggleMobileMenu()" id="hamburger">
            <span></span><span></span><span></span>
        </button>
        
        <div class="nav-menu-wrapper" id="navMenu">
            
            <div class="nav-group">
                <a href="stats.php" class="nav-item">
                    <span>📉</span> Statistiques
                </a>

                <?php if (Auth::isLogged()): ?>
                    <a href="admin.php" class="nav-item">
                        <span>📊</span> Tableau de bord
                    </a>
                    <a href="add_word.php" class="nav-item">
                        <span>➕</span> Ajouter
                    </a>
                <?php endif; ?>
            </div>

            <div class="nav-group">
                <?php if (Auth::isLogged()): ?>
                    <a href="profile.php" class="nav-item user-profile-link">
                        <span class="online-indicator" title="En ligne"></span>
                        <span style="color: #bdc3c7;">👤</span>
                        <strong style="color: white;"><?= htmlspecialchars($_SESSION['username'] ?? 'Profil') ?></strong>
                    </a>
                    <a href="logout.php" class="btn-nav-auth btn-logout">Déconnexion</a>
                <?php else: ?>
                    <a href="register.php" class="nav-item" style="color: #27ae60;">✨ Inscription</a>
                    <a href="login.php" class="btn-nav-auth btn-login">Connexion</a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</nav>

<script>
    // 1. Activer le lien courant
    document.querySelectorAll('.nav-item').forEach(link => {
        if(link.href === window.location.href) {
            link.style.color = '<?= $accent ?>';
        }
    });

    // 2. Gestion du menu mobile (TOUS les liens)
    function toggleMobileMenu() {
        const menu = document.getElementById('navMenu');
        const spans = document.querySelectorAll('#hamburger span');
        
        menu.classList.toggle('active');
        
        if(menu.classList.contains('active')) {
            spans[0].style.transform = 'rotate(45deg) translate(5px, 6px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(5px, -6px)';
        } else {
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    }
</script>