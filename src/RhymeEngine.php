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
	
	/*pour que Auth.php puisse accéder à la base*/
	public function getPDO() { return $this->pdo; }
	
	
	/**
	 * Recherche avancée adaptée à la table "rimes"
	 */
	public function searchAdvanced($params) {
		// On cible bien ta table "rimes"
		$sql = "SELECT * FROM rimes WHERE 1=1";
		$binds = [];

		// 1. Recherche textuelle multi-colonnes
		if (!empty($params['q'])) {
			$sql .= " AND (mot LIKE :q OR rime LIKE :q OR signification LIKE :q OR exemple LIKE :q)";
			$binds['q'] = '%' . $params['q'] . '%';
		}

		// 2. Tri dynamique sécurisé
		// On adapte les colonnes : "date_ajout" devient "created_at"
		$allowedSort = ['mot', 'rime', 'created_at', 'famille'];
		$sort = in_array($params['sort'], $allowedSort) ? $params['sort'] : 'created_at';
		
		// Gestion de l'ordre
		$order = (isset($params['order']) && strtolower($params['order']) === 'asc') ? 'ASC' : 'DESC';
		
		$sql .= " ORDER BY $sort $order";

		// 3. Limitation
		if (!empty($params['limit']) && $params['limit'] !== 'all') {
			$limit = (int)$params['limit'];
			$sql .= " LIMIT $limit";
		}

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($binds);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}