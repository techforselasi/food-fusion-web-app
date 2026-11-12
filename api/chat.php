<?php

declare(strict_types=1);

/**
 * Server-side proxy to Hugging Face Inference Providers (OpenAI-compatible chat completions).
 * Legacy api-inference.huggingface.co is deprecated (410); this uses router.huggingface.co/v1.
 * Configure via hf_secrets.php or HUGGINGFACE_API_TOKEN / HF_TOKEN env.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$message = trim((string) ($data['message'] ?? ''));
if ($message === '' || strlen($message) > 4000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Message missing or too long']);
    exit;
}

$context = $data['context'] ?? null;
if (!is_array($context)) {
    $context = [];
}

$root = dirname(__DIR__);
$defaultModel = 'openai/gpt-oss-120b:fastest';
$token = (string) (getenv('HUGGINGFACE_API_TOKEN') ?: getenv('HF_TOKEN') ?: '');
$model = $defaultModel;

$secretsPath = $root . '/hf_secrets.php';
if (is_readable($secretsPath)) {
    $sec = require $secretsPath;
    if (is_array($sec)) {
        if ($token === '' && !empty($sec['token'])) {
            $token = (string) $sec['token'];
        }
        if (!empty($sec['model'])) {
            $model = (string) $sec['model'];
        }
    }
}

if ($token === '' || str_starts_with($token, 'hf_PASTE_')) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Assistant is not configured. Copy hf_secrets.example.php to hf_secrets.php and add your Hugging Face token.',
        'code' => 'no_token',
    ]);
    exit;
}

$system = 'You are a warm, practical kitchen assistant for FoodFusion, a site about global home cooking. '
    . 'Give concise answers (roughly 2–6 short sentences unless the user asks for more). '
    . 'Focus on tips, substitutions, technique, timing, and food safety when relevant. '
    . 'If you are unsure, say so. Do not claim to have seen the full recipe text unless it was pasted in the question.';

if (($context['page'] ?? '') === 'recipe') {
    $rt = trim((string) ($context['recipeTitle'] ?? ''));
    $cu = trim((string) ($context['cuisine'] ?? ''));
    if ($rt !== '') {
        $system .= ' The user is currently viewing a recipe titled: "' . $rt . '".';
    }
    if ($cu !== '') {
        $system .= ' Cuisine context: ' . $cu . '.';
    }
}

$url = 'https://router.huggingface.co/v1/chat/completions';
$payload = json_encode([
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $message],
    ],
    'max_tokens' => 512,
    'temperature' => 0.7,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 120,
]);

$responseBody = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false || $responseBody === '') {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Network error contacting AI service.', 'detail' => $curlErr]);
    exit;
}

$decoded = json_decode($responseBody, true);

if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error']) && $httpCode === 200) {
    $loadingHint = strtolower($decoded['error']);
    if (str_contains($loadingHint, 'loading') || str_contains($loadingHint, 'unavailable')) {
        http_response_code(503);
        echo json_encode([
            'ok' => false,
            'error' => 'The model is starting up. Wait 20–60 seconds and try again.',
            'detail' => $decoded['error'],
        ]);
        exit;
    }
}

if ($httpCode === 503 || $httpCode === 429) {
    $hint = ff_hf_error_message($decoded);
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'The model is busy or rate-limited. Wait a moment and try again.',
        'detail' => $hint,
    ]);
    exit;
}

if ($httpCode >= 400) {
    $errMsg = ff_hf_error_message($decoded) ?: 'Request failed.';
    if ($httpCode === 401) {
        $errMsg = 'Invalid or unauthorized Hugging Face token. Check hf_secrets.php and token permissions (Inference Providers).';
    }
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $errMsg]);
    exit;
}

$reply = ff_hf_extract_chat_reply($decoded);
if ($reply === null || $reply === '') {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Unexpected response from AI service. Try another model in hf_secrets.php.']);
    exit;
}

echo json_encode(['ok' => true, 'reply' => $reply]);

/**
 * @param mixed $decoded JSON body from HF router
 */
function ff_hf_error_message($decoded): string
{
    if (!is_array($decoded)) {
        return '';
    }
    if (isset($decoded['message']) && is_string($decoded['message'])) {
        return $decoded['message'];
    }
    $e = $decoded['error'] ?? null;
    if (is_string($e)) {
        return $e;
    }
    if (is_array($e) && isset($e['message']) && is_string($e['message'])) {
        return $e['message'];
    }

    return '';
}

/**
 * OpenAI-style chat completion: choices[0].message.content (string or content parts).
 *
 * @param mixed $decoded
 */
function ff_hf_extract_chat_reply($decoded): ?string
{
    if (!is_array($decoded)) {
        return null;
    }

    $choices = $decoded['choices'] ?? null;
    if (!is_array($choices) || !isset($choices[0]) || !is_array($choices[0])) {
        return null;
    }

    $msg = $choices[0]['message'] ?? null;
    if (!is_array($msg) || !array_key_exists('content', $msg)) {
        return null;
    }

    $content = $msg['content'];
    if (is_string($content)) {
        $t = trim($content);
        return $t !== '' ? $t : null;
    }

    if (is_array($content)) {
        $parts = [];
        foreach ($content as $part) {
            if (is_array($part) && isset($part['type']) && $part['type'] === 'text' && isset($part['text'])) {
                $parts[] = (string) $part['text'];
            } elseif (is_string($part)) {
                $parts[] = $part;
            }
        }
        $t = trim(implode('', $parts));
        return $t !== '' ? $t : null;
    }

    return null;
}
