<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use App\Services\PlexClient;
use App\Services\SubtitleService;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

final class WhatHaveIMissedPrompt extends Prompt
{
    protected string $name = 'what-have-i-missed';

    protected string $title = 'What Have I Missed';

    protected string $description = 'Provides a concise and informative summary of what you have missed in the currently playing movie or TV show.';

    public function handle(Request $request, PlexClient $plexClient, SubtitleService $subtitleService): array
    {
        try {
            Log::info('WhatHaveIMissedPrompt: Starting prompt execution');

            $sessions = $plexClient->getActiveSessions();

            if ($sessions === []) {
                Log::warning('WhatHaveIMissedPrompt: No active sessions found');

                return [
                    Response::text('You are a helpful Plex media assistant.')->asAssistant(),
                    Response::text('There are currently no active playback sessions. Please start playing something on Plex and try again.'),
                ];
            }

            $sessionIndex = $request->integer('session_index', 0);

            if (! isset($sessions[$sessionIndex])) {
                Log::warning('WhatHaveIMissedPrompt: Invalid session index', [
                    'requested_index' => $sessionIndex,
                    'available_sessions' => count($sessions),
                ]);

                return [
                    Response::text('You are a helpful Plex media assistant.')->asAssistant(),
                    Response::text(sprintf(
                        'Invalid session index %d. There are %d active sessions (indexed 0-%d).',
                        $sessionIndex,
                        count($sessions),
                        count($sessions) - 1
                    )),
                ];
            }

            $session = $sessions[$sessionIndex];

            $itemType = $session['type'] ?? 'unknown';
            $title = $session['title'] ?? 'Unknown';
            $imdbId = $session['guid'] ?? null;

            $imdbId = $imdbId && str_contains((string) $imdbId, 'imdb://') ? str_replace('imdb://', '', $imdbId) : null;

            $viewOffsetMs = $session['viewOffset'] ?? 0;
            $minutesElapsed = (int) ceil($viewOffsetMs / 1000 / 60);

            Log::info('WhatHaveIMissedPrompt: Retrieved session information', [
                'type' => $itemType,
                'title' => $title,
                'imdb_id' => $imdbId,
                'minutes_elapsed' => $minutesElapsed,
            ]);

            $contentDescription = $this->buildContentDescription($session, $itemType, $title);

            $params = [];
            if ($imdbId) {
                $params['imdb_id'] = $imdbId;
            } else {
                $params['title'] = $title;
            }

            $subtitles = [];
            if ($minutesElapsed > 0) {
                $subtitles = $subtitleService->getSubtitlesUpToMinute($params, $minutesElapsed);
            }

            if ($subtitles === []) {
                Log::info('WhatHaveIMissedPrompt: No subtitles available', [
                    'params' => $params,
                    'minutes_elapsed' => $minutesElapsed,
                ]);

                return [
                    Response::text('You are a helpful Plex media assistant.')->asAssistant(),
                    Response::text(sprintf(
                        'You are currently watching %s at %d minutes in. Unfortunately, subtitles are not available for this content, so I cannot provide a summary of what you have missed.',
                        $contentDescription,
                        $minutesElapsed
                    )),
                ];
            }

            $subtitleText = $this->formatSubtitles($subtitles);

            Log::info('WhatHaveIMissedPrompt: Successfully generated prompt', [
                'content_description' => $contentDescription,
                'minutes_elapsed' => $minutesElapsed,
                'subtitle_count' => count($subtitles),
            ]);

            $systemMessage = <<<'SYSTEM'
You are a helpful Plex media assistant. When asked to summarize what someone has missed in a movie or TV show, you should:
1. Provide a concise, chronological summary of the key plot points and events
2. Focus on the most important story developments
3. Use clear, accessible language
4. Keep the summary brief but informative (2-4 paragraphs maximum)
5. Avoid unnecessary detail while capturing the essence of what happened
SYSTEM;

            $userMessage = sprintf(
                <<<'USER'
I'm watching %s and I'm currently at %d minutes into the content. Here are the subtitles up to this point:

%s

Please provide a concise summary of what I've missed so far. Focus on the key plot points and important story developments.
USER,
                $contentDescription,
                $minutesElapsed,
                $subtitleText
            );

            return [
                Response::text($systemMessage)->asAssistant(),
                Response::text($userMessage),
            ];
        } catch (Exception $exception) {
            Log::error('WhatHaveIMissedPrompt: Prompt execution failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                Response::text('You are a helpful Plex media assistant.')->asAssistant(),
                Response::text('An error occurred while trying to determine what you have missed: '.$exception->getMessage()),
            ];
        }
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'session_index',
                description: 'The index of the active session to use (0-based). Defaults to the first session (0) if multiple sessions are active.',
                required: false,
            ),
        ];
    }

    private function buildContentDescription(array $session, string $itemType, string $title): string
    {
        if ($itemType === 'episode') {
            $show = $session['grandparentTitle'] ?? 'Unknown Show';
            $seasonNum = $session['parentIndex'] ?? '?';
            $episodeNum = $session['index'] ?? '?';

            return sprintf(
                '%s - S%sE%s - %s',
                $show,
                $seasonNum,
                $episodeNum,
                $title
            );
        }

        if ($itemType === 'movie') {
            $year = $session['year'] ?? '';

            return sprintf('%s (%s)', $title, $year);
        }

        return $title;
    }

    private function formatSubtitles(array $subtitles): string
    {
        $formatted = [];

        foreach ($subtitles as $subtitle) {
            $formatted[] = sprintf(
                '[%s] %s',
                $subtitle['start'],
                $subtitle['text']
            );
        }

        return implode("\n", $formatted);
    }
}
