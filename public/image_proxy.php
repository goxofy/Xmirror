<?php

// Basic security checks
if (!isset($_GET['url'])) {
    http_response_code(400);
    die('Missing URL parameter.');
}

$image_url = $_GET['url'];

// Validate that the URL is a valid pbs.twimg.com URL
// We allow http or https, and ensure it points to pbs.twimg.com
if (!preg_match("~^https?://pbs\.twimg\.com/~", $image_url)) {
    http_response_code(403);
    die('Invalid or forbidden URL.');
}

// Fetch the image content using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $image_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Often needed on cheap hosts
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; TwitterMirror/1.0)');

// Forward headers if needed, but usually just fetching content is enough.
// We might want to pass through the content type from the upstream response.

$image_data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code !== 200 || $image_data === false) {
    http_response_code(502);
    die('Failed to fetch image from source.');
}

// Use the content type from cURL, or default to jpeg
if (empty($content_type)) {
    $content_type = 'image/jpeg';
}

// Serve the image to the user
header('Content-Type: ' . $content_type);
header('Content-Length: ' . strlen($image_data));
// Cache for 1 year (Twitter images are immutable)
header('Cache-Control: public, max-age=31536000, immutable');

echo $image_data;
exit;