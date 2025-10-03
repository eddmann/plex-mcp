<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Plex;

use App\Services\SubtitleService;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class SearchSubtitlesTool extends Tool
{
    protected string $description = 'Search and retrieve subtitles for a movie or TV show to understand what has happened up to a given timestamp. Provide either an IMDb ID or title to search for subtitles, optionally filtered by language and playback position in minutes.';

    public function handle(Request $request, SubtitleService $subtitleService): Response
    {
        try {
            $imdbId = $request->get('imdb_id');
            $title = $request->get('title');
            $language = $request->get('language');
            $minutesElapsed = $request->get('minutes_elapsed');

            Log::info('SearchSubtitlesTool: Starting tool execution', [
                'imdb_id' => $imdbId,
                'title' => $title,
                'language' => $language,
                'minutes_elapsed' => $minutesElapsed,
            ]);

            if (! $imdbId && ! $title) {
                Log::warning('SearchSubtitlesTool: Neither imdb_id nor title provided');

                return Response::text(json_encode([
                    'status' => 'error',
                    'message' => 'Either imdb_id or title must be provided.',
                ]));
            }

            if ($imdbId && $title) {
                Log::warning('SearchSubtitlesTool: Both imdb_id and title provided');

                return Response::text(json_encode([
                    'status' => 'error',
                    'message' => 'Only one of imdb_id or title should be provided, not both.',
                ]));
            }

            $params = [];
            if ($imdbId) {
                $params['imdb_id'] = $imdbId;
            }
            if ($title) {
                $params['title'] = $title;
            }
            if ($language) {
                $params['language'] = $language;
            }

            if ($minutesElapsed !== null) {
                $subtitles = $subtitleService->getSubtitlesUpToMinute($params, (int) $minutesElapsed);
            } else {
                $subtitles = $subtitleService->getSubtitles($params);
            }

            if ($subtitles === []) {
                Log::info('SearchSubtitlesTool: No subtitles found', $params);

                return Response::text(json_encode([
                    'status' => 'success',
                    'message' => 'No subtitles found for the given parameters.',
                    'params' => $params,
                    'subtitle_count' => 0,
                    'subtitles' => [],
                ]));
            }

            Log::info('SearchSubtitlesTool: Successfully retrieved subtitles', [
                'params' => $params,
                'subtitle_count' => count($subtitles),
                'minutes_elapsed' => $minutesElapsed,
            ]);

            $result = [
                'status' => 'success',
                'message' => sprintf('Found %d subtitle entries', count($subtitles)),
                'params' => $params,
                'subtitle_count' => count($subtitles),
                'subtitles' => $subtitles,
            ];

            if ($minutesElapsed !== null) {
                $result['minutes_elapsed'] = (int) $minutesElapsed;
            }

            return Response::text(json_encode($result));
        } catch (Exception $exception) {
            Log::error('SearchSubtitlesTool: Tool execution failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return Response::text(json_encode([
                'status' => 'error',
                'message' => 'Error searching subtitles: '.$exception->getMessage(),
            ]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'imdb_id' => $schema->string()
                ->description('IMDb ID for the movie or TV show (e.g., "tt1234567"). Use this OR title, not both.'),

            'title' => $schema->string()
                ->description('Title of the movie or TV show to search for. Use this OR imdb_id, not both.'),

            'language' => $schema->string()
                ->description('Optional language code for subtitles (e.g., "en", "es", "fr"). Defaults to English if not specified.'),

            'minutes_elapsed' => $schema->integer()
                ->description('Optional playback position in minutes. If provided, only returns subtitles up to and including this minute. If omitted, returns all subtitles.'),
        ];
    }
}
