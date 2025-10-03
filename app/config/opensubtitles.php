<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OpenSubtitles API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenSubtitles.com API key. You can obtain this by registering
    | at https://www.opensubtitles.com and generating an API key from
    | your account settings.
    |
    */

    'api_key' => env('OPENSUBTITLES_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | OpenSubtitles API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the OpenSubtitles REST API.
    |
    */

    'base_url' => env('OPENSUBTITLES_BASE_URL', 'https://api.opensubtitles.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | User Agent
    |--------------------------------------------------------------------------
    |
    | A descriptive User-Agent string that identifies your application.
    | OpenSubtitles requires a specific format: AppName v1.0
    |
    */

    'user_agent' => env('OPENSUBTITLES_USER_AGENT', 'PlexMCP v1.0'),

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for HTTP requests to the OpenSubtitles API.
    |
    */

    'timeout' => env('OPENSUBTITLES_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Subtitle Cache Path
    |--------------------------------------------------------------------------
    |
    | The directory where downloaded subtitle files will be cached locally.
    |
    */

    'cache_path' => storage_path('app/subtitles'),

];
