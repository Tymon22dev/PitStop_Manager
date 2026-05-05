CREATE DATABASE pitstop_db;
USE pitstop_db;

-- Tabela użytkowników (zgodnie z Twoim pomysłem: Admin tworzy Usera)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('admin', 'user') DEFAULT 'user', 
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1
);
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(50) NOT NULL, 
    model VARCHAR(50) NOT NULL, 
    vin VARCHAR(50) UNIQUE, 
    year INT,
    status ENUM('aktywny', 'w_naprawie', 'wycofany') DEFAULT 'aktywny'
);

-- Tabela kategorii (np. Silnik, Zawieszenie, Opony)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Tabela części (Magazyn - CRUD dla Usera/Admina)
CREATE TABLE parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    serial_number VARCHAR(50),
    price DECIMAL(10, 2) NOT NULL,
    quantity INT DEFAULT 1,
    status ENUM('nowy', 'używany', 'uszkodzony') DEFAULT 'nowy',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tabela logów technicznych (Twoje "Posty" - CRUD dla Usera/Admina)
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    vehicle_id INT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    track_name VARCHAR(100), 
    vehicle_id INT, 
    status ENUM('zaplanowane', 'zakończone', 'anulowane') DEFAULT 'zaplanowane',
    result VARCHAR(100) DEFAULT 'nieokreślony',
    description TEXT,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
);