<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Prompts\WhatHaveIMissedPrompt;
use App\Mcp\Tools\Plex\GetActiveSessionsTool;
use App\Mcp\Tools\Plex\SearchSubtitlesTool;
use Laravel\Mcp\Server;

final class PlexServer extends Server
{
    protected string $name = 'Plex Server';

    protected string $version = '1.0.0';

    protected string $instructions = 'Provides access to Plex Media Server sessions and subtitle search. Use the available tools to monitor active playback sessions and search subtitles for media content.';

    protected array $tools = [
        GetActiveSessionsTool::class,
        SearchSubtitlesTool::class,
    ];

    protected array $prompts = [
        WhatHaveIMissedPrompt::class,
    ];
}
