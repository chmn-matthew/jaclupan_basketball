<?php
// config.php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'jaclupan_basketball';
$username = 'root';
$password = '';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_name VARCHAR(255) NOT NULL,
            coach_name VARCHAR(255) NOT NULL,
            contact_number VARCHAR(20) NOT NULL,
            email VARCHAR(255) NOT NULL,
            division VARCHAR(50) NOT NULL,
            players TEXT NOT NULL,
            logo_path VARCHAR(255),
            players_registered TINYINT(1) DEFAULT 0,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_team_email (email)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            birthdate DATE NOT NULL,
            jersey_number INT NOT NULL,
            position VARCHAR(50),
            photo_path VARCHAR(255) NOT NULL,
            document_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            UNIQUE KEY unique_team_jersey (team_id, jersey_number)
        )
    ");
    
    // Ensure players table has address and birth_certificate_path columns
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'players' AND COLUMN_NAME = 'address'");
        $stmt->execute([$dbname]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE players ADD COLUMN address VARCHAR(255) NULL AFTER birthdate");
        }
    } catch (Exception $ignored) {}
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'players' AND COLUMN_NAME = 'birth_certificate_path'");
        $stmt->execute([$dbname]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE players ADD COLUMN birth_certificate_path VARCHAR(255) NULL AFTER document_path");
        }
    } catch (Exception $ignored) {}

    // Ensure document_path allows NULL (ID is optional)
    try {
        $column = $pdo->query("SHOW COLUMNS FROM players LIKE 'document_path'")->fetch(PDO::FETCH_ASSOC);
        if ($column && (strtoupper($column['Null'] ?? '') !== 'YES')) {
            $pdo->exec("ALTER TABLE players MODIFY document_path VARCHAR(255) NULL");
        }
    } catch (Exception $ignored) {}
    
    // Ensure username/password columns exist on teams table for login
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'teams' AND COLUMN_NAME = 'username'");
        $stmt->execute([$dbname]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE teams ADD COLUMN username VARCHAR(255) UNIQUE");
        }
    } catch (Exception $ignored) {}
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'teams' AND COLUMN_NAME = 'password'");
        $stmt->execute([$dbname]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE teams ADD COLUMN password VARCHAR(255)");
        }
    } catch (Exception $ignored) {}
    
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create upload directories if they don't exist
if (!file_exists('uploads/photos')) {
    mkdir('uploads/photos', 0777, true);
}
if (!file_exists('uploads/documents')) {
    mkdir('uploads/documents', 0777, true);
}
if (!file_exists('uploads/logos')) {
    mkdir('uploads/logos', 0777, true);
}
?>