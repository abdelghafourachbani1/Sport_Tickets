
CREATE DATABASE sport_tickets;
USE sport_tickets;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('USER','ORGANIZER','ADMIN') NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('acitif','inactive') NOT NULL
);

CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    pays VARCHAR(50)
);

CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    team_home_id INT NOT NULL,
    team_away_id INT NOT NULL,
    match_date DATE NOT NULL,
    match_time TIME NOT NULL,
    lieu VARCHAR(100) NOT NULL,
    duree INT DEFAULT 90,
    total_places INT NOT NULL,
    status ENUM('en_attente','valide','refuse') DEFAULT 'en_attente',
    motif_refus TEXT,
    
    FOREIGN KEY (organizer_id) REFERENCES users(id),
    FOREIGN KEY (team_home_id) REFERENCES teams(id),
    FOREIGN KEY (team_away_id) REFERENCES teams(id)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    nom VARCHAR(50) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    nb_places INT NOT NULL,
    description TEXT,
    
    FOREIGN KEY (match_id) REFERENCES matches(id)
);

CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    category_id INT NOT NULL,
    numero_place VARCHAR(10) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    qr_code VARCHAR(255),
    identifiant VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('valide','annule') DEFAULT 'valide',
    date_achat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    note INT NOT NULL,
    commentaire TEXT,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (match_id) REFERENCES matches(id)
);


