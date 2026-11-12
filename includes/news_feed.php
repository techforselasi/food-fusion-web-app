<?php

declare(strict_types=1);

/**
 * Home “news feed” recipe cards via TheMealDB JSON API (free, no API key).
 * https://www.themealdb.com/api.php
 */

/**
 * @return array<string, mixed>|null
 */
function ff_news_feed_fetch_json(string $url): ?array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        // Keep this short: home page should fail fast and use DB fallback.
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $raw = curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $http < 200 || $http >= 300) {
        error_log('FoodFusion news feed fetch failed HTTP ' . $http . ' ' . $curlErr);
        return null;
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

/**
 * @return list<array{
 *   title:string,
 *   summary:string,
 *   image_url:?string,
 *   source_url:?string,
 *   published_at:?string
 * }>
 */
function ff_news_feed_items(int $limit = 6): array
{
    $limit = max(1, min(12, $limit));
    $letters = range('a', 'z');
    shuffle($letters);
    $maxAttempts = 4;
    $attempts = 0;
    $startedAt = microtime(true);
    $maxRuntimeSeconds = 8.0;

    /** @var array<string, array{title:string,summary:string,image_url:?string,source_url:?string,published_at:?string}> $byId */
    $byId = [];

    foreach ($letters as $letter) {
        if (count($byId) >= $limit) {
            break;
        }
        if ($attempts >= $maxAttempts) {
            break;
        }
        if ((microtime(true) - $startedAt) >= $maxRuntimeSeconds) {
            break;
        }

        $url = 'https://www.themealdb.com/api/json/v1/1/search.php?s=' . rawurlencode($letter);
        $attempts++;
        $json = ff_news_feed_fetch_json($url);
        if ($json === null || !isset($json['meals']) || !is_array($json['meals']) || $json['meals'] === []) {
            continue;
        }

        foreach ($json['meals'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = trim((string) ($row['idMeal'] ?? ''));
            $title = trim((string) ($row['strMeal'] ?? ''));
            if ($id === '' || $title === '') {
                continue;
            }
            if (isset($byId[$id])) {
                continue;
            }

            $instr = trim((string) ($row['strInstructions'] ?? ''));
            $instr = preg_replace('/\s+/', ' ', $instr) ?: '';
            $summary = $instr !== ''
                ? $instr
                : 'Recipe highlight from TheMealDB — open the card for ingredients and full steps.';
            if (function_exists('mb_strlen') && mb_strlen($summary) > 180) {
                $summary = mb_substr($summary, 0, 177) . '...';
            } elseif (strlen($summary) > 180) {
                $summary = substr($summary, 0, 177) . '...';
            }

            $img = trim((string) ($row['strMealThumb'] ?? ''));
            $source = trim((string) ($row['strSource'] ?? ''));
            $yt = trim((string) ($row['strYoutube'] ?? ''));

            if ($source !== '' && preg_match('#^https?://#i', $source)) {
                $link = $source;
            } elseif ($yt !== '' && str_contains(strtolower($yt), 'youtube')) {
                $link = $yt;
            } else {
                $link = 'https://www.themealdb.com/meal/' . rawurlencode($id);
            }

            $byId[$id] = [
                'title' => $title,
                'summary' => $summary,
                'image_url' => $img !== '' ? $img : null,
                'source_url' => $link,
                'published_at' => null,
            ];

            if (count($byId) >= $limit) {
                break 2;
            }
        }
    }

    return array_slice(array_values($byId), 0, $limit);
}
