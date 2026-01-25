<?php
/**
 * CLASSE RhymeEngine
 * Moteur de recherche principal pour le dictionnaire de rimes.
 * Gère les connexions SQLite et les requêtes de recherche avancée.
 */

class RhymeEngine {
    private $pdo;

    public function __construct() {
        try {
            $dbPath = __DIR__ . '/../data/dico.sqlite'; 
            $this->pdo = new PDO("sqlite:" . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base : " . $e->getMessage());
        }
    }

    /**
     * Retourne l'instance PDO pour les opérations externes
     */
    public function getPDO() { 
        return $this->pdo; 
    }

    /**
     * Recherche avancée avec filtres, tri et gestion des variantes
     */
    public function searchAdvanced($params) {
        $sql = "SELECT * FROM rimes WHERE 1=1";
        $binds = [];

        // Filtre de recherche textuelle
        if (!empty($params['q'])) {
            $sql .= " AND (mot LIKE :q OR rime LIKE :q OR signification LIKE :q OR exemple LIKE :q)";
            $binds['q'] = '%' . $params['q'] . '%';
        }

        // Configuration du tri
        $allowedSort = ['mot', 'rime', 'created_at', 'famille'];
        $sort = in_array($params['sort'] ?? '', $allowedSort) ? $params['sort'] : 'created_at';
        $order = (isset($params['order']) && strtolower($params['order']) === 'asc') ? 'ASC' : 'DESC';
        
        /**
         * LOGIQUE DES VARIANTES DANS LE TRI :
         * Si on trie par "mot", on ajoute un sous-tri par "variante" 
         * pour que Awal (1) soit avant Awal (2).
         */
        if ($sort === 'mot') {
            $sql .= " ORDER BY mot $order, variante ASC";
        } else {
            $sql .= " ORDER BY $sort $order";
        }

        // Limitation des résultats
        if (!empty($params['limit']) && $params['limit'] !== 'all') {
            $limit = (int)$params['limit'];
            $sql .= " LIMIT $limit";
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($binds);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur SQL RhymeEngine : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Recherche par suffixe (rime pure)
     */
    public function searchByRhyme($suffix) {
        if (empty($suffix)) return [];
        // On trie par mot puis par variante pour la cohérence
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE mot LIKE :suffix ORDER BY mot ASC, variante ASC");
        $stmt->execute(['suffix' => '%' . $suffix]);
        return $stmt->fetchAll();
    }

    /**
     * Recherche simple de mot (autocomplétion ou recherche directe)
     */
    public function searchWord($query) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE mot LIKE :query ORDER BY mot ASC, variante ASC LIMIT 50");
        $stmt->execute(['query' => $query . '%']);
        return $stmt->fetchAll();
    }
}