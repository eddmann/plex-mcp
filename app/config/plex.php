<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Plex Server URL
    |--------------------------------------------------------------------------
    |
    | The URL of your Plex Media Server. This should include the protocol
    | (http or https) and the port number (typically 32400).
    |
    */

    'url' => env('PLEX_URL', 'http://localhost:32400'),

    /*
    |--------------------------------------------------------------------------
    | Plex Authentication Token
    |--------------------------------------------------------------------------
    |
    | Your Plex authentication token. You can find this in your Plex account
    | settings or by inspecting network requests in your browser.
    |
    */

    'token' => env('PLEX_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for HTTP requests to the Plex server.
    |
    */

    'timeout' => env('PLEX_TIMEOUT', 30),

];
