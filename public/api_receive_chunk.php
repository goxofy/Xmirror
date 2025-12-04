<?php

// Set a higher time limit just in case, though it should be fast.
set_time_limit(120);
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/database.php';

// Auth Check
$cookie_name = 'twitter_mirror_auth';
$cookie_value = md5(ACCESS_PASSWORD . 'salt_string');
if (!isset($_COOKIE[$cookie_name]) || $_COOKIE[$cookie_name] !== $cookie_value) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Basic security check: only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Unknown error', 'imported_count' => 0];

try {
    $json_data = file_get_contents('php://input');
    $payload = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON received: ' . json_last_error_msg());
    }

    $tweets_chunk = $payload['tweets'] ?? [];
    $is_first_chunk = $payload['is_first_chunk'] ?? false;

    if (empty($tweets_chunk)) {
        throw new Exception('Received empty or invalid data chunk.');
    }

    $pdo = get_db_connection();

    // Only run table creation for the very first chunk
    if ($is_first_chunk) {
        create_tables();
    }

    $stmt = $pdo->prepare(
        "INSERT OR IGNORE INTO tweets (tweet_id, text, author_username, author_name, created_at, type, quoted_text, quoted_author_username, quoted_author_name, raw_data) 
         VALUES (:tweet_id, :text, :author_username, :author_name, :created_at, :type, :quoted_text, :quoted_author_username, :quoted_author_name, :raw_data)"
    );

    $pdo->beginTransaction();

    $imported_count = 0;
    foreach ($tweets_chunk as $entry) {
        if (!isset($entry['tweet']))
            continue;
        $tweet = $entry['tweet'];

        $type = 'tweet';
        $text = $tweet['full_text'];
        $author_username = $tweet['user']['screen_name'] ?? TWITTER_USERNAME;
        $author_name = $tweet['user']['name'] ?? TWITTER_USERNAME;
        $quoted_text = null;
        $quoted_author_username = null;
        $quoted_author_name = null;

        if (strpos($text, 'RT @') === 0) {
            $type = 'retweet';
        } elseif (isset($tweet['quoted_status'])) {
            $type = 'quote';
            $quoted_text = $tweet['quoted_status']['full_text'];
            $quoted_author_username = $tweet['quoted_status']['user']['screen_name'];
            $quoted_author_name = $tweet['quoted_status']['user']['name'];
        }

        $params = [
            ':tweet_id' => $tweet['id_str'],
            ':text' => $text,
            ':author_username' => $author_username,
            ':author_name' => $author_name,
            ':created_at' => date('Y-m-d H:i:s', strtotime($tweet['created_at'])),
            ':type' => $type,
            ':quoted_text' => $quoted_text,
            ':quoted_author_username' => $quoted_author_username,
            ':quoted_author_name' => $quoted_author_name,
            ':raw_data' => json_encode($tweet)
        ];

        if ($stmt->execute($params) && $stmt->rowCount() > 0) {
            $imported_count++;
        }
    }

    $pdo->commit();

    $response = ['status' => 'success', 'message' => "Processed chunk successfully.", 'imported_count' => $imported_count];

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response = ['status' => 'error', 'message' => $e->getMessage(), 'imported_count' => 0];
}

echo json_encode($response);

?>