<?php
/**
 * CLASSE AdminEngine
 * Version : Linguistique Avancée & Gestion Utilisateurs Complète
 * Synchronisée avec les tables 'rimes' et 'users' (v2026)
 */

class AdminEngine {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* ==========================================================================
       PARTIE 1 : GESTION DES RIMES ET VARIANTES (Table: rimes)
       ========================================================================== */

    /**
     * Vérifie si un mot existe déjà (pour éviter les doublons accidentels)
     */
    public function checkWordExists($mot) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rimes WHERE mot = ?");
        $stmt->execute([$mot]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Ajout d'un mot avec gestion linguistique et variantes
     */
    public function addWord($data) {
        // Détermination du numéro de variante
        $stmt = $this->pdo->prepare("SELECT MAX(variante) FROM rimes WHERE mot = ?");
        $stmt->execute([$data['mot']]);
        $lastVariant = (int)$stmt->fetchColumn();
        
        $data['auteur_id'] = $_SESSION['user_id'] ?? null;
        $data['variante']  = $lastVariant + 1;

        $sql = "INSERT INTO rimes (
                    mot, signification, exemple, lettre, rime, 
                    classe_grammaticale, genre, nombre, 
                    auteur_id, variante, created_at, updated_at
                ) VALUES (
                    :mot, :signification, :exemple, :lettre, :rime, 
                    :classe_grammaticale, :genre, :nombre, 
                    :auteur_id, :variante, datetime('now'), datetime('now')
                )";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'mot'                 => $data['mot'],
            'signification'       => $data['signification'],
            'exemple'             => $data['exemple'],
            'lettre'              => $data['lettre'],
            'rime'                => $data['rime'],
            'classe_grammaticale' => $data['classe_grammaticale'],
            'genre'               => $data['genre'],
            'nombre'              => $data['nombre'],
            'auteur_id'           => $data['auteur_id'],
            'variante'            => $data['variante']
        ]);
    }

    /**
     * Mise à jour d'un mot existant
     */
    public function updateWord($id, $data) {
        $sql = "UPDATE rimes SET 
                    mot = :mot, 
                    signification = :signification, 
                    exemple = :exemple, 
                    lettre = :lettre, 
                    rime = :rime, 
                    classe_grammaticale = :classe_grammaticale, 
                    genre = :genre, 
                    nombre = :nombre, 
                    updated_at = datetime('now'),
                    version = version + 1
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'mot'                 => $data['mot'],
            'signification'       => $data['signification'],
            'exemple'             => $data['exemple'],
            'lettre'              => $data['lettre'],
            'rime'                => $data['rime'],
            'classe_grammaticale' => $data['classe_grammaticale'],
            'genre'               => $data['genre'],
            'nombre'              => $data['nombre'],
            'id'                  => $id
        ]);
    }

    /**
     * Suppression et réorganisation automatique des variantes
     */
    public function deleteWord($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT mot, variante FROM rimes WHERE id = ?");
            $stmt->execute([$id]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) return false;

            $mot = $target['mot'];
            $varianteSupprimee = (int)$target['variante'];

            $this->pdo->prepare("DELETE FROM rimes WHERE id = ?")->execute([$id]);

            // Renumérotation pour boucher le "trou" dans les variantes
            $update = $this->pdo->prepare("UPDATE rimes SET variante = variante - 1 WHERE mot = ? AND variante > ?");
            return $update->execute([$mot, $varianteSupprimee]);
        } catch (PDOException $e) {
            error_log("Erreur suppression rime : " . $e->getMessage());
            return false;
        }
    }

    public function getWordById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getWordsByUser($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE auteur_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==========================================================================
       PARTIE 2 : GESTION DES UTILISATEURS (Table: users)
       ========================================================================== */

    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mise à jour profil/admin avec support des nouveaux champs (is_active, role)
     */
    public function updateUser($id, $data) {
        $fields = "prenom = :prenom, nom = :nom, email = :email, updated_at = datetime('now')";
        $params = [
            'prenom' => $data['prenom'], 
            'nom'    => $data['nom'], 
            'email'  => $data['email'], 
            'id'     => $id
        ];

        // Rôle (Admin uniquement)
        if (isset($data['role'])) { 
            $fields .= ", role = :role"; 
            $params['role'] = $data['role']; 
        }
        
        // Statut d'activation (Admin uniquement)
        if (isset($data['is_active'])) { 
            $fields .= ", is_active = :is_active"; 
            $params['is_active'] = (int)$data['is_active']; 
        }

        if (isset($data['username'])) { 
            $fields .= ", username = :username"; 
            $params['username'] = $data['username']; 
        }

        // Changement de mot de passe (si rempli)
        if (!empty($data['password'])) { 
            $fields .= ", password = :password"; 
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT); 
        }

        $sql = "UPDATE users SET $fields WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Mise à jour du tracking de dernière activité
     */
    public function updateLastSeen($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_seen = datetime('now') WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}