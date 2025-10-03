<?php

declare(strict_types=1);

use App\Mcp\Servers\PlexServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/plex', PlexServer::class);

Mcp::local('plex', PlexServer::class);
