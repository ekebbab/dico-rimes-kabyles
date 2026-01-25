<?php
/**
 * CLASSE RhymeEngine
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

    public function getPDO() { 
        return $this->pdo; 
    }

    public function searchAdvanced($params) {
        $sql = "SELECT * FROM rimes WHERE 1=1";
        $binds = [];

        if (!empty($params['q'])) {
            $sql .= " AND (mot LIKE :q OR rime LIKE :q OR signification LIKE :q OR exemple LIKE :q)";
            $binds['q'] = '%' . $params['q'] . '%';
        }

        $allowedSort = ['mot', 'rime', 'created_at', 'famille'];
        $sort = in_array($params['sort'] ?? '', $allowedSort) ? $params['sort'] : 'created_at';
        $order = (isset($params['order']) && strtolower($params['order']) === 'asc') ? 'ASC' : 'DESC';
        
        $sql .= " ORDER BY $sort $order";

        if (!empty($params['limit']) && $params['limit'] !== 'all') {
            $limit = (int)$params['limit'];
            $sql .= " LIMIT $limit";
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($binds);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage());
            return [];
        }
    }

    public function searchByRhyme($suffix) {
        if (empty($suffix)) return [];
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE mot LIKE :suffix ORDER BY mot ASC");
        $stmt->execute(['suffix' => '%' . $suffix]);
        return $stmt->fetchAll();
    }

    public function searchWord($query) {
        $stmt = $this->pdo->prepare("SELECT * FROM rimes WHERE mot LIKE :query LIMIT 50");
        $stmt->execute(['query' => $query . '%']);
        return $stmt->fetchAll();
    }
}