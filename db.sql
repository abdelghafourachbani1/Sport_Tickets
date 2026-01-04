-- ============================================
-- BASE DE DONNÉES: BILLETTERIE SPORTIVE
-- ============================================

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS billetterie_sportive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE billetterie_sportive;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'organisateur', 'acheteur') NOT NULL DEFAULT 'acheteur',
    statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: equipes
-- ============================================
CREATE TABLE equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    logo VARCHAR(255) NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: matches
-- ============================================
CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_domicile_id INT NOT NULL,
    equipe_exterieur_id INT NOT NULL,
    date DATETIME NOT NULL,
    lieu VARCHAR(100) NOT NULL,
    duree INT NOT NULL DEFAULT 90,
    places_totales INT NOT NULL CHECK (places_totales <= 2000),
    statut ENUM('en_attente', 'validé', 'refusé', 'terminé') NOT NULL DEFAULT 'en_attente',
    organisateur_id INT NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipe_domicile_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_exterieur_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (organisateur_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_statut (statut),
    INDEX idx_date (date),
    INDEX idx_lieu (lieu),
    INDEX idx_organisateur (organisateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: categories
-- ============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    nom VARCHAR(50) NOT NULL,
    prix DECIMAL(10, 2) NOT NULL CHECK (prix > 0),
    places_disponibles INT NOT NULL CHECK (places_disponibles >= 0),
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    INDEX idx_match (match_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: billets
-- ============================================
CREATE TABLE billets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    categorie_id INT NOT NULL,
    acheteur_id INT NOT NULL,
    numero_place VARCHAR(20) NOT NULL,
    prix_paye DECIMAL(10, 2) NOT NULL,
    date_achat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    qr_code VARCHAR(255) NOT NULL UNIQUE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (acheteur_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_place (match_id, categorie_id, numero_place),
    INDEX idx_match (match_id),
    INDEX idx_acheteur (acheteur_id),
    INDEX idx_qr_code (qr_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: commentaires
-- ============================================
CREATE TABLE commentaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    user_id INT NOT NULL,
    texte TEXT NOT NULL,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'valide', 'refuse') NOT NULL DEFAULT 'en_attente',
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_commentaire_user (match_id, user_id),
    INDEX idx_match (match_id),
    INDEX idx_user (user_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: refus_matches
-- ============================================
CREATE TABLE refus_matches (
    id INT AUTO_INCREMENT
    PRIMARY KEY,
match_id INT NOT NULL,
admin_id INT NOT NULL,
raison TEXT NOT NULL,
date_refus DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX idx_match (match_id)
)