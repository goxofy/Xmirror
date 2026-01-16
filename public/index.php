<?php
// Load Configuration
require_once __DIR__ . '/../src/config.php';

// --- AUTHENTICATION LOGIC ---
// --- AUTHENTICATION LOGIC ---
$cookie_name = 'twitter_mirror_auth';
$cookie_value = md5(ACCESS_PASSWORD . 'salt_string'); // Simple hash
$cookie_days = 30;

$authenticated = false;
$login_error = '';

// Check Cookie
if (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === $cookie_value) {
    $authenticated = true;
}

// Check Login Form
if (isset($_POST['password'])) {
    if ($_POST['password'] === ACCESS_PASSWORD) {
        setcookie($cookie_name, $cookie_value, time() + (86400 * $cookie_days), "/"); // 86400 = 1 day

        // Check for redirect
        $redirect_url = $_SERVER['PHP_SELF'];
        if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
            $redirect_url = $_GET['redirect'];
        }

        header("Location: " . $redirect_url);
        exit;
    } else {
        $login_error = 'Incorrect password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xmirror</title>
    <style>
        :root {
            --bg-color: #1e1e1e;
            --text-color: #d4d4d4;
            --accent-color: #569cd6;
            /* VS Code Blue */
            --border-color: #333333;
            --meta-color: #858585;
            --quote-border: #4ec9b0;
            /* VS Code Teal */
            --quote-text: #9cdcfe;
            /* Light Blue */
            --code-bg: #2d2d2d;
            --link-color: #569cd6;
        }

        body {
            font-family: "Consolas", "Monaco", "Courier New", monospace;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            margin: 0;
        }

        /* Login Form Styles */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
        }

        .login-box {
            background-color: #252526;
            padding: 40px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .login-box h2 {
            color: var(--accent-color);
            margin-top: 0;
            margin-bottom: 20px;
        }

        .login-box input[type="password"] {
            background-color: #3c3c3c;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 10px;
            border-radius: 4px;
            width: 200px;
            margin-bottom: 15px;
            font-family: inherit;
        }

        .login-box button {
            background-color: var(--accent-color);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
            font-weight: bold;
        }

        .login-box button:hover {
            opacity: 0.9;
        }

        .error-msg {
            color: #f48771;
            margin-top: 15px;
            font-size: 0.9em;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: var(--bg-color);
            padding: 0;
        }

        h1 {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-weight: normal;
            color: var(--accent-color);
            font-size: 1.8em;
        }

        .tweet {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            transition: background-color 0.2s;
        }

        .tweet:hover {
            background-color: #252526;
            /* Subtle hover effect */
        }

        .tweet:last-child {
            border-bottom: none;
        }

        .meta {
            font-size: 0.85em;
            color: var(--meta-color);
            margin-top: 10px;
        }

        .retweet-header {
            color: var(--meta-color);
            font-size: 0.9em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .retweet-header::before {
            content: "⇄";
            font-size: 1.2em;
        }

        .quote-box {
            border: 1px solid var(--border-color);
            border-left: 3px solid var(--quote-border);
            border-radius: 4px;
            padding: 12px;
            margin-top: 12px;
            background-color: rgba(255, 255, 255, 0.03);
            color: var(--quote-text);
        }

        .content {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 1.05em;
        }

        /* Image Optimization */
        .tweet-media {
            margin-top: 12px;
            display: grid;
            gap: 8px;
            border-radius: 8px;
            overflow: hidden;
        }

        /* Single image */
        .tweet-media.media-count-1 {
            grid-template-columns: 1fr;
        }

        /* Two images */
        .tweet-media.media-count-2 {
            grid-template-columns: 1fr 1fr;
        }

        /* Three images */
        .tweet-media.media-count-3 {
            grid-template-columns: 1fr 1fr;
        }

        .tweet-media.media-count-3 img:first-child {
            grid-row: span 2;
        }

        /* Four images */
        .tweet-media.media-count-4 {
            grid-template-columns: 1fr 1fr;
        }

        .tweet-media img {
            width: 100%;
            height: 100%;
            max-height: 300px;
            object-fit: cover;
            /* Crop to fit grid nicely */
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: opacity 0.2s;
            cursor: pointer;
        }

        .tweet-media.media-count-1 img {
            object-fit: contain;
            /* Don't crop single images */
            max-height: 350px;
            border: none;
            /* Letterbox for single images */
        }

        .tweet-media img:hover {
            opacity: 0.9;
            border-color: var(--accent-color);
        }

        a {
            color: var(--accent-color);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Expanded Link Styling */
        a.tco-link {
            color: var(--link-color);
            text-decoration: none;
            border-bottom: 1px dotted var(--link-color);
        }

        a.tco-link.expanded {
            border-bottom: none;
            background-color: rgba(86, 156, 214, 0.1);
            /* Subtle highlight */
            padding: 0 2px;
            border-radius: 2px;
        }

        a.tco-link::after {
            content: " ↗";
            /* External link icon */
            font-size: 0.8em;
            opacity: 0.7;
        }

        .pagination {
            margin-top: 40px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .pagination a {
            color: var(--text-color);
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin: 0 5px;
            transition: all 0.2s;
        }

        .pagination a:hover {
            background-color: var(--accent-color);
            color: #1e1e1e;
            border-color: var(--accent-color);
            text-decoration: none;
        }

        .pagination .current-page {
            font-weight: bold;
            background-color: #333;
            color: var(--text-color);
            padding: 8px 16px;
            border-radius: 4px;
            border: 1px solid #333;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-color);
        }

        ::-webkit-scrollbar-thumb {
            background: #444;
            border-radius: 6px;
            border: 3px solid var(--bg-color);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Lightbox */
        #lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            cursor: zoom-out;
        }

        #lightbox img {
            max-width: 95vw;
            max-height: 95vh;
            border: 2px solid var(--border-color);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            cursor: default;
        }

        /* Lazy Loading Placeholder */
        img.lazy-load {
            /* Spinner background - Brighter and larger */
            background: #252526 url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1MCA1MCI+PGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzU2OWNkNiIgc3Ryb2tlLXdpZHRoPSI1IiBzdHJva2UtZGFzaGFycmF5PSIzMS40IDMxLjQiIHRyYW5zZm9ybT0icm90YXRlKDAgMjUgMjUpIj48YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InJvdGF0ZSIgZnJvbT0iMCAyNSAyNSIgdG89IjM2MCAyNSAyNSIgZHVyPSIwLjhzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIvPjwvY2lyY2xlPjwvc3ZnPg==') no-repeat center center;
            background-size: 50px 50px;
            /* Blur effect for "gradually clear" feel */
            filter: blur(5px);
            transition: filter 0.5s ease-out, opacity 0.3s;
            opacity: 0.7;
            /* Slightly dim while loading */
        }

        img.lazy-loaded {
            background: none;
            filter: blur(0);
            opacity: 1;
        }

        img.lazy-loaded {
            background: none;
            filter: blur(0);
            opacity: 1;
        }

        /* Video Card */
        .video-card {
            margin-top: 12px;
            /* Match .tweet-media margin */
            position: relative;
            display: block;
            /* Ensure it takes full width/new line */
            width: 100%;
            /* Full width for centering */
            max-width: 100%;
            /* Or specific width if needed */
            border-radius: 8px;
            overflow: hidden;
            border: none;
            /* No border to match single images */
            cursor: pointer;
            text-decoration: none;
            /* Ensure no underline for anchor */
            /* background-color: #000; Removed to match tweet images */
        }

        .video-card img {
            display: block;
            width: 100%;
            height: auto;
            max-height: 350px;
            /* Match single image max-height */
            object-fit: contain;
            /* Preserve aspect ratio like single images */
            transition: opacity 0.2s;
        }

        .video-card:hover img {
            opacity: 0.8;
        }

        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.2s, background-color 0.2s;
        }

        .video-card:hover .play-button {
            transform: translate(-50%, -50%) scale(1.1);
            background-color: rgba(29, 155, 240, 0.9);
            /* Twitter Blue */
        }

        .play-button svg {
            width: 24px;
            height: 24px;
            fill: white;
            margin-left: 3px;
            /* Visual correction */
        }
    </style>
</head>

<body>

    <?php
    if (!$authenticated) {
        ?>
        <div class="login-container">
            <div class="login-box">
                <h2>System Access</h2>
                <form method="post">
                    <input type="password" name="password" placeholder="Enter Password" required autofocus>
                    <br>
                    <button type="submit">Login</button>
                </form>
                <?php if ($login_error): ?>
                    <div class="error-msg"><?php echo $login_error; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </body>

    </html>
    <?php
    exit; // Stop execution here
    }
    ?>

<div class="container">

    <?php

    // --- RAPIDAPI AUTO-UPDATE LOGIC ---
    
    // require_once __DIR__ . '/../src/config.php'; // Already required at top
    require_once __DIR__ . '/../src/database.php';

    $update_lock_file = __DIR__ . '/../db/last_update.lock';
    $last_update_timestamp = file_exists($update_lock_file) ? (int) @file_get_contents($update_lock_file) : 0;

    // Debugging & Force Update
    $debug_mode = isset($_GET['debug']);
    $force_update = isset($_GET['force']);

    if ($force_update || (time() - $last_update_timestamp) > UPDATE_INTERVAL) {

        if ($debug_mode)
            echo "<div style='color:yellow; border:1px solid yellow; padding:10px; margin-bottom:20px;'><strong>DEBUG MODE:</strong> Attempting update...<br>";

        // Only update timestamp if NOT in debug/force mode to avoid spamming, 
        // OR update it if it was a legitimate time-based trigger.
        // Actually, let's just update it to prevent concurrent runs, unless debugging.
        if (!$debug_mode) {
            file_put_contents($update_lock_file, time());
        }

        try {
            $pdo = get_db_connection();

            // RapidAPI Endpoint
            $api_url = "https://" . RAPID_API_HOST . "/timeline.php";
            $params = [
                'screenname' => TWITTER_USERNAME,
                'count' => 100,
            ];

            if ($debug_mode)
                echo "Fetching from: $api_url<br>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url . '?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'x-rapidapi-key: ' . RAPID_API_KEY,
                'x-rapidapi-host: ' . RAPID_API_HOST
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($debug_mode)
                echo "HTTP Code: $http_code<br>";
            if ($debug_mode && $curl_error)
                echo "cURL Error: $curl_error<br>";

            if ($http_code == 200) {
                $result = json_decode($response, true);

                if (!empty($result['timeline'])) {
                    if ($debug_mode)
                        echo "Got " . count($result['timeline']) . " tweets from API.<br>";

                    $stmt = $pdo->prepare("INSERT OR IGNORE INTO tweets (tweet_id, text, author_username, author_name, created_at, type, quoted_text, quoted_author_username, quoted_author_name, raw_data) VALUES (:tweet_id, :text, :author_username, :author_name, :created_at, :type, :quoted_text, :quoted_author_username, :quoted_author_name, :raw_data)");

                    $inserted_count = 0;
                    foreach ($result['timeline'] as $tweet_data) {
                        // ... (rest of the loop logic) ...
                        // We need to keep the loop logic inside here, but I can't easily replace just the wrapper 
                        // without copying the whole loop. I will assume the user wants me to rewrite the wrapper 
                        // and keep the inner logic. 
                        // WAIT, replace_file_content replaces the WHOLE block. 
                        // I need to include the inner logic or use a larger range.
                        // The previous `view_file` showed lines 161-277. I will replace that whole block.
    
                        // Let's re-implement the loop logic carefully.
    
                        $type = 'tweet';
                        // Check for Retweet
                        $is_retweet = false;
                        $source_tweet = $tweet_data; // Default to top-level
    
                        if (isset($tweet_data['retweeted_tweet'])) {
                            $is_retweet = true;
                            $source_tweet = $tweet_data['retweeted_tweet']; // Use the original tweet for text/media
                            $text = "RT @" . $source_tweet['author']['screen_name'] . ": " . $source_tweet['text'];
                            $type = 'retweet';
                        } else {
                            $text = $tweet_data['text'];
                            if (strpos($text, 'RT @') === 0) {
                                $type = 'retweet'; // Fallback detection
                            }
                        }

                        $author_username = $tweet_data['author']['screen_name'];
                        $author_name = $tweet_data['author']['name'];
                        $created_at_str = $tweet_data['created_at'];

                        $timestamp = strtotime($created_at_str);
                        if (!$timestamp) {
                            $timestamp = time();
                        }
                        $created_at = date('Y-m-d H:i:s', $timestamp);

                        // Check for Quote
                        $quoted_text = null;
                        $quoted_author_username = null;
                        $quoted_author_name = null;
                        $quoted_media_urls = []; // New array for quoted media
    
                        if (isset($tweet_data['quoted'])) {
                            $type = 'quote';
                            $quoted_text = $tweet_data['quoted']['text'];
                            $quoted_author_username = $tweet_data['quoted']['author']['screen_name'];
                            $quoted_author_name = $tweet_data['quoted']['author']['name'];

                            // Extract media from quoted tweet
                            if (isset($tweet_data['quoted']['media']['photo'])) {
                                foreach ($tweet_data['quoted']['media']['photo'] as $photo) {
                                    $quoted_media_urls[] = $photo['media_url_https'];
                                }
                            }
                            // Legacy formats for quoted tweet
                            elseif (isset($tweet_data['quoted']['extended_entities']['media'])) {
                                foreach ($tweet_data['quoted']['extended_entities']['media'] as $media) {
                                    if ($media['type'] == 'photo') {
                                        $quoted_media_urls[] = $media['media_url_https'];
                                    }
                                }
                            } elseif (isset($tweet_data['quoted']['entities']['media'])) {
                                foreach ($tweet_data['quoted']['entities']['media'] as $media) {
                                    if ($media['type'] == 'photo') {
                                        $quoted_media_urls[] = $media['media_url_https'];
                                    }
                                }
                            }
                        }

                        // Extract Media (Images) from the SOURCE tweet (to fix missing images in RTs)
                        $media_urls = [];
                        // 1. RapidAPI Format (media.photo)
                        if (isset($source_tweet['media']['photo'])) {
                            foreach ($source_tweet['media']['photo'] as $photo) {
                                $media_urls[] = $photo['media_url_https'];
                            }
                        }
                        // 2. Legacy/V1.1/tweets.js Format (extended_entities) - unlikely in this API but good for safety
                        elseif (isset($source_tweet['extended_entities']['media'])) {
                            foreach ($source_tweet['extended_entities']['media'] as $media) {
                                if ($media['type'] == 'photo') {
                                    $media_urls[] = $media['media_url_https'];
                                }
                            }
                        } elseif (isset($source_tweet['entities']['media'])) {
                            foreach ($source_tweet['entities']['media'] as $media) {
                                if ($media['type'] == 'photo') {
                                    $media_urls[] = $media['media_url_https'];
                                }
                            }
                        }

                        // Prepare Raw Data
                        // We need to ensure the raw_data stored reflects the media_urls if they were extracted from a retweeted_tweet
                        // For simplicity, we'll just store the original $tweet_data as raw_data, and handle media extraction at display time.
                        // If we want to store processed media_urls, we'd need to add a column or modify raw_data.
    
                        // IMPORTANT: We need to pass the extracted quoted media to the display logic.
                        // Since we are storing the WHOLE tweet_data in raw_data, the display logic *should* be able to find it 
                        // IF we update the display logic to look for it.
                        // But wait, `quoted` is inside `tweet_data`. So `raw_data` ALREADY contains the quoted media info.
                        // So `raw_data` DOES contain the quoted media.
    
                        // So the fix is ONLY in the DISPLAY logic.
                        // I will abort this edit and switch to editing the display logic.
    
                        $raw_data_json = json_encode($tweet_data);

                        $stmt->execute([
                            ':tweet_id' => $tweet_data['tweet_id'],
                            ':text' => $text,
                            ':author_username' => $author_username,
                            ':author_name' => $author_name,
                            ':created_at' => $created_at,
                            ':type' => $type,
                            ':quoted_text' => $quoted_text,
                            ':quoted_author_username' => $quoted_author_username,
                            ':quoted_author_name' => $quoted_author_name,
                            ':raw_data' => $raw_data_json
                        ]);

                        if ($stmt->rowCount() > 0) {
                            $inserted_count++;
                        }
                    }

                    if ($debug_mode)
                        echo "Inserted $inserted_count new tweets.<br>";
                } else {
                    if ($debug_mode)
                        echo "API returned empty timeline.<br>";
                    if ($debug_mode)
                        var_dump($result);
                }
            } else {
                if ($debug_mode)
                    echo "API Request Failed. Response: $response<br>";
            }

            if ($debug_mode)
                echo "</div>";

        } catch (Throwable $t) {
            if ($debug_mode)
                echo "<strong>Error:</strong> " . $t->getMessage() . "<br></div>";
            // file_put_contents(__DIR__ . '/error.log', $t->getMessage(), FILE_APPEND);
        }
    }

    // --- DISPLAY LOGIC (remains mostly the same) ---
    
    $pdo = get_db_connection();

    $tweets_per_page = 50;
    $total_tweets_count = $pdo->query("SELECT COUNT(*) FROM tweets")->fetchColumn();
    $total_pages = $total_tweets_count > 0 ? ceil($total_tweets_count / $tweets_per_page) : 1;
    $current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($current_page < 1)
        $current_page = 1;
    if ($current_page > $total_pages)
        $current_page = $total_pages;
    $offset = ($current_page - 1) * $tweets_per_page;

    $stmt = $pdo->prepare("SELECT * FROM tweets ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $tweets_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $page_tweets = $stmt->fetchAll();

    // Helper function to linkify t.co URLs
    function linkify_tweet($text)
    {
        return preg_replace(
            '~(https?://t\.co/[a-zA-Z0-9]+)~',
            '<a href="$1" class="tco-link" target="_blank">$1</a>',
            $text
        );
    }

    if ($total_tweets_count === 0) {
        echo "<h1>No tweets found.</h1>";
    } else {
        foreach ($page_tweets as $tweet) {
            echo '<div class="tweet">';
            $raw_tweet = json_decode($tweet['raw_data'], true);

            // --- CLEANUP LOGIC: Remove Media URLs from Text ---
            $clean_text = $tweet['text'];
            if (isset($raw_tweet['extended_entities']['media'])) {
                foreach ($raw_tweet['extended_entities']['media'] as $media) {
                    if (isset($media['url'])) {
                        $clean_text = str_replace($media['url'], '', $clean_text);
                    }
                }
            } elseif (isset($raw_tweet['entities']['media'])) {
                foreach ($raw_tweet['entities']['media'] as $media) {
                    if (isset($media['url'])) {
                        $clean_text = str_replace($media['url'], '', $clean_text);
                    }
                }
            }

            $clean_quoted_text = isset($tweet['quoted_text']) ? $tweet['quoted_text'] : '';
            // Note: Quoted text cleanup is harder without raw quoted data, 
            // but usually the main text cleanup covers the most annoying cases.
            // If we had raw quoted data we would do the same there.
    
            // Use linkify function on SAFE text (sanitize first!)
            $text_to_display = linkify_tweet(htmlspecialchars($clean_text));
            $quoted_text_to_display = isset($tweet['quoted_text']) ? linkify_tweet(htmlspecialchars($clean_quoted_text)) : '';

            // Display logic for text and quote box
            switch ($tweet['type']) {
                case 'retweet':
                    echo '<div class="retweet-header">';
                    echo "Retweeted from @{$tweet['author_username']} ({$tweet['author_name']})";
                    echo '</div>';
                    echo '<div class="content">' . $text_to_display . '</div>';
                    break;
                case 'quote':
                    echo '<div class="content">' . $text_to_display . '</div>';
                    echo '<div class="quote-box">';
                    echo "Quoting @{$tweet['quoted_author_username']} ({$tweet['quoted_author_name']}):<br>";
                    echo '<div class="content">' . $quoted_text_to_display . '</div>';

                    // Display Quoted Media
                    $quoted_media_urls = [];
                    if (isset($raw_tweet['quoted']['media']['photo'])) {
                        foreach ($raw_tweet['quoted']['media']['photo'] as $photo) {
                            $quoted_media_urls[] = $photo['media_url_https'];
                        }
                    } elseif (isset($raw_tweet['quoted']['extended_entities']['media'])) {
                        foreach ($raw_tweet['quoted']['extended_entities']['media'] as $media) {
                            if ($media['type'] == 'photo') {
                                $quoted_media_urls[] = $media['media_url_https'];
                            }
                        }
                    } elseif (isset($raw_tweet['quoted']['entities']['media'])) {
                        foreach ($raw_tweet['quoted']['entities']['media'] as $media) {
                            if ($media['type'] == 'photo') {
                                $quoted_media_urls[] = $media['media_url_https'];
                            }
                        }
                    }

                    if (!empty($quoted_media_urls)) {
                        $q_count = count($quoted_media_urls);
                        echo '<div class="tweet-media media-count-' . $q_count . '">';
                        foreach ($quoted_media_urls as $url) {
                            $proxy_url = 'image_proxy.php?url=' . urlencode($url);
                            echo '<img class="lazy-load" data-src="' . $proxy_url . '" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" loading="lazy">';
                        }
                        echo '</div>';
                    }

                    echo '</div>';
                    break;
                default:
                    echo '<div class="content">' . $text_to_display . '</div>';
                    break;
            }

            // Image Display Logic
            $media_urls = [];
            $media_source = $raw_tweet; // Default to top-level
    
            // If it's a retweet, the media is usually in the retweeted_tweet object
            if (isset($raw_tweet['retweeted_tweet'])) {
                $media_source = $raw_tweet['retweeted_tweet'];
            }

            // 1. RapidAPI Format
            if (isset($media_source['media']['photo'])) {
                foreach ($media_source['media']['photo'] as $photo) {
                    $media_urls[] = $photo['media_url_https'];
                }
            }
            // 2. Legacy/V1.1/tweets.js Format
            elseif (isset($media_source['extended_entities']['media'])) {
                foreach ($media_source['extended_entities']['media'] as $media) {
                    if ($media['type'] == 'photo') {
                        $media_urls[] = $media['media_url_https'];
                    }
                }
            } elseif (isset($media_source['entities']['media'])) {
                foreach ($media_source['entities']['media'] as $media) {
                    if ($media['type'] == 'photo') {
                        $media_urls[] = $media['media_url_https'];
                    }
                }
            }
            // 3. V2 API Format (Complex, requires includes, often skipped in simple mirrors)
            elseif (isset($media_source['attachments']['media_keys'])) {
                // Logic omitted for simplicity as we are moving to RapidAPI
            }

            if (!empty($media_urls)) {
                $m_count = count($media_urls);
                echo '<div class="tweet-media media-count-' . $m_count . '">';
                foreach ($media_urls as $url) {
                    $proxy_url = 'image_proxy.php?url=' . urlencode($url);
                    echo '<img class="lazy-load" data-src="' . $proxy_url . '" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" loading="lazy">';
                }
                echo '</div>';
            }
            echo '<div class="meta">';
            echo $tweet['created_at'];
            echo ' | <a href="https://twitter.com/' . $tweet['author_username'] . '/status/' . $tweet['tweet_id'] . '" target="_blank">View on Twitter</a>';
            echo '</div>';
            echo '</div>';
        }

        // Pagination Links
        echo '<div class="pagination">';
        if ($current_page > 1) {
            echo '<a href="?page=' . ($current_page - 1) . '">&laquo; Previous</a>';
        }
        echo '<span class="current-page">Page ' . $current_page . ' of ' . $total_pages . '</span>';
        if ($current_page < $total_pages) {
            echo '<a href="?page=' . ($current_page + 1) . '">Next &raquo;</a>';
        }
        echo '</div>';
    }

    ?>

</div>

<!-- Lightbox Container -->
<div id="lightbox">
    <img id="lightbox-img" src="" alt="Full size">
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const images = document.querySelectorAll('.tweet-media img');

        images.forEach(img => {
            img.addEventListener('click', (e) => {
                e.stopPropagation();
                lightboxImg.src = img.src;
                lightbox.style.display = 'flex';
            });
        });

        lightbox.addEventListener('click', () => {
            lightbox.style.display = 'none';
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && lightbox.style.display === 'flex') {
                lightbox.style.display = 'none';
            }
        });

        // Lazy Loading with IntersectionObserver
        const lazyImages = document.querySelectorAll('img.lazy-load');

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;

                        // Wait for the image to actually load before removing the spinner/blur
                        img.onload = () => {
                            img.classList.remove('lazy-load');
                            img.classList.add('lazy-loaded');
                        };

                        // Handle errors (e.g. proxy fail) by showing a placeholder or just removing spinner
                        img.onerror = () => {
                            img.classList.remove('lazy-load');
                            img.style.border = "1px solid red"; // Visual cue for error
                        };

                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '200px 0px', // Start loading 200px before they appear
                threshold: 0.01
            });

            lazyImages.forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for very old browsers
            lazyImages.forEach(img => {
                img.src = img.dataset.src;
                img.classList.add('lazy-loaded');
            });
        }

        // t.co Link Expander
        const tcoLinks = document.querySelectorAll('a.tco-link');

        // Use IntersectionObserver for links too? Or just load them all? 
        // Let's load them all but with a small delay or batching to avoid hammering the server.
        // Actually, IntersectionObserver is great here too.

        if ('IntersectionObserver' in window) {
            const linkObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const link = entry.target;
                        expandLink(link);
                        observer.unobserve(link);
                    }
                });
            });

            tcoLinks.forEach(link => {
                linkObserver.observe(link);
            });
        } else {
            tcoLinks.forEach(link => expandLink(link));
        }

        function expandLink(link) {
            const originalUrl = link.href;
            // Only process if it looks like a t.co link
            if (!originalUrl.includes('t.co/')) return;

            fetch('unshorten_proxy.php?url=' + encodeURIComponent(originalUrl))
                .then(response => response.json())
                .then(data => {
                    if (data.url && data.url !== originalUrl) {
                        // Check if it's a photo URL (either from flag or regex check)
                        if (data.is_photo || /\/photo\/\d+$/.test(data.url)) {
                            link.remove(); // Remove the link entirely
                            return;
                        }

                        // Check if it's a Video
                        if (data.is_video && data.thumbnail && data.video_url) {
                            // Create Video Card
                            const card = document.createElement('a');
                            card.className = 'video-card';
                            card.href = data.video_url;
                            card.target = '_blank';
                            card.rel = 'noreferrer';

                            const img = document.createElement('img');
                            img.src = 'image_proxy.php?url=' + encodeURIComponent(data.thumbnail);

                            const playBtn = document.createElement('div');
                            playBtn.className = 'play-button';
                            playBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';

                            card.appendChild(img);
                            card.appendChild(playBtn);

                            // Replace the link with the card
                            // Ensure it's on a new line if it was inline
                            link.parentNode.replaceChild(card, link);
                            return;
                        }

                        link.href = data.url;

                        // Format display URL: remove protocol, truncate to 25 chars
                        let displayUrl = data.url.replace(/^https?:\/\//, '');
                        if (displayUrl.length > 25) {
                            displayUrl = displayUrl.substring(0, 25) + '...';
                        }

                        link.innerText = displayUrl;
                        link.classList.add('expanded');
                    }
                })
                .catch(err => console.error('Failed to expand link:', err));
        }
    });
</script>

</body>

</html>