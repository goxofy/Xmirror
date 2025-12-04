<?php

require_once __DIR__ . '/config.php';

/**
 * Establishes a connection to the SQLite database.
 *
 * @return PDO
 */
function get_db_connection()
{
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        // In a real app, you'd want to log this error, not just die.
        throw new PDOException("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Creates the necessary tables in the database if they don't already exist.
 */
function create_tables()
{
    $pdo = get_db_connection();

    $sql = "CREATE TABLE IF NOT EXISTS tweets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tweet_id TEXT NOT NULL UNIQUE,
        text TEXT NOT NULL,
        author_username TEXT NOT NULL,
        author_name TEXT NOT NULL,
        created_at TEXT NOT NULL,
        type TEXT NOT NULL, -- 'tweet', 'retweet', 'quote'
        quoted_text TEXT,
        quoted_author_username TEXT,
        quoted_author_name TEXT,
        raw_data TEXT NOT NULL
    );";

    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        die("Table creation failed: " . $e->getMessage());
    }
}

?>
