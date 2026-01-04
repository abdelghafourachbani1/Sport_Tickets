<?php

/**
 * Classe Statistiques
 * Méthodes statiques pour calculer diverses statistiques
 */
class Statistiques
{

    /**
     * Obtenir le nombre de billets vendus pour un match
     * @param int $matchId
     * @return int
     */
    public static function getBilletsVendusParMatch(int $matchId): int
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT COUNT(*) as total FROM billets WHERE match_id = :matchId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Erreur statistiques billets vendus: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtenir le chiffre d'affaires pour un match
     * @param int $matchId
     * @return float
     */
    public static function getChiffreAffairesParMatch(int $matchId): float
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT SUM(prix_paye) as total FROM billets WHERE match_id = :matchId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Erreur statistiques chiffre d'affaires: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtenir les statistiques globales de la plateforme
     * @return array
     */
    public static function getStatistiquesGlobales(): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $stats = [];

            // Total utilisateurs
            $query1 = "SELECT COUNT(*) as total FROM users WHERE statut = 'actif'";
            $stats['total_utilisateurs'] = $db->query($query1)->fetch(PDO::FETCH_ASSOC)['total'];

            // Total matches validés
            $query2 = "SELECT COUNT(*) as total FROM matches WHERE statut = 'validé'";
            $stats['total_matches'] = $db->query($query2)->fetch(PDO::FETCH_ASSOC)['total'];

            // Total billets vendus
            $query3 = "SELECT COUNT(*) as total FROM billets";
            $stats['total_billets'] = $db->query($query3)->fetch(PDO::FETCH_ASSOC)['total'];

            // Chiffre d'affaires global
            $query4 = "SELECT SUM(prix_paye) as total FROM billets";
            $result = $db->query($query4)->fetch(PDO::FETCH_ASSOC);
            $stats['chiffre_affaires_global'] = $result['total'] ?? 0;

            // Utilisation de la VUE SQL
            $query5 = "SELECT * FROM vue_statistiques_matches ORDER BY billets_vendus DESC LIMIT 10";
            $stats['top_matches'] = $db->query($query5)->fetchAll(PDO::FETCH_ASSOC);

            // Moyenne des notes
            $query6 = "SELECT AVG(note) as moyenne FROM commentaires WHERE statut = 'validé'";
            $result = $db->query($query6)->fetch(PDO::FETCH_ASSOC);
            $stats['note_moyenne_globale'] = round($result['moyenne'] ?? 0, 2);

            return $stats;
        } catch (PDOException $e) {
            error_log("Erreur statistiques globales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir les matches les plus populaires
     * @param int $limit
     * @return array
     */
    public static function getMatchesPlusPopulaires(int $limit = 10): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT m.*, 
                             e1.nom as equipe_domicile, e1.logo as logo_domicile,
                             e2.nom as equipe_exterieur, e2.logo as logo_exterieur,
                             COUNT(b.id) as billets_vendus,
                             SUM(b.prix_paye) as revenus,
                             AVG(c.note) as note_moyenne
                      FROM matches m
                      JOIN equipes e1 ON m.equipe_domicile_id = e1.id
                      JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
                      LEFT JOIN billets b ON m.id = b.match_id
                      LEFT JOIN commentaires c ON m.id = c.match_id AND c.statut = 'validé'
                      WHERE m.statut = 'validé'
                      GROUP BY m.id
                      ORDER BY billets_vendus DESC
                      LIMIT :limit";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur matches populaires: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir les revenus d'un organisateur avec PROCÉDURE STOCKÉE
     * @param int $orgId
     * @return array
     */
    public static function getRevenusParOrganisateur(int $orgId): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            // Appel de la procédure stockée
            $query = "CALL sp_calculer_revenus_organisateur(:orgId)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':orgId', $orgId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $result ?: [
                'revenus_totaux' => 0,
                'nombre_matches' => 0,
                'billets_vendus' => 0
            ];
        } catch (PDOException $e) {
            error_log("Erreur revenus organisateur: " . $e->getMessage());
            return [
                'revenus_totaux' => 0,
                'nombre_matches' => 0,
                'billets_vendus' => 0
            ];
        }
    }

    /**
     * Obtenir les statistiques par catégorie pour un match
     * @param int $matchId
     * @return array
     */
    public static function getStatistiquesParCategorie(int $matchId): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT 
                        c.nom as categorie,
                        c.prix,
                        COUNT(b.id) as billets_vendus,
                        c.places_disponibles as places_restantes,
                        SUM(b.prix_paye) as revenus_categorie
                      FROM categories c
                      LEFT JOIN billets b ON c.id = b.categorie_id
                      WHERE c.match_id = :matchId
                      GROUP BY c.id
                      ORDER BY c.prix DESC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur statistiques par catégorie: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir les statistiques mensuelles
     * @param int $year
     * @return array
     */
    public static function getStatistiquesMensuelles(int $year): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT 
                        MONTH(b.date_achat) as mois,
                        COUNT(b.id) as billets_vendus,
                        SUM(b.prix_paye) as revenus,
                        COUNT(DISTINCT b.match_id) as matches_avec_ventes
                      FROM billets b
                      WHERE YEAR(b.date_achat) = :year
                      GROUP BY MONTH(b.date_achat)
                      ORDER BY mois ASC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur statistiques mensuelles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir le taux de remplissage d'un match
     * @param int $matchId
     * @return float Pourcentage
     */
    public static function getTauxRemplissage(int $matchId): float
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT 
                        m.places_totales,
                        COUNT(b.id) as billets_vendus
                      FROM matches m
                      LEFT JOIN billets b ON m.id = b.match_id
                      WHERE m.id = :matchId
                      GROUP BY m.id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || $result['places_totales'] == 0) {
                return 0;
            }

            return round(($result['billets_vendus'] / $result['places_totales']) * 100, 2);
        } catch (PDOException $e) {
            error_log("Erreur taux de remplissage: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtenir les utilisateurs les plus actifs
     * @param int $limit
     * @return array
     */
    public static function getUtilisateursPlusActifs(int $limit = 10): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT 
                        u.id,
                        u.nom,
                        u.prenom,
                        u.email,
                        COUNT(b.id) as total_billets,
                        SUM(b.prix_paye) as montant_depense
                        FROM users u
                        JOIN billets b ON u.id = b.acheteur_id
                        WHERE u.role = 'acheteur' AND u.statut = 'actif'
                        GROUP BY u.id
                        ORDER BY total_billets DESC
                        LIMIT :limit";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur utilisateurs actifs: " . $e->getMessage());
            return [];
        }
    }


    public static function getMatchesAVenir(int $limit = 5): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT m.*, 
                            e1.nom as equipe_domicile, e1.logo as logo_domicile,
                            e2.nom as equipe_exterieur, e2.logo as logo_exterieur,
                            COUNT(b.id) as billets_vendus
                        FROM matches m
                        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
                        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
                        LEFT JOIN billets b ON m.id = b.match_id
                        WHERE m.statut = 'validé' AND m.date > NOW()
                        GROUP BY m.id
                        ORDER BY m.date ASC
                        LIMIT :limit";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur matches à venir: " . $e->getMessage());
            return [];
        }
    }
}
