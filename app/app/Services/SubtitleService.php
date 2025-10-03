<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final readonly class SubtitleService
{
    public function __construct(
        private OpenSubtitlesClient $openSubtitlesClient
    ) {}

    public function getSubtitles(array $params): array
    {
        try {
            $subtitleFilePath = $this->openSubtitlesClient->downloadSubtitle($params);

            if (! $subtitleFilePath) {
                Log::warning('No subtitle file available', $params);

                return [];
            }

            $subtitles = $this->parseSrtFile($subtitleFilePath);

            Log::info('Retrieved all subtitles', [
                'total_subtitles' => count($subtitles),
            ]);

            return $subtitles;
        } catch (Exception $exception) {
            Log::error('Failed to get subtitles', [
                'params' => $params,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    public function getSubtitlesUpToMinute(array $params, int $currentMinute): array
    {
        try {
            $subtitleFilePath = $this->openSubtitlesClient->downloadSubtitle($params);

            if (! $subtitleFilePath) {
                Log::warning('No subtitle file available', $params);

                return [];
            }

            $subtitles = $this->parseSrtFile($subtitleFilePath);

            $filteredSubtitles = $this->filterSubtitlesUpToMinute($subtitles, $currentMinute);

            Log::info('Filtered subtitles successfully', [
                'total_subtitles' => count($subtitles),
                'filtered_count' => count($filteredSubtitles),
                'current_minute' => $currentMinute,
            ]);

            return $filteredSubtitles;
        } catch (Exception $exception) {
            Log::error('Failed to get subtitles', [
                'params' => $params,
                'current_minute' => $currentMinute,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function parseSrtFile(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new Exception("Subtitle file not found: {$filePath}");
        }

        $content = File::get($filePath);
        $subtitles = [];

        $blocks = preg_split('/\r?\n\r?\n/', mb_trim($content));

        foreach ($blocks as $block) {
            if (empty(mb_trim($block))) {
                continue;
            }

            $lines = preg_split('/\r?\n/', mb_trim($block));

            if (count($lines) < 3) {
                continue;
            }

            $timestampLine = $lines[1];

            if (preg_match('/(\d{2}:\d{2}:\d{2},\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2},\d{3})/', $timestampLine, $matches)) {
                $startTime = $matches[1];
                $endTime = $matches[2];

                $text = implode("\n", array_slice($lines, 2));

                $subtitles[] = [
                    'start' => $startTime,
                    'end' => $endTime,
                    'start_seconds' => $this->timeToSeconds($startTime),
                    'end_seconds' => $this->timeToSeconds($endTime),
                    'text' => mb_trim($text),
                ];
            }
        }

        Log::debug('Parsed SRT file', [
            'file_path' => $filePath,
            'subtitle_count' => count($subtitles),
        ]);

        return $subtitles;
    }

    private function filterSubtitlesUpToMinute(array $subtitles, int $currentMinute): array
    {
        $maxSeconds = ($currentMinute + 1) * 60;

        return array_values(
            array_filter($subtitles, fn (array $subtitle): bool => $subtitle['start_seconds'] < $maxSeconds)
        );
    }

    private function timeToSeconds(string $timestamp): int
    {
        $timestamp = str_replace(',', '.', $timestamp);

        if (preg_match('/(\d{2}):(\d{2}):(\d{2})\.(\d{3})/', $timestamp, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            $seconds = (int) $matches[3];

            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        return 0;
    }
}
