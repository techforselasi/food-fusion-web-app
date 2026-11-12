<?php

declare(strict_types=1);

/**
 * Cloudinary: credentials, signed upload, and recipe image persistence.
 * Credentials: `cloudinary` array in gitignored hf_secrets.php, env vars CLOUDINARY_*,
 * or the defaults array below.
 */

// Optional defaults; prefer `cloudinary` in gitignored hf_secrets.php or CLOUDINARY_* env vars.
$GLOBALS['_ff_cloudinary'] = [
    'cloud_name' => '',
    'api_key' => '',
    'api_secret' => '',
    'folder' => 'foodfusion/recipes',
];

/**
 * @return array<string, mixed>|null
 */
function ff_cloudinary_config(): ?array
{
    static $resolved = null;
    static $done = false;

    if ($done) {
        return $resolved;
    }
    $done = true;

    $base = $GLOBALS['_ff_cloudinary'] ?? [];
    if (!is_array($base)) {
        $base = [];
    }

    $secretsPath = dirname(__DIR__) . '/hf_secrets.php';
    if (is_readable($secretsPath)) {
        $hf = require $secretsPath;
        if (is_array($hf) && isset($hf['cloudinary']) && is_array($hf['cloudinary'])) {
            $base = array_merge($base, $hf['cloudinary']);
        }
    }

    $fromEnv = static function (string $key, string $fallback): string {
        $v = getenv($key);
        if ($v !== false && $v !== '') {
            return trim($v);
        }

        return trim($fallback);
    };

    $resolved = [
        'cloud_name' => $fromEnv('CLOUDINARY_CLOUD_NAME', (string) ($base['cloud_name'] ?? '')),
        'api_key' => $fromEnv('CLOUDINARY_API_KEY', (string) ($base['api_key'] ?? '')),
        'api_secret' => $fromEnv('CLOUDINARY_API_SECRET', (string) ($base['api_secret'] ?? '')),
        'folder' => $fromEnv('CLOUDINARY_FOLDER', (string) ($base['folder'] ?? 'foodfusion/recipes')),
    ];

    return $resolved;
}

function ff_media_uploads_enabled(): bool
{
    $c = ff_cloudinary_config();
    if ($c === null) {
        return false;
    }
    $name = (string) ($c['cloud_name'] ?? '');
    $key = (string) ($c['api_key'] ?? '');
    $secret = (string) ($c['api_secret'] ?? '');

    return $name !== '' && $key !== '' && $secret !== '';
}

/**
 * Build Cloudinary API signature (parameters except file, cloud_name, resource_type, api_key).
 *
 * @param array<string, string|int> $params
 */
function ff_cloudinary_sign_params(array $params, string $apiSecret): string
{
    ksort($params);
    $pairs = [];
    foreach ($params as $k => $v) {
        $pairs[] = $k . '=' . $v;
    }

    return sha1(implode('&', $pairs) . $apiSecret);
}

/**
 * Upload a recipe image to Cloudinary and insert a `media` row.
 *
 * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file One $_FILES entry
 * @return int|null New media_id or null on failure / not configured
 */
function ff_cloudinary_upload_recipe_image(PDO $conn, int $recipeId, int $userId, array $file): ?int
{
    if (!ff_media_uploads_enabled() || !ff_table_exists($conn, 'media')) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }

    $cfg = ff_cloudinary_config();
    if ($cfg === null) {
        return null;
    }

    $cloud = (string) $cfg['cloud_name'];
    $apiKey = (string) $cfg['api_key'];
    $apiSecret = (string) $cfg['api_secret'];
    $folder = trim((string) ($cfg['folder'] ?? ''));
    if ($cloud === '' || $apiKey === '' || $apiSecret === '') {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        return null;
    }

    $timestamp = time();
    $signParams = ['timestamp' => $timestamp];
    if ($folder !== '') {
        $signParams['folder'] = $folder;
    }
    $signature = ff_cloudinary_sign_params($signParams, $apiSecret);

    $url = 'https://api.cloudinary.com/v1_1/' . rawurlencode($cloud) . '/image/upload';

    $postFields = [
        'file' => new CURLFile($tmp, $mime, basename((string) $file['name'])),
        'api_key' => $apiKey,
        'timestamp' => (string) $timestamp,
        'signature' => $signature,
    ];
    if ($folder !== '') {
        $postFields['folder'] = $folder;
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $code < 200 || $code >= 300) {
        error_log('FoodFusion Cloudinary upload HTTP ' . $code . ': ' . substr((string) $raw, 0, 500));
        return null;
    }

    $json = json_decode($raw, true);
    if (!is_array($json) || empty($json['public_id']) || empty($json['secure_url'])) {
        error_log('FoodFusion Cloudinary upload unexpected response: ' . substr((string) $raw, 0, 500));
        return null;
    }

    $publicId = (string) $json['public_id'];
    $secureUrl = (string) $json['secure_url'];
    $width = isset($json['width']) ? (int) $json['width'] : null;
    $height = isset($json['height']) ? (int) $json['height'] : null;
    $format = isset($json['format']) ? (string) $json['format'] : null;
    $bytes = isset($json['bytes']) ? (int) $json['bytes'] : null;

    try {
        $stmt = $conn->prepare(
            'INSERT INTO media (
                recipe_id, user_id, cloudinary_public_id, secure_url,
                width, height, format, bytes, resource_type, alt_text, is_primary, sort_order
            ) VALUES (
                :rid, :uid, :pid, :url,
                :w, :h, :fmt, :bytes, :rtype, :alt, 1, 0
            )'
        );
        $stmt->execute([
            ':rid' => $recipeId,
            ':uid' => $userId,
            ':pid' => $publicId,
            ':url' => $secureUrl,
            ':w' => $width,
            ':h' => $height,
            ':fmt' => $format,
            ':bytes' => $bytes,
            ':rtype' => 'image',
            ':alt' => null,
        ]);

        return (int) $conn->lastInsertId();
    } catch (PDOException $e) {
        return null;
    }
}
