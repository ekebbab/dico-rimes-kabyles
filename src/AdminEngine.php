<?php
/**
 * CLASSE AdminEngine
 * Gère les opérations CRUD sur les rimes et les utilisateurs.
 */

class AdminEngine {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* ==========================================================================
       PARTIE 1 : GESTION DES RIMES ET VARIANTES
       ========================================================================== */

    public function checkWordExists($mot) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rimes WHERE mot = ?");
        $stmt->execute([$mot]);
        return (int)$stmt->fetchColumn();
    }

    public function addWord($data) {
        $stmt = $this->pdo->prepare("SELECT MAX(variante) FROM rimes WHERE mot = ?");
        $stmt->execute([$data['mot']]);
        $lastVariant = (int)$stmt->fetchColumn();
        $newVariant = $lastVariant + 1;

        $data['auteur_id'] = $_SESSION['user_id'];
        $data['variante'] = $newVariant;

        $sql = "INSERT INTO rimes (mot, rime, signification, exemple, famille, auteur_id, variante, created_at) 
                VALUES (:mot, :rime, :signification, :exemple, :famille, :auteur_id, :variante, datetime('now'))";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function deleteWord($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT mot, variante FROM rimes WHERE id = ?");
            $stmt->execute([$id]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) return false;

            $mot = $target['mot'];
            $varianteSupprimee = (int)$target['variante'];

            $del = $this->pdo->prepare("DELETE FROM rimes WHERE id = ?");
            $del->execute([$id]);

            $update = $this->pdo->prepare("UPDATE rimes SET variante = variante - 1 WHERE mot = ? AND variante > ?");
            return $update->execute([$mot, $varianteSupprimee]);
        } catch (PDOException $e) {
            error_log("Erreur suppression : " . $e->getMessage());
            return false;
        }
    }
    
    public function getWordById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateWord($id, $data) {
        $data['id'] = $id;
        $sql = "UPDATE rimes SET mot = :mot, rime = :rime, signification = :signification, exemple = :exemple, famille = :famille, updated_at = datetime('now') WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function getWordsByUser($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE auteur_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==========================================================================
       PARTIE 2 : GESTION DES UTILISATEURS
       ========================================================================== */

    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mise à jour polyvalente (Profil perso ou Admin)
     */
    public function updateUser($id, $data) {
        // Liste des champs de base
        $fields = "prenom = :prenom, nom = :nom, email = :email, updated_at = datetime('now')";
        
        $params = [
            'prenom' => $data['prenom'],
            'nom'    => $data['nom'],
            'email'  => $data['email'],
            'id'     => $id
        ];

        // Ajout dynamique du rôle (si présent dans les données envoyées par l'admin)
        if (isset($data['role'])) {
            $fields .= ", role = :role";
            $params['role'] = $data['role'];
        }

        // Ajout dynamique du username
        if (isset($data['username'])) {
            $fields .= ", username = :username";
            $params['username'] = $data['username'];
        }

        // Hachage du mot de passe s'il est fourni
        if (!empty($data['password'])) {
            $fields .= ", password = :password";
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql = "UPDATE users SET $fields WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erreur updateUser : " . $e->getMessage());
            return false;
        }
    }
}