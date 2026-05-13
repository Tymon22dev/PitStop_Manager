CREATE DATABASE IF NOT EXISTS pitstop_db;
USE pitstop_db;

DROP TABLE IF EXISTS parts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS event_vehicles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS vehicles;

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
    number VARCHAR(10),
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
    min_quantity INT DEFAULT 5,
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    track_name VARCHAR(100), 
    status ENUM('zaplanowane', 'zakończone', 'anulowane') DEFAULT 'zaplanowane',
    result VARCHAR(100) DEFAULT 'nieokreślony',
    description TEXT,
    image_path VARCHAR(255) NULL
);

CREATE TABLE event_vehicles (
    event_id INT,
    vehicle_id INT,
    PRIMARY KEY (event_id, vehicle_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);