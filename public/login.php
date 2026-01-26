<?php
/**
 * PAGE DE CONNEXION
 * Gère l'authentification, la vérification de l'activation du compte et l'initialisation des sessions.
 */
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/RhymeEngine.php';

Auth::init();

// Si déjà connecté, redirection directe vers le dashboard
if (Auth::isLogged()) {
    header('Location: admin.php');
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $engine = new RhymeEngine();
        $db = $engine->getPDO();

        // Récupération de l'utilisateur (On vérifie aussi is_active)
        $stmt = $db->prepare("SELECT id, username, password, role, is_active FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe
        if ($user && password_verify($password, $user['password'])) {
            
            // SÉCURITÉ : Vérifier si le compte est actif
            if ((int)$user['is_active'] !== 1) {
                $error = "votre compte est en attente de validation par un administrateur.";
            } else {
                // Stockage des informations en session
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role']; 

                header('Location: admin.php');
                exit;
            }
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Dico Kabyle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .toggle-eye {
            position: absolute;
            right: 12px;
            cursor: pointer;
            font-size: 1.2rem;
            user-select: none;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .toggle-eye:hover {
            color: var(--accent-color);
        }

        #password {
            padding-right: 45px;
        }

        .register-link {
            display: block;
            margin-bottom: 15px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .register-link:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/navbar.php'; ?>

    <main class="container login-wrapper">
        <section class="login-card">
            <header>
                <h2>Espace Membres</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Connectez-vous pour gérer vos rimes</p>
            </header>
            
            <?php if ($error): ?>
                <div class="error-box" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" placeholder="Pseudo" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <span class="toggle-eye" id="togglePassword">👁️</span>
                    </div>
                </div>

                <button type="submit" class="btn-full">Se connecter</button>
            </form>

            <footer style="margin-top: 25px; text-align: center; border-top: 1px solid #eee; padding-top: 15px;">
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 10px;">Pas encore de compte ?</p>
                <a href="register.php" class="register-link">✨ Rejoindre la Team (S'inscrire)</a>
                
                <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: block; margin-top: 10px;">
                    ← Retour au dictionnaire
                </a>
            </footer>
        </section>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePassword');

        toggleIcon.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>
</body>
</html>