<?php

// Basic security checks
if (!isset($_GET['url'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing URL parameter']));
}

$url = $_GET['url'];

// Validate that the URL is a valid t.co URL
if (!preg_match("~^https?://t\.co/[a-zA-Z0-9]+~", $url)) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid or forbidden URL. Only t.co links allowed.']));
}

// Use cURL to get the Location header (HEAD request only)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in output
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Do NOT follow redirects automatically
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; TwitterMirror/1.0)');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 300 && $http_code < 400) {
    // Extract Location header
    if (preg_match('/^Location: (.+)$/mi', $response, $matches)) {
        $real_url = trim($matches[1]);

        $response_data = ['url' => $real_url, 'is_photo' => false, 'is_video' => false];

        // 1. Check for Photo
        if (preg_match('~/photo/\d+$~', $real_url)) {
            $response_data['is_photo'] = true;
        }

        // 2. Check for Video (e.g. /status/123456/video/1)
        elseif (preg_match('~/status/(\d+)/video/(\d+)~', $real_url, $v_matches)) {
            $tweet_id = $v_matches[1];

            // Fetch video details from RapidAPI
            // We need to include config to get the key, or use the one we just read.
            // Let's try to include config.php safely.
            if (file_exists(__DIR__ . '/../src/config.php')) {
                require_once __DIR__ . '/../src/config.php';
            }

            if (defined('RAPID_API_KEY')) {
                $api_url = "https://twitter-api45.p.rapidapi.com/tweet.php?id=" . $tweet_id;

                $ch_api = curl_init();
                curl_setopt($ch_api, CURLOPT_URL, $api_url);
                curl_setopt($ch_api, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_api, CURLOPT_HTTPHEADER, [
                    'x-rapidapi-host: twitter-api45.p.rapidapi.com',
                    'x-rapidapi-key: ' . RAPID_API_KEY
                ]);
                curl_setopt($ch_api, CURLOPT_TIMEOUT, 10);

                $api_response = curl_exec($ch_api);
                curl_close($ch_api);

                $tweet_data = json_decode($api_response, true);

                // Helper to extract video from a tweet object
                function extract_video_info($data)
                {
                    if (isset($data['media']['video'][0])) {
                        return $data['media']['video'][0];
                    }
                    // Sometimes it might be in extended_entities or just entities (though RapidAPI usually normalizes to media)
                    // Let's check for quoted status video
                    if (isset($data['quoted']['media']['video'][0])) {
                        return $data['quoted']['media']['video'][0];
                    }
                    return null;
                }

                $video_info = extract_video_info($tweet_data);

                if ($video_info) {
                    $response_data['is_video'] = true;
                    $response_data['thumbnail'] = $video_info['media_url_https'] ?? '';

                    // Find best variant (mp4 with highest bitrate)
                    $best_variant = null;
                    $max_bitrate = -1;

                    if (isset($video_info['variants'])) {
                        foreach ($video_info['variants'] as $variant) {
                            if (isset($variant['content_type']) && $variant['content_type'] === 'video/mp4') {
                                $bitrate = $variant['bitrate'] ?? 0;
                                if ($bitrate > $max_bitrate) {
                                    $max_bitrate = $bitrate;
                                    $best_variant = $variant['url'];
                                }
                            }
                        }
                    }
                    // Fallback to m3u8 if no mp4 found (rare but possible)
                    if (!$best_variant && isset($video_info['variants'][0]['url'])) {
                        $best_variant = $video_info['variants'][0]['url'];
                    }

                    $response_data['video_url'] = $best_variant;
                }
            }
        }

        // Cache this result for a long time
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Content-Type: application/json');
        echo json_encode($response_data);
        exit;
    }
}

// Fallback if no redirect found or error
header('Content-Type: application/json');
echo json_encode(['url' => $url]); // Return original if failed
exit;
