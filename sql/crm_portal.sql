CREATE DATABASE IF NOT EXISTS crm_portal;
USE crm_portal;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'User',
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    company VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(120) DEFAULT NULL,
    status ENUM('Active','Pending','Qualified','Inactive') DEFAULT 'Active',
    assigned_to INT DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_client_user FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE business_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_by INT DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_card_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_card_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE follow_ups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    followup_date DATETIME NOT NULL,
    status ENUM('Pending','Completed','Overdue') DEFAULT 'Pending',
    notes VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_follow_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_follow_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (name, email, password, role, status) VALUES
('Super Admin', 'admin@crm.com', '$2y$10$KIXID9uQF6sD9m6j8K6n7.PvF6L8qg9S4wCq6hD4j7QyNn0L7rCqW', 'Super Admin', 'Active'),
('Riya', 'riya@crm.com', '$2y$10$KIXID9uQF6sD9m6j8K6n7.PvF6L8qg9S4wCq6hD4j7QyNn0L7rCqW', 'Manager', 'Active'),
('Rahul', 'rahul@crm.com', '$2y$10$KIXID9uQF6sD9m6j8K6n7.PvF6L8qg9S4wCq6hD4j7QyNn0L7rCqW', 'Executive', 'Active');

INSERT INTO clients (name, company, phone, email, status, assigned_to, notes, created_at) VALUES
('Amit Verma', 'Verma Infotech', '+91 9876543210', 'amit@verma.com', 'Active', 2, 'Important corporate account', '2026-01-10 10:00:00'),
('Neha Kapoor', 'Kapoor Media', '+91 9988776655', 'neha@kapoor.com', 'Pending', 3, 'Waiting for proposal approval', '2026-02-14 12:00:00'),
('Rahul Singh', 'Singh Associates', '+91 9090909090', 'rahul@singh.com', 'Qualified', 1, 'Qualified after first discussion', '2026-03-18 09:30:00'),
('Green Valley Pvt Ltd', 'Green Valley Pvt Ltd', '+91 9999999991', 'contact@greenvalley.com', 'Active', 2, 'Interested in bulk plan', '2026-04-08 14:00:00'),
('Urban Space Studio', 'Urban Space Studio', '+91 9999999992', 'hello@urbanspace.com', 'Pending', 3, 'Card verification pending', '2026-05-22 11:20:00'),
('Rahul Traders', 'Rahul Traders', '+91 9999999993', 'owner@rahultraders.com', 'Active', 1, 'Warm lead', '2026-06-27 15:15:00');

INSERT INTO business_cards (client_id, image_path, uploaded_by) VALUES
(1, 'uploads/card1.jpg', 1),
(2, 'uploads/card2.jpg', 2),
(3, 'uploads/card3.jpg', 3),
(4, 'uploads/card4.jpg', 1);

INSERT INTO follow_ups (client_id, followup_date, status, notes, created_by) VALUES
(6, '2026-07-30 10:30:00', 'Pending', 'Lead Warm', 1),
(4, '2026-07-31 15:15:00', 'Pending', 'Proposal Pending', 2),
(5, '2026-08-01 12:00:00', 'Pending', 'Card Verification', 3),
(1, '2026-07-25 11:00:00', 'Completed', 'Intro call completed', 2),
(2, '2026-07-20 17:00:00', 'Overdue', 'Waiting for feedback', 3);


ALTER TABLE clients
    ADD COLUMN first_name VARCHAR(100) NULL AFTER name,
    ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name,
    ADD COLUMN location VARCHAR(150) NULL AFTER phone,
    ADD COLUMN linkedin_id VARCHAR(255) NULL AFTER location,
    ADD COLUMN photo VARCHAR(255) NULL AFTER linkedin_id,
    ADD COLUMN card_photo VARCHAR(255) NULL AFTER photo;

UPDATE clients SET first_name = 'Amit', last_name = 'Verma', location = 'Noida', linkedin_id = 'linkedin.com/in/amitverma' WHERE email = 'amit@verma.com';
UPDATE clients SET first_name = 'Neha', last_name = 'Kapoor', location = 'Delhi', linkedin_id = 'linkedin.com/in/nehakapoor' WHERE email = 'neha@kapoor.com';
UPDATE clients SET first_name = 'Rahul', last_name = 'Singh', location = 'Ghaziabad', linkedin_id = 'linkedin.com/in/rahulsingh' WHERE email = 'rahul@singh.com';
UPDATE clients SET first_name = 'Green', last_name = 'Valley', location = 'Gurugram', linkedin_id = 'linkedin.com/company/greenvalley' WHERE email = 'contact@greenvalley.com';
UPDATE clients SET first_name = 'Urban', last_name = 'Studio', location = 'Noida', linkedin_id = 'linkedin.com/company/urbanspace' WHERE email = 'hello@urbanspace.com';
UPDATE clients SET first_name = 'Rahul', last_name = 'Traders', location = 'Dadri', linkedin_id = 'linkedin.com/company/rahultraders' WHERE email = 'owner@rahultraders.com';
