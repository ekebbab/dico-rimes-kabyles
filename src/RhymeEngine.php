<?php
/**
 * CLASSE RhymeEngine
 * Moteur de recherche robuste adapté à la structure linguistique 2026.
 * Gère le filtrage par Lettre, Rime, Classe Grammaticale, Genre et Nombre.
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
     * Recherche avancée avec tris dynamiques et critères linguistiques
     */
    public function searchAdvanced($params) {
        $sql = "SELECT * FROM rimes WHERE 1=1";
        $binds = [];

        // 1. RECHERCHE TEXTUELLE (Champ principal 'q')
        // Gère les Cas 1 (Saisie) et Cas 2 (Navigation vide) décrits
        if (!empty($params['q'])) {
            $q = '%' . $params['q'] . '%';
            $type = $params['type'] ?? 'all';

            switch ($type) {
                case 'kabyle':
                    // Recherche dans le mot ou la rime
                    $sql .= " AND (mot LIKE :q OR rime LIKE :q)";
                    break;
                case 'francais':
                    // Recherche dans la signification
                    $sql .= " AND signification LIKE :q";
                    break;
                case 'exemple':
                    // Recherche dans les exemples
                    $sql .= " AND exemple LIKE :q";
                    break;
                default:
                    // Recherche globale
                    $sql .= " AND (mot LIKE :q OR rime LIKE :q OR signification LIKE :q OR exemple LIKE :q)";
                    break;
            }
            $binds['q'] = $q;
        }

        // 2. FILTRES LINGUISTIQUES (Navigation par critères)
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

        // 3. LOGIQUE DE TRI DYNAMIQUE (Demande : MOT, SIGNIFICATION, LETTRE, RIME, DATE)
        $allowedSort = [
            'mot'           => 'mot',
            'signification' => 'signification',
            'lettre'        => 'lettre',
            'rime'          => 'rime',
            'updated_at'    => 'updated_at'
        ];

        $sort = $allowedSort[$params['sort'] ?? ''] ?? 'mot';
        $order = (isset($params['order']) && strtoupper($params['order']) === 'DESC') ? 'DESC' : 'ASC';

        // Intégration systématique du rangement par variante pour les mots identiques
        $sql .= " ORDER BY $sort $order, variante ASC";

        // 4. LIMITATION ET PAGINATION (5, 10, 20, 50, 100, 500, ALL)
        if (!empty($params['limit']) && $params['limit'] !== 'all') {
            $sql .= " LIMIT " . (int)$params['limit'];
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
     * Utile pour récupérer toutes les rimes existantes pour une lettre donnée
     * (Pour remplir dynamiquement la liste déroulante du moteur de recherche)
     */
    public function getAvailableRhymes($lettre) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT rime FROM rimes WHERE lettre = ? ORDER BY rime ASC");
        $stmt->execute([$lettre]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}