<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Plex;

use App\Services\PlexClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class GetActiveSessionsTool extends Tool
{
    protected string $description = 'Get information about current playback sessions on the Plex server, including user details, player information, transcoding status, and playback progress.';

    public function handle(): Response
    {
        try {
            Log::info('GetActiveSessionsTool: Starting tool execution');

            $plexClient = new PlexClient;
            $sessions = $plexClient->getActiveSessions();

            if ($sessions === []) {
                Log::info('GetActiveSessionsTool: No active sessions found');

                return Response::text(json_encode([
                    'status' => 'success',
                    'message' => 'No active sessions found.',
                    'sessions_count' => 0,
                    'sessions' => [],
                ]));
            }

            $sessionsData = [];
            $transcodeCount = 0;
            $directPlayCount = 0;
            $totalBitrate = 0;

            foreach ($sessions as $index => $session) {
                $sessionInfo = $this->parseSession($session, $index + 1);

                if ($sessionInfo['transcoding']['active']) {
                    $transcodeCount++;
                } else {
                    $directPlayCount++;
                }

                if (isset($sessionInfo['media_info']['bitrate_kbps'])) {
                    $totalBitrate += $sessionInfo['media_info']['bitrate_kbps'];
                }

                $sessionsData[] = $sessionInfo;
            }

            $result = [
                'status' => 'success',
                'message' => sprintf('Found %d active sessions', count($sessions)),
                'sessions_count' => count($sessions),
                'transcode_count' => $transcodeCount,
                'direct_play_count' => $directPlayCount,
                'total_bitrate_kbps' => $totalBitrate,
                'sessions' => $sessionsData,
            ];

            Log::info('GetActiveSessionsTool: Successfully retrieved and parsed sessions', [
                'sessions_count' => count($sessions),
                'transcode_count' => $transcodeCount,
                'direct_play_count' => $directPlayCount,
                'total_bitrate_kbps' => $totalBitrate,
            ]);

            return Response::text(json_encode($result));
        } catch (Exception $exception) {
            Log::error('GetActiveSessionsTool: Tool execution failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return Response::text(json_encode([
                'status' => 'error',
                'message' => 'Error getting active sessions: '.$exception->getMessage(),
            ]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    private function parseSession(array $session, int $sessionId): array
    {
        $itemType = $session['type'] ?? 'unknown';
        $title = $session['title'] ?? 'Unknown';

        $player = $session['Player'] ?? [];
        $user = $session['User'] ?? [];

        $sessionInfo = [
            'session_id' => $sessionId,
            'state' => $player['state'] ?? 'unknown',
            'player_name' => $player['title'] ?? 'Unknown Player',
            'user' => $user['title'] ?? 'Unknown User',
            'content_type' => $itemType,
            'duration' => (int) (($session['duration'] ?? 0) / 1000 / 60),
        ];

        if ($itemType === 'episode') {
            $show = $session['grandparentTitle'] ?? 'Unknown Show';
            $seasonNum = $session['parentIndex'] ?? '?';
            $episodeNum = $session['index'] ?? '?';
            $sessionInfo['content_description'] = sprintf(
                '%s - S%sE%s - %s (TV Episode)',
                $show,
                $seasonNum,
                $episodeNum,
                $title
            );
        } elseif ($itemType === 'movie') {
            $year = $session['year'] ?? '';
            $sessionInfo['content_description'] = sprintf(
                '%s (%s) (Movie)',
                $title,
                $year
            );
            $sessionInfo['year'] = $year;
        } else {
            $sessionInfo['content_description'] = sprintf('%s (%s)', $title, $itemType);
        }

        $sessionInfo['player'] = [
            'ip' => $player['address'] ?? null,
            'platform' => $player['platform'] ?? null,
            'product' => $player['product'] ?? null,
            'device' => $player['device'] ?? null,
            'version' => $player['version'] ?? null,
        ];

        if (isset($session['viewOffset'], $session['duration']) && $session['duration'] > 0) {
            $progress = ($session['viewOffset'] / $session['duration']) * 100;
            $secondsRemaining = ($session['duration'] - $session['viewOffset']) / 1000;
            $minutesRemaining = (int) ($secondsRemaining / 60);

            $sessionInfo['progress'] = [
                'percent' => round($progress, 1),
                'minutes_remaining' => max(0, $minutesRemaining),
                'minutes_elapsed' => (int) ceil(($session['viewOffset'] / 1000 / 60)),
            ];
        }

        $media = $session['Media'][0] ?? null;
        if ($media) {
            $sessionInfo['media_info'] = [];

            if (isset($media['bitrate'])) {
                $sessionInfo['media_info']['bitrate'] = sprintf('%d kbps', $media['bitrate']);
                $sessionInfo['media_info']['bitrate_kbps'] = $media['bitrate'];
            }

            if (isset($media['videoResolution'])) {
                $sessionInfo['media_info']['resolution'] = $media['videoResolution'];
            }
        }

        $transcode = $session['TranscodeSession'] ?? null;
        if ($transcode) {
            $sessionInfo['transcoding'] = [
                'active' => true,
            ];

            if (isset($transcode['videoCodec'], $transcode['sourceVideoCodec'])) {
                $sessionInfo['transcoding']['video'] = sprintf(
                    '%s → %s',
                    $transcode['sourceVideoCodec'],
                    $transcode['videoCodec']
                );
            }

            if (isset($transcode['audioCodec'], $transcode['sourceAudioCodec'])) {
                $sessionInfo['transcoding']['audio'] = sprintf(
                    '%s → %s',
                    $transcode['sourceAudioCodec'],
                    $transcode['audioCodec']
                );
            }

            if (isset($transcode['width'], $transcode['height'])) {
                $sessionInfo['transcoding']['resolution'] = sprintf(
                    '%s → %dx%d',
                    $media['videoResolution'] ?? 'unknown',
                    $transcode['width'],
                    $transcode['height']
                );
            }
        } else {
            $sessionInfo['transcoding'] = [
                'active' => false,
                'mode' => 'Direct Play/Stream',
            ];
        }

        return $sessionInfo;
    }
}
