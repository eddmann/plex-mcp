<?php

declare(strict_types=1);

use App\Mcp\Servers\PlexServer;
use App\Mcp\Tools\Plex\SearchSubtitlesTool;
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

it('searches subtitles by IMDb ID', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,000 --> 00:00:15,000
Hello, world!

2
00:00:20,000 --> 00:00:25,000
This is a test subtitle.

SRT;

    Http::fake([
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
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt0133093',
        'language' => 'en',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('"subtitle_count":2');
    $response->assertSee('Hello, world!');
    $response->assertSee('This is a test subtitle.');
});

it('searches subtitles by title', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:01:00,000 --> 00:01:05,000
Movie dialogue here.

SRT;

    Http::fake([
        '*/subtitles*' => Http::response([
            'data' => [
                [
                    'attributes' => [
                        'files' => [
                            ['file_id' => 67890],
                        ],
                    ],
                ],
            ],
        ]),
        '*/download' => Http::response([
            'link' => 'https://example.com/subtitle2.srt',
        ]),
        'https://example.com/subtitle2.srt' => Http::response($srtContent),
    ]);

    // Act
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'title' => 'The Matrix',
        'language' => 'en',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('Movie dialogue here.');
    $response->assertSee('"title":"The Matrix"');
});

it('filters subtitles by minutes elapsed', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:30,000 --> 00:00:35,000
First subtitle at 30 seconds.

2
00:01:30,000 --> 00:01:35,000
Second subtitle at 1 minute 30 seconds.

3
00:02:30,000 --> 00:02:35,000
Third subtitle at 2 minutes 30 seconds.

4
00:03:30,000 --> 00:03:35,000
Fourth subtitle at 3 minutes 30 seconds.

SRT;

    Http::fake([
        '*/subtitles*' => Http::response([
            'data' => [
                [
                    'attributes' => [
                        'files' => [
                            ['file_id' => 11111],
                        ],
                    ],
                ],
            ],
        ]),
        '*/download' => Http::response([
            'link' => 'https://example.com/subtitle3.srt',
        ]),
        'https://example.com/subtitle3.srt' => Http::response($srtContent),
    ]);

    // Act
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt0133093',
        'minutes_elapsed' => 2,
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('"minutes_elapsed":2');
    $response->assertSee('"subtitle_count":3');
    $response->assertSee('First subtitle at 30 seconds');
    $response->assertSee('Second subtitle at 1 minute 30 seconds');
    $response->assertSee('Third subtitle at 2 minutes 30 seconds');
});

it('validates that either imdb_id or title is provided', function () {
    // Act
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'language' => 'en',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"error"');
    $response->assertSee('Either imdb_id or title must be provided');
});

it('validates that both imdb_id and title are not provided together', function () {
    // Act
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt0133093',
        'title' => 'The Matrix',
        'language' => 'en',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"error"');
    $response->assertSee('Only one of imdb_id or title should be provided, not both');
});

it('returns empty result when no subtitles are found', function () {
    // Arrange
    Http::fake([
        '*/subtitles*' => Http::response([
            'data' => [],
        ]),
    ]);

    // Act
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt9999999',
        'language' => 'en',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('"subtitle_count":0');
    $response->assertSee('No subtitles found for the given parameters');
});

it('handles OpenSubtitles API errors gracefully', function () {
    // Arrange
    // When OpenSubtitles API returns 500, it catches the exception and returns empty result
    Http::fake([
        '*/subtitles*' => Http::response('API Error', 500),
    ]);

    // Act
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt0133093',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('success');
    $response->assertSee('No subtitles found');
});

it('uses cached subtitles when available', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,000 --> 00:00:15,000
Cached subtitle content.

SRT;

    $cachePath = config('opensubtitles.cache_path');
    $cacheKey = 'imdb_tt0133093_en';
    $cacheFile = "{$cachePath}/{$cacheKey}.srt";

    File::put($cacheFile, $srtContent);

    Http::fake([
        '*' => Http::response('Should not be called', 500),
    ]);

    // Act
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt0133093',
        'language' => 'en',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('Cached subtitle content');
    Http::assertNothingSent();
});

it('handles subtitle download link failures', function () {
    // Arrange
    // When download fails, it returns null which results in empty subtitles
    Http::fake([
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
        'https://example.com/subtitle.srt' => Http::response('Not found', 404),
    ]);

    // Act
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt0133093',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('success');
    $response->assertSee('No subtitles found');
});

it('parses complex SRT files correctly', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,500 --> 00:00:15,800
First line
Second line of the same subtitle

2
00:00:20,000 --> 00:00:25,000
Another subtitle


3
00:00:30,100 --> 00:00:35,900
Third subtitle with
multiple
lines

SRT;

    Http::fake([
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
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt0133093',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"subtitle_count":3');
    $response->assertSee('First line\nSecond line of the same subtitle');
    $response->assertSee('Another subtitle');
    $response->assertSee('Third subtitle with\nmultiple\nlines');
});

it('defaults to English language when not specified', function () {
    // Arrange
    $srtContent = <<<'SRT'
1
00:00:10,000 --> 00:00:15,000
English subtitle.

SRT;

    Http::fake([
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
    $response = PlexServer::tool(SearchSubtitlesTool::class, [
        'imdb_id' => 'tt0133093',
    ]);

    // Assert
    $response->assertOk();
    $response->assertSee('"status":"success"');
    $response->assertSee('English subtitle');
});
