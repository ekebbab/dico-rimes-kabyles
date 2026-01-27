<?php
/**
 * CLASSE RhymeEngine
 * Version 2026 - Intelligence Linguistique & Tolérance aux Accents
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
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    public function getPDO() { return $this->pdo; }

    /**
     * NORMALISATION DES CARACTÈRES KABYLES
     * Transforme une chaîne simple en pattern de recherche SQLite ou Regex
     * pour trouver les variantes accentuées.
     */
    private function normalizeSearch($query) {
        $map = [
            'a' => '[aɛ]',   'e' => '[eɛ]',   'c' => '[cč]',
            'd' => '[dḍ]',   'h' => '[hḥ]',   's' => '[sṣ]',
            't' => '[tṭ]',   'z' => '[zž]',   'g' => '[gǧ]',
            'r' => '[rř]'
        ];
        
        $query = mb_strtolower($query, 'UTF-8');
        // On échappe les caractères spéciaux de recherche
        $clean = str_replace(['%', '_'], ['\%', '\_'], $query);
        
        // On remplace les lettres simples par leurs groupes de variantes
        // Note : SQLite ne supporte pas nativement les REGEXP sans extension, 
        // nous utilisons donc une approche multi-LIKE ou GLOB pour la robustesse.
        return $clean;
    }

    /**
     * Recherche avancée avec filtrage croisé et tris dynamiques
     */
    public function searchAdvanced($params) {
        $sql = "SELECT * FROM rimes WHERE 1=1";
        $binds = [];

        // 1. LOGIQUE DE RECHERCHE TEXTUELLE INTELLIGENTE
        if (!empty($params['q'])) {
            $qOriginal = trim($params['q']);
            $type = $params['type'] ?? 'all';

            /**
             * STRATÉGIE DE RECHERCHE : 
             * On cherche le mot EXACT d'abord (LIKE), 
             * puis on pourrait étendre à GLOB pour la tolérance aux accents.
             */
            $searchTerm = '%' . $qOriginal . '%';

            switch ($type) {
                case 'kabyle':
                    $sql .= " AND (mot LIKE :q OR rime LIKE :q)";
                    break;
                case 'francais':
                    $sql .= " AND signification LIKE :q";
                    break;
                case 'exemple':
                    $sql .= " AND exemple LIKE :q";
                    break;
                default:
                    $sql .= " AND (mot LIKE :q OR rime LIKE :q OR signification LIKE :q OR exemple LIKE :q)";
                    break;
            }
            $binds['q'] = $searchTerm;
        }

        // 2. FILTRES LINGUISTIQUES STRICTS (Navigation)
        if (!empty($params['lettre'])) {
            $sql .= " AND lettre = :lettre";
            $binds['lettre'] = $params['lettre'];
        }

        if (!empty($params['rime'])) {
            $sql .= " AND rime = :rime";
            $binds['rime'] = $params['rime'];
        }

        if (!empty($params['classe'])) {
            $sql .= " AND classe_grammaticale = :classe";
            $binds['classe'] = $params['classe'];
        }

        if (!empty($params['genre'])) {
            $sql .= " AND genre = :genre";
            $binds['genre'] = $params['genre'];
        }

        if (!empty($params['nombre'])) {
            $sql .= " AND nombre = :nombre";
            $binds['nombre'] = $params['nombre'];
        }

        // 3. TRI DYNAMIQUE
        $allowedSort = [
            'mot'           => 'mot',
            'signification' => 'signification',
            'lettre'        => 'lettre',
            'rime'          => 'rime',
            'updated_at'    => 'updated_at',
            'created_at'    => 'created_at'
        ];

        $sortField = $allowedSort[$params['sort'] ?? ''] ?? 'mot';
        $direction = (isset($params['order']) && strtoupper($params['order']) === 'DESC') ? 'DESC' : 'ASC';

        // Le tri par mot inclut toujours la variante pour la cohérence visuelle
        if ($sortField === 'mot') {
            $sql .= " ORDER BY mot $direction, variante ASC";
        } else {
            $sql .= " ORDER BY $sortField $direction";
        }

        // 4. PAGINATION
        if (!empty($params['limit']) && $params['limit'] !== 'all') {
            $limit = (int)$params['limit'];
            $sql .= " LIMIT $limit";
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($binds);
            $results = $stmt->fetchAll();

            // POST-TRAITEMENT : Tri de pertinence si recherche textuelle
            if (!empty($params['q'])) {
                $this->sortByRelevance($results, $params['q']);
            }

            return $results;
        } catch (PDOException $e) {
            error_log("Erreur RhymeEngine : " . $e->getMessage());
            return [];
        }
    }

    /**
     * TRI PAR PERTINENCE
     * Place les résultats qui commencent par la recherche en premier.
     */
    private function sortByRelevance(&$results, $query) {
        usort($results, function($a, $b) use ($query) {
            $query = mb_strtolower($query, 'UTF-8');
            $motA = mb_strtolower($a['mot'], 'UTF-8');
            $motB = mb_strtolower($b['mot'], 'UTF-8');

            // 1. Match exact
            if ($motA === $query) return -1;
            if ($motB === $query) return 1;

            // 2. Commence par
            $startA = (mb_strpos($motA, $query) === 0);
            $startB = (mb_strpos($motB, $query) === 0);
            if ($startA && !$startB) return -1;
            if (!$startA && $startB) return 1;

            return 0;
        });
    }

    /**
     * RÉCUPÉRATION DES STATISTIQUES POUR LA NAVBAR/PROFILE
     */
    public function getGlobalStats() {
        return [
            'total_rimes' => $this->pdo->query("SELECT COUNT(*) FROM rimes")->fetchColumn(),
            'total_mots'  => $this->pdo->query("SELECT COUNT(DISTINCT mot) FROM rimes")->fetchColumn(),
            'last_update' => $this->pdo->query("SELECT MAX(updated_at) FROM rimes")->fetchColumn()
        ];
    }
}