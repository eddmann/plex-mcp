<?php

declare(strict_types=1);

use App\Mcp\Prompts\WhatHaveIMissedPrompt;
use App\Mcp\Servers\PlexServer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Ensure cache directory exists
    $cachePath = config('opensubtitles.cache_path');
    if (! File::exists($cachePath)) {
        File::makeDirectory($cachePath, 0755, true);
    }
});

afterEach(function () {
    // Clean up cache files
    $cachePath = config('opensubtitles.cache_path');
    if (File::exists($cachePath)) {
        File::cleanDirectory($cachePath);
    }
});

it('generates prompt for movie with subtitles', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:30,000 --> 00:00:35,000
Welcome to the Matrix.

2
00:01:00,000 --> 00:01:05,000
Take the red pill or the blue pill.

SRT;

    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'The Matrix',
                        'year' => '1999',
                        'guid' => 'imdb://tt0133093',
                        'viewOffset' => 120000,
                        'duration' => 8160000,
                        'Player' => ['state' => 'playing', 'title' => 'Test Player'],
                        'User' => ['title' => 'Test User'],
                    ],
                ],
            ],
        ]),
        '*/subtitles*' => Http::response([
            'data' => [
                [
                    'attributes' => [
                        'files' => [
                            ['file_id' => 12345],
                        ],
                    ],
                ],
            ],
        ]),
        '*/download' => Http::response([
            'link' => 'https://example.com/subtitle.srt',
        ]),
        'https://example.com/subtitle.srt' => Http::response($srtContent),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class, [
        'session_index' => 0,
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('You are a helpful Plex media assistant');
    $response->assertSee('The Matrix (1999)');
    $response->assertSee('2 minutes');
    $response->assertSee('Welcome to the Matrix');
    $response->assertSee('Take the red pill or the blue pill');
});

it('generates prompt for TV episode with subtitles', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,000 --> 00:00:15,000
Previously on Breaking Bad...

2
00:00:30,000 --> 00:00:35,000
I am the one who knocks.

SRT;

    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'episode',
                        'title' => 'Pilot',
                        'grandparentTitle' => 'Breaking Bad',
                        'parentIndex' => 1,
                        'index' => 1,
                        'guid' => 'imdb://tt0959621',
                        'viewOffset' => 60000,
                        'duration' => 2880000,
                        'Player' => ['state' => 'playing', 'title' => 'Test Player'],
                        'User' => ['title' => 'Test User'],
                    ],
                ],
            ],
        ]),
        '*/subtitles*' => Http::response([
            'data' => [
                [
                    'attributes' => [
                        'files' => [
                            ['file_id' => 12345],
                        ],
                    ],
                ],
            ],
        ]),
        '*/download' => Http::response([
            'link' => 'https://example.com/subtitle.srt',
        ]),
        'https://example.com/subtitle.srt' => Http::response($srtContent),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class, [
        'session_index' => 0,
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('Breaking Bad - S1E1 - Pilot');
    $response->assertSee('Previously on Breaking Bad');
    $response->assertSee('I am the one who knocks');
});

it('handles no active sessions', function () {
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
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class);

    // Assert
    $response->assertOk();
    $response->assertSee('You are a helpful Plex media assistant');
    $response->assertSee('There are currently no active playback sessions');
});

it('handles invalid session index', function () {
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
                        'viewOffset' => 60000,
                        'Player' => ['state' => 'playing', 'title' => 'Test Player'],
                        'User' => ['title' => 'Test User'],
                    ],
                ],
            ],
        ]),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class, [
        'session_index' => 5,
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('Invalid session index 5');
    $response->assertSee('There are 1 active sessions (indexed 0-0)');
});

it('handles missing subtitles', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'Obscure Movie',
                        'year' => '2024',
                        'guid' => 'imdb://tt9999999',
                        'viewOffset' => 120000,
                        'Player' => ['state' => 'playing', 'title' => 'Test Player'],
                        'User' => ['title' => 'Test User'],
                    ],
                ],
            ],
        ]),
        '*/subtitles*' => Http::response([
            'data' => [],
        ]),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class);

    // Assert
    $response->assertOk();
    $response->assertSee('You are a helpful Plex media assistant');
    $response->assertSee('Obscure Movie (2024)');
    $response->assertSee('2 minutes');
    $response->assertSee('Unfortunately, subtitles are not available for this content');
});

it('handles content without IMDb ID using title fallback', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,000 --> 00:00:15,000
Content without IMDb ID.

SRT;

    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'Indie Film',
                        'year' => '2023',
                        'guid' => 'plex://movie/abc123',
                        'viewOffset' => 60000,
                        'Player' => ['state' => 'playing', 'title' => 'Test Player'],
                        'User' => ['title' => 'Test User'],
                    ],
                ],
            ],
        ]),
        '*/subtitles*' => Http::response([
            'data' => [
                [
                    'attributes' => [
                        'files' => [
                            ['file_id' => 12345],
                        ],
                    ],
                ],
            ],
        ]),
        '*/download' => Http::response([
            'link' => 'https://example.com/subtitle.srt',
        ]),
        'https://example.com/subtitle.srt' => Http::response($srtContent),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class);

    // Assert
    $response->assertOk();
    $response->assertSee('Indie Film (2023)');
    $response->assertSee('Content without IMDb ID');
});

it('handles multiple sessions by selecting specified index', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,000 --> 00:00:15,000
Second movie dialogue.

SRT;

    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 3,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'Movie 1',
                        'year' => '2020',
                        'guid' => 'imdb://tt1111111',
                        'viewOffset' => 60000,
                        'Player' => ['state' => 'playing', 'title' => 'Player 1'],
                        'User' => ['title' => 'User 1'],
                    ],
                    [
                        'type' => 'movie',
                        'title' => 'Movie 2',
                        'year' => '2021',
                        'guid' => 'imdb://tt2222222',
                        'viewOffset' => 120000,
                        'Player' => ['state' => 'playing', 'title' => 'Player 2'],
                        'User' => ['title' => 'User 2'],
                    ],
                    [
                        'type' => 'movie',
                        'title' => 'Movie 3',
                        'year' => '2022',
                        'guid' => 'imdb://tt3333333',
                        'viewOffset' => 180000,
                        'Player' => ['state' => 'playing', 'title' => 'Player 3'],
                        'User' => ['title' => 'User 3'],
                    ],
                ],
            ],
        ]),
        '*/subtitles*' => Http::response([
            'data' => [
                [
                    'attributes' => [
                        'files' => [
                            ['file_id' => 12345],
                        ],
                    ],
                ],
            ],
        ]),
        '*/download' => Http::response([
            'link' => 'https://example.com/subtitle.srt',
        ]),
        'https://example.com/subtitle.srt' => Http::response($srtContent),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class, [
        'session_index' => 1,
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('Movie 2 (2021)');
    $response->assertSee('2 minutes');
});

it('defaults to first session when session_index is not provided', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,000 --> 00:00:15,000
First movie dialogue.

SRT;

    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 2,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'First Movie',
                        'year' => '2020',
                        'guid' => 'imdb://tt1111111',
                        'viewOffset' => 60000,
                        'Player' => ['state' => 'playing', 'title' => 'Player 1'],
                        'User' => ['title' => 'User 1'],
                    ],
                    [
                        'type' => 'movie',
                        'title' => 'Second Movie',
                        'year' => '2021',
                        'guid' => 'imdb://tt2222222',
                        'viewOffset' => 120000,
                        'Player' => ['state' => 'playing', 'title' => 'Player 2'],
                        'User' => ['title' => 'User 2'],
                    ],
                ],
            ],
        ]),
        '*/subtitles*' => Http::response([
            'data' => [
                [
                    'attributes' => [
                        'files' => [
                            ['file_id' => 12345],
                        ],
                    ],
                ],
            ],
        ]),
        '*/download' => Http::response([
            'link' => 'https://example.com/subtitle.srt',
        ]),
        'https://example.com/subtitle.srt' => Http::response($srtContent),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class);

    // Assert
    $response->assertOk();
    $response->assertSee('First Movie (2020)');
});

it('handles content at the beginning with minimal elapsed time', function () {
    // Arrange
    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'New Movie',
                        'year' => '2024',
                        'guid' => 'imdb://tt1234567',
                        'viewOffset' => 5000,
                        'Player' => ['state' => 'playing', 'title' => 'Test Player'],
                        'User' => ['title' => 'Test User'],
                    ],
                ],
            ],
        ]),
        '*/subtitles*' => Http::response([
            'data' => [],
        ]),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class);

    // Assert
    $response->assertOk();
    $response->assertSee('New Movie (2024)');
    $response->assertSee('1 minutes');
});

it('handles API errors gracefully', function () {
    // Arrange
    // When Plex API returns 500, PlexClient catches it and returns empty array
    // This results in "no active sessions" message
    Http::fake([
        '*/status/sessions' => Http::response('Server error', 500),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class);

    // Assert
    $response->assertOk();
    $response->assertSee('You are a helpful Plex media assistant');
    $response->assertSee('There are currently no active playback sessions');
});

it('includes system message with instructions for AI assistant', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,000 --> 00:00:15,000
Test subtitle.

SRT;

    Http::fake([
        '*/status/sessions' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [
                    [
                        'type' => 'movie',
                        'title' => 'Test Movie',
                        'year' => '2020',
                        'guid' => 'imdb://tt0000000',
                        'viewOffset' => 60000,
                        'Player' => ['state' => 'playing', 'title' => 'Test Player'],
                        'User' => ['title' => 'Test User'],
                    ],
                ],
            ],
        ]),
        '*/subtitles*' => Http::response([
            'data' => [
                [
                    'attributes' => [
                        'files' => [
                            ['file_id' => 12345],
                        ],
                    ],
                ],
            ],
        ]),
        '*/download' => Http::response([
            'link' => 'https://example.com/subtitle.srt',
        ]),
        'https://example.com/subtitle.srt' => Http::response($srtContent),
    ]);

    // Act
    $response = PlexServer::prompt(WhatHaveIMissedPrompt::class);

    // Assert
    $response->assertOk();
    $response->assertSee('helpful Plex media assistant');
    $response->assertSee('concise, chronological summary');
    $response->assertSee('key plot points');
    $response->assertSee('2-4 paragraphs maximum');
});
