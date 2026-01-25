<?php
/**
 * CLASSE AdminEngine
 * Gère les opérations CRUD sur les rimes avec vérification de propriété.
 */

class AdminEngine {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ajouter un mot en liant automatiquement l'utilisateur connecté
     */
    public function addWord($data) {
        // On force l'auteur_id avec l'utilisateur actuellement en session
        $data['auteur_id'] = $_SESSION['user_id'];

        $sql = "INSERT INTO rimes (mot, rime, signification, exemple, famille, auteur_id, created_at) 
                VALUES (:mot, :rime, :signification, :exemple, :famille, :auteur_id, datetime('now'))";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Supprimer un mot (La sécurité est gérée dans admin.php via Auth::canManage)
     */
    public function deleteWord($id) {
        $stmt = $this->pdo->prepare("DELETE FROM rimes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Récupérer un mot spécifique pour l'édition
     */
    public function getWordById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mettre à jour un mot
     */
    public function updateWord($id, $data) {
        // On s'assure que l'ID est bien présent dans les données pour le WHERE
        $data['id'] = $id;

        $sql = "UPDATE rimes SET 
                mot = :mot, 
                rime = :rime, 
                signification = :signification, 
                exemple = :exemple, 
                famille = :famille,
                updated_at = datetime('now')
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Récupérer les rimes d'un utilisateur spécifique (pour son profil)
     */
    public function getWordsByUser($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE auteur_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}