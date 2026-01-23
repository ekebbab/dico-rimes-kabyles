<?php
// src/RhymeEngine.php

class RhymeEngine {
    private $pdo;

    public function __construct() {
        try {
            // Chemin vers ta base à la racine (dossier /data)
            $dbPath = __DIR__ . '/../data/dico.sqlite'; 
            $this->pdo = new PDO("sqlite:" . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base : " . $e->getMessage());
        }
    }

    /**
     * Recherche les mots par terminaison (rime)
     */
    public function searchByRhyme($suffix) {
        if (empty($suffix)) return [];
        
        // On cherche les mots qui se terminent par le suffixe
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE mot LIKE :suffix ORDER BY mot ASC");
        $stmt->execute(['suffix' => '%' . $suffix]);
        return $stmt->fetchAll();
    }

    /**
     * Recherche globale (mot exact ou début de mot)
     */
    public function searchWord($query) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE mot LIKE :query LIMIT 50");
        $stmt->execute(['query' => $query . '%']);
        return $stmt->fetchAll();
    }
}