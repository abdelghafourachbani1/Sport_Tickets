CREATE database sport_tickets ;
USE sport_tickets ;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('USER','ORGANIZER','ADMIN') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    team_home VARCHAR(100) NOT NULL,
    team_away VARCHAR(100) NOT NULL,
    match_date DATETIME NOT NULL,
    location VARCHAR(100) NOT NULL,
    duration INT DEFAULT 90,
    total_seats INT CHECK (total_seats <= 2000),
    status ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
    
    FOREIGN KEY (organizer_id) REFERENCES users(id)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (match_id) REFERENCES matches(id)
);

CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    is_reserved BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    category_id INT NOT NULL,
    seat_id INT NOT NULL,
    qr_code VARCHAR(255),

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id)
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (match_id) REFERENCES matches(id)
);
