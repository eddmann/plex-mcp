<?php

declare(strict_types=1);

use App\Mcp\Servers\PlexServer;
use App\Mcp\Tools\Plex\GetActiveSessionsTool;
use Illuminate\Support\Facades\Http;

it('returns no active sessions when none exist', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 0,
                'Metadata' => [],
            ],
        ]),
    ]);

    // Act
    $response = PlexServer::tool(GetActiveSessionsTool::class);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('"message":"No active sessions found."');
    $response->assertSee('"sessions_count":0');
});

it('returns active sessions with direct play', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'Inception',
                        'year' => '2010',
                        'duration' => 8880000,
                        'viewOffset' => 3000000,
                        'Player' => [
                            'state' => 'playing',
                            'title' => 'Living Room TV',
                            'address' => '192.168.1.100',
                            'platform' => 'Plex for LG',
                            'product' => 'Plex for LG',
                            'device' => 'LG TV',
                            'version' => '5.0',
                        ],
                        'User' => [
                            'title' => 'John Doe',
                        ],
                        'Media' => [
                            [
                                'bitrate' => 8000,
                                'videoResolution' => '1080p',
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    // Act
    $response = PlexServer::tool(GetActiveSessionsTool::class);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('"sessions_count":1');
    $response->assertSee('"direct_play_count":1');
    $response->assertSee('"transcode_count":0');
    $response->assertSee('Inception (2010) (Movie)');
    $response->assertSee('John Doe');
    $response->assertSee('Living Room TV');
    $response->assertSee('"state":"playing"');
});

it('returns active sessions with transcoding', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'The Matrix',
                        'year' => '1999',
                        'duration' => 8160000,
                        'viewOffset' => 1000000,
                        'Player' => [
                            'state' => 'playing',
                            'title' => 'Chrome Browser',
                            'address' => '192.168.1.50',
                            'platform' => 'Chrome',
                            'product' => 'Plex Web',
                            'device' => 'PC',
                            'version' => '4.0',
                        ],
                        'User' => [
                            'title' => 'Jane Smith',
                        ],
                        'Media' => [
                            [
                                'bitrate' => 12000,
                                'videoResolution' => '4k',
                            ],
                        ],
                        'TranscodeSession' => [
                            'videoCodec' => 'h264',
                            'sourceVideoCodec' => 'hevc',
                            'audioCodec' => 'aac',
                            'sourceAudioCodec' => 'dts',
                            'width' => 1920,
                            'height' => 1080,
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    // Act
    $response = PlexServer::tool(GetActiveSessionsTool::class);

    // Assert
    $response->assertOk();
    $response->assertSee('"transcode_count":1');
    $response->assertSee('"direct_play_count":0');
    $response->assertSee('hevc');
    $response->assertSee('h264');
    $response->assertSee('dts');
    $response->assertSee('aac');
    $response->assertSee('1920x1080');
});

it('returns active TV episode sessions with proper formatting', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'episode',
                        'title' => 'The One Where It All Began',
                        'grandparentTitle' => 'Friends',
                        'parentIndex' => 1,
                        'index' => 1,
                        'duration' => 1320000,
                        'viewOffset' => 600000,
                        'Player' => [
                            'state' => 'playing',
                            'title' => 'iPhone',
                        ],
                        'User' => [
                            'title' => 'Bob Johnson',
                        ],
                        'Media' => [
                            [
                                'bitrate' => 3000,
                                'videoResolution' => '720p',
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    // Act
    $response = PlexServer::tool(GetActiveSessionsTool::class);

    // Assert
    $response->assertOk();
    $response->assertSee('Friends - S1E1 - The One Where It All Began (TV Episode)');
    $response->assertSee('Bob Johnson');
});

it('calculates progress correctly', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'Test Movie',
                        'year' => '2020',
                        'duration' => 6000000,
                        'viewOffset' => 3000000,
                        'Player' => [
                            'state' => 'playing',
                            'title' => 'Test Player',
                        ],
                        'User' => [
                            'title' => 'Test User',
                        ],
                        'Media' => [[]],
                    ],
                ],
            ],
        ]),
    ]);

    // Act
    $response = PlexServer::tool(GetActiveSessionsTool::class);

    // Assert
    $response->assertOk();
    $response->assertSee('"percent":50');
    $response->assertSee('"minutes_remaining":50');
    $response->assertSee('"minutes_elapsed":50');
});

it('handles multiple active sessions', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 3,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'Movie 1',
                        'year' => '2020',
                        'duration' => 6000000,
                        'viewOffset' => 1000000,
                        'Player' => ['state' => 'playing', 'title' => 'Player 1'],
                        'User' => ['title' => 'User 1'],
                        'Media' => [['bitrate' => 5000]],
                    ],
                    [
                        'type' => 'movie',
                        'title' => 'Movie 2',
                        'year' => '2021',
                        'duration' => 7200000,
                        'viewOffset' => 2000000,
                        'Player' => ['state' => 'playing', 'title' => 'Player 2'],
                        'User' => ['title' => 'User 2'],
                        'Media' => [['bitrate' => 8000]],
                        'TranscodeSession' => [
                            'videoCodec' => 'h264',
                            'sourceVideoCodec' => 'hevc',
                        ],
                    ],
                    [
                        'type' => 'episode',
                        'title' => 'Episode 1',
                        'grandparentTitle' => 'TV Show',
                        'parentIndex' => 1,
                        'index' => 1,
                        'duration' => 2400000,
                        'viewOffset' => 1200000,
                        'Player' => ['state' => 'playing', 'title' => 'Player 3'],
                        'User' => ['title' => 'User 3'],
                        'Media' => [['bitrate' => 3000]],
                    ],
                ],
            ],
        ]),
    ]);

    // Act
    $response = PlexServer::tool(GetActiveSessionsTool::class);

    // Assert
    $response->assertOk();
    $response->assertSee('"sessions_count":3');
    $response->assertSee('"transcode_count":1');
    $response->assertSee('"direct_play_count":2');
    $response->assertSee('"total_bitrate_kbps":16000');
    $response->assertSee('Movie 1');
    $response->assertSee('Movie 2');
    $response->assertSee('TV Show - S1E1 - Episode 1');
});

it('handles API errors gracefully', function () {
    // Arrange
    // When Plex API returns 500, PlexClient catches the exception and returns empty array
    // This causes the tool to return "No active sessions found"
    Http::fake([
        '*/status/sessions' => Http::response(['error' => 'Server error'], 500),
    ]);

    // Act
    $response = PlexServer::tool(GetActiveSessionsTool::class);

    // Assert
    $response->assertOk();
    $response->assertSee('success');
    $response->assertSee('No active sessions found');
});

it('handles missing optional fields gracefully', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'Minimal Movie',
                        'Player' => [],
                        'User' => [],
                        'Media' => [[]],
                    ],
                ],
            ],
        ]),
    ]);

    // Act
    $response = PlexServer::tool(GetActiveSessionsTool::class);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('"sessions_count":1');
    $response->assertSee('Minimal Movie');
    $response->assertSee('Unknown User');
    $response->assertSee('Unknown Player');
});
