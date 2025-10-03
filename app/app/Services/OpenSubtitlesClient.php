<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class OpenSubtitlesClient
{
    private string $baseUrl;

    private string $apiKey;

    private string $userAgent;

    private int $timeout;

    private string $cachePath;

    public function __construct()
    {
        $this->baseUrl = config('opensubtitles.base_url');
        $this->apiKey = config('opensubtitles.api_key');
        $this->userAgent = config('opensubtitles.user_agent');
        $this->timeout = config('opensubtitles.timeout', 30);
        $this->cachePath = config('opensubtitles.cache_path');

        $this->ensureCacheDirectoryExists();
    }

    public function downloadSubtitle(array $params): ?string
    {
        $cacheKey = $this->generateCacheKey($params);
        $cachedFilePath = $this->getCachedFilePath($cacheKey);

        if (File::exists($cachedFilePath)) {
            Log::info('Using cached subtitle file', [
                'cache_key' => $cacheKey,
                'file_path' => $cachedFilePath,
            ]);

            return $cachedFilePath;
        }

        try {
            $searchResults = $this->searchSubtitles($params);

            if ($searchResults === []) {
                Log::warning('No subtitles found for search parameters', $params);

                return null;
            }

            $bestSubtitle = $searchResults[0];
            $fileId = $bestSubtitle['attributes']['files'][0]['file_id'] ?? null;

            if (! $fileId) {
                Log::warning('No file_id found in subtitle search results', [
                    'subtitle' => $bestSubtitle,
                ]);

                return null;
            }

            $subtitleContent = $this->downloadSubtitleFile($fileId);

            if (! $subtitleContent) {
                return null;
            }

            File::put($cachedFilePath, $subtitleContent);

            Log::info('Downloaded and cached subtitle file', [
                'cache_key' => $cacheKey,
                'file_path' => $cachedFilePath,
                'file_id' => $fileId,
            ]);

            return $cachedFilePath;
        } catch (Exception $exception) {
            Log::error('Failed to download subtitle', [
                'params' => $params,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function searchSubtitles(array $params): array
    {
        $queryParams = [
            'languages' => $params['language'] ?? 'en',
        ];

        if (isset($params['imdb_id'])) {
            $queryParams['imdb_id'] = $params['imdb_id'];
        } elseif (isset($params['title'])) {
            $queryParams['query'] = $params['title'];
        } else {
            throw new Exception('Either imdb_id or title must be provided');
        }

        Log::debug('Searching OpenSubtitles API', [
            'query_params' => $queryParams,
        ]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Api-Key' => $this->apiKey,
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
            ])
            ->get("{$this->baseUrl}/subtitles", $queryParams);

        if ($response->failed()) {
            Log::error('OpenSubtitles API search request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'query_params' => $queryParams,
            ]);

            throw new Exception("OpenSubtitles API search failed: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();
        $results = $data['data'] ?? [];

        Log::info('OpenSubtitles API search completed', [
            'result_count' => count($results),
        ]);

        return $results;
    }

    private function downloadSubtitleFile(int $fileId): ?string
    {
        Log::debug('Downloading subtitle file', [
            'file_id' => $fileId,
        ]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Api-Key' => $this->apiKey,
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
            ])
            ->post("{$this->baseUrl}/download", [
                'file_id' => $fileId,
            ]);

        if ($response->failed()) {
            Log::error('OpenSubtitles API download request failed', [
                'file_id' => $fileId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception("OpenSubtitles API download failed: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();
        $downloadLink = $data['link'] ?? null;

        if (! $downloadLink) {
            Log::error('No download link in API response', [
                'file_id' => $fileId,
                'response' => $data,
            ]);

            return null;
        }

        $subtitleResponse = Http::timeout($this->timeout)->get($downloadLink);

        if ($subtitleResponse->failed()) {
            Log::error('Failed to download subtitle file from link', [
                'download_link' => $downloadLink,
                'status' => $subtitleResponse->status(),
            ]);

            return null;
        }

        Log::info('Successfully downloaded subtitle file', [
            'file_id' => $fileId,
            'content_length' => mb_strlen($subtitleResponse->body()),
        ]);

        return $subtitleResponse->body();
    }

    private function generateCacheKey(array $params): string
    {
        $key = '';

        if (isset($params['imdb_id'])) {
            $key .= "imdb_{$params['imdb_id']}";
        } elseif (isset($params['title'])) {
            $key .= 'title_'.md5($params['title']);
        }

        $language = $params['language'] ?? 'en';

        return $key."_{$language}";
    }

    private function getCachedFilePath(string $cacheKey): string
    {
        return "{$this->cachePath}/{$cacheKey}.srt";
    }

    private function ensureCacheDirectoryExists(): void
    {
        if (! File::exists($this->cachePath)) {
            File::makeDirectory($this->cachePath, 0755, true);
        }
    }
}
