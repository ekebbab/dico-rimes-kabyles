<?php
// src/AdminEngine.php

class AdminEngine {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Récupérer tous les mots (pour le tableau admin)
    public function getAllWords($limit = 50) {
        $stmt = $this->pdo->query("SELECT * FROM rimes ORDER BY id DESC LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ajouter un mot
    public function addWord($data) {
        $sql = "INSERT INTO rimes (mot, rime, signification, exemple, famille) 
                VALUES (:mot, :rime, :signification, :exemple, :famille)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    // Supprimer un mot
    public function deleteWord($id) {
        $stmt = $this->pdo->prepare("DELETE FROM rimes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    // Récupérer un mot spécifique pour l'édition
    public function getWordById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
	
	public function updateWord($id, $data) {
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
}