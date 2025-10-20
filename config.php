<?php
// config.php - PDO connection, DB auto-create and seeding
date_default_timezone_set('Africa/Lagos');
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'abba_risky_store';

try {
    $pdo = new PDO("mysql:host={$DB_HOST}", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `{$DB_NAME}`");
} catch (PDOException $e) {
    die('DB Connection failed: ' . $e->getMessage());
}

// create tables
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  sell_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  quantity INT NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  quantity_sold INT NOT NULL DEFAULT 1,
  sold_price DECIMAL(12,2) NOT NULL,
  gain_per_item DECIMAL(12,2) NOT NULL,
  total_gain DECIMAL(14,2) NOT NULL,
  sold_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// seed default admin and products
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (username,password) VALUES (?,?)");
    $ins->execute(['admin',$hash]);
}

$stmt = $pdo->query("SELECT COUNT(*) FROM products");
if ($stmt->fetchColumn() == 0) {
    $ins = $pdo->prepare("INSERT INTO products (name,cost_price,sell_price,quantity) VALUES (?,?,?,?)");
    $ins->execute(['Engine Oil',4500.00,5500.00,10]);
    $ins->execute(['Tyre',20000.00,26000.00,5]);
}
?>