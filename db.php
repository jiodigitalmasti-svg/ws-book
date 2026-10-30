<?php
// db.php - SQLite Database Connection & Initialization
session_start();

$db_file = __DIR__ . '/facebook.sqlite';

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create Tables if not exist
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        full_name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        bio TEXT DEFAULT 'Hello! I am using Facebook Clone.',
        avatar_color TEXT DEFAULT '#1877F2',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        image_url TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS likes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Seed default demo user if empty
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $stmt = $db->prepare("INSERT INTO users (username, full_name, email, password, bio, avatar_color) VALUES (?, ?, ?, ?, ?, ?)");
        $passHash = password_hash('password123', PASSWORD_BCRYPT);
        
        $stmt->execute(['alex_m', 'Alex Morgan', 'alex@example.com', $passHash, 'Web developer & tech enthusiast! Welcome to my Facebook feed.', '#1877F2']);
        $alexId = $db->lastInsertId();

        $stmt->execute(['sarah_j', 'Sarah Jenkins', 'sarah@example.com', $passHash, 'Coffee lover ☕ | Photographer 📸', '#e91e63']);
        $sarahId = $db->lastInsertId();

        // Seed posts
        $pStmt = $db->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $pStmt->execute([$alexId, "Hello world! 👋 Excited to build this awesome Facebook clone with PHP and SQLite!"]);
        $pStmt->execute([$sarahId, "Just finished a beautiful weekend walk in the park! What is everyone else up to today? ☀️🌸"]);
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

function getCurrentUser($db) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
