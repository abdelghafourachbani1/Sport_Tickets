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

CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    category_id INT NOT NULL,
    seat_id INT NOT NULL,
    qr_code VARCHAR(255),

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
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










INSERT INTO users (role, name, email, password) VALUES
('ADMIN', 'Admin One', 'admin@sport.com', 'admin123'),
('ORGANIZER', 'Organizer One', 'organizer@sport.com', 'org123'),
('USER', 'User One', 'user1@sport.com', 'user123'),
('USER', 'User Two', 'user2@sport.com', 'user123');

INSERT INTO matches (
    organizer_id, team_home, team_away, match_date, location, total_seats, status
) VALUES
(2, 'FC Barcelona', 'Real Madrid', '2025-02-10 20:00:00', 'Camp Nou', 1500, 'APPROVED'),
(2, 'Liverpool', 'Manchester City', '2025-03-05 18:30:00', 'Anfield', 1800, 'PENDING');

INSERT INTO categories (match_id, name, price) VALUES
(1, 'VIP', 150.00),
(1, 'Standard', 80.00),
(2, 'VIP', 130.00),
(2, 'Standard', 70.00);

INSERT INTO tickets (
    user_id,
    match_id,
    category_id,
    seat_id,
    qr_code
) VALUES
(3, 1, 1, 101, 'QR-BCF-001'),
(4, 1, 2, 102, 'QR-BCF-002'),
(3, 2, 3, 201, 'QR-LIV-003'),
(4, 2, 4, 202, 'QR-LIV-004');


INSERT INTO comments (
    user_id, match_id, rating, comment
) VALUES
(3, 1, 5, 'Amazing match!'),
(4, 1, 4, 'Great atmosphere'),
(3, 2, 3, 'Good match but could be better');
