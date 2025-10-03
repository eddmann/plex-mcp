<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Plex MCP Server') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,600,700" rel="stylesheet" />
</head>
<body style="margin: 0; padding: 0; font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #1f2326; min-height: 100vh; display: flex; align-items: center; justify-content: center; color: #ffffff;">
    <div style="width: 100%; max-width: 680px; margin: 20px; background: #282a2d; border-radius: 8px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6); overflow: hidden; border: 1px solid #3a3d41;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1f2326 0%, #282a2d 100%); padding: 48px 32px; text-align: center; position: relative; overflow: hidden; border-bottom: 2px solid #e5a00d;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #e5a00d 0%, #cc7b19 50%, #e5a00d 100%);"></div>

            <div style="position: relative; z-index: 1;">
                <!-- Plex Logo -->
                <img src="https://www.plex.tv/wp-content/themes/plex/assets/img/favicons/plex-180.png" alt="Plex" style="width: 96px; height: 96px; margin: 0 auto 24px; display: block; border-radius: 50%; filter: drop-shadow(0 4px 12px rgba(229, 160, 13, 0.3));" />

                <h1 style="margin: 0 0 12px; font-size: 38px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">Plex MCP Server</h1>
                <p style="margin: 0; font-size: 16px; color: #9fa4a8; font-weight: 400;">Model Context Protocol Interface for Plex Media Server</p>
            </div>
        </div>

        <!-- Content -->
        <div style="padding: 40px 32px;">
            <div style="margin-bottom: 32px;">
                <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #e5a00d;">About This Server</h2>
                <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.7; color: #d1d4d6;">
                    This MCP server provides programmatic access to your Plex Media Server, enabling AI assistants and other MCP-compatible clients to interact with your media library and retrieve session information.
                </p>
                <p style="margin: 0; font-size: 15px; line-height: 1.7; color: #d1d4d6;">
                    Connect using any MCP-compatible client to access tools for session monitoring, subtitle search, and intelligent prompts for content summaries.
                </p>
            </div>

            <!-- Connection Info -->
            <div style="margin-bottom: 32px; padding: 24px; background: #1f2326; border-radius: 6px; border: 1px solid #3a3d41;">
                <h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 600; color: #e5a00d; display: flex; align-items: center;">
                    <svg style="width: 20px; height: 20px; margin-right: 8px; fill: #e5a00d;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.59 13.41c.41.39.41 1.03 0 1.42-.39.39-1.03.39-1.42 0a5.003 5.003 0 0 1 0-7.07l3.54-3.54a5.003 5.003 0 0 1 7.07 0 5.003 5.003 0 0 1 0 7.07l-1.49 1.49c.01-.82-.12-1.64-.4-2.42l.47-.48a2.982 2.982 0 0 0 0-4.24 2.982 2.982 0 0 0-4.24 0l-3.53 3.53a2.982 2.982 0 0 0 0 4.24zm2.82-4.24c.39-.39 1.03-.39 1.42 0a5.003 5.003 0 0 1 0 7.07l-3.54 3.54a5.003 5.003 0 0 1-7.07 0 5.003 5.003 0 0 1 0-7.07l1.49-1.49c-.01.82.12 1.64.4 2.43l-.47.47a2.982 2.982 0 0 0 0 4.24 2.982 2.982 0 0 0 4.24 0l3.53-3.53a2.982 2.982 0 0 0 0-4.24.973.973 0 0 1 0-1.42z"/>
                    </svg>
                    Connection Endpoint
                </h3>
                <div style="background: #16181a; border-radius: 4px; padding: 16px; font-family: 'Monaco', 'Menlo', 'Courier New', monospace; font-size: 14px; color: #e5a00d; position: relative; overflow-x: auto; border: 1px solid #2a2d30;">
                    <code style="white-space: nowrap;">{{ config('app.url') }}/mcp/plex</code>
                </div>
                <p style="margin: 12px 0 0; font-size: 13px; color: #9fa4a8; line-height: 1.6;">
                    Use this endpoint URL when configuring your MCP client to connect to this server.
                </p>
            </div>

            <!-- Features -->
            <div style="margin-bottom: 32px;">
                <h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 600; color: #e5a00d;">Available Tools</h3>
                <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                    <div style="display: flex; align-items: start; padding: 16px; background: #1f2326; border-radius: 4px; border-left: 3px solid #e5a00d; border: 1px solid #3a3d41; border-left: 3px solid #e5a00d;">
                        <svg style="width: 20px; height: 20px; margin-right: 12px; fill: #e5a00d; flex-shrink: 0; margin-top: 2px;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                        <div>
                            <div style="font-weight: 600; color: #ffffff; margin-bottom: 4px; font-size: 14px;">Active Sessions</div>
                            <div style="font-size: 13px; color: #9fa4a8; line-height: 1.5;">Monitor current playback sessions with user and device details</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: start; padding: 16px; background: #1f2326; border-radius: 4px; border: 1px solid #3a3d41; border-left: 3px solid #cc7b19;">
                        <svg style="width: 20px; height: 20px; margin-right: 12px; fill: #cc7b19; flex-shrink: 0; margin-top: 2px;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                        </svg>
                        <div>
                            <div style="font-weight: 600; color: #ffffff; margin-bottom: 4px; font-size: 14px;">Subtitle Search</div>
                            <div style="font-size: 13px; color: #9fa4a8; line-height: 1.5;">Search and retrieve subtitles by title or IMDb ID</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prompts -->
            <div style="margin-bottom: 32px;">
                <h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 600; color: #e5a00d;">Available Prompts</h3>
                <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                    <div style="display: flex; align-items: start; padding: 16px; background: #1f2326; border-radius: 4px; border-left: 3px solid #e5a00d; border: 1px solid #3a3d41; border-left: 3px solid #e5a00d;">
                        <svg style="width: 20px; height: 20px; margin-right: 12px; fill: #e5a00d; flex-shrink: 0; margin-top: 2px;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z"/>
                        </svg>
                        <div>
                            <div style="font-weight: 600; color: #ffffff; margin-bottom: 4px; font-size: 14px;">What Have I Missed</div>
                            <div style="font-size: 13px; color: #9fa4a8; line-height: 1.5;">Generates a concise summary of what you've missed in currently playing content using subtitles</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Getting Started -->
            <div style="padding: 20px; background: #1f2326; border-radius: 6px; border: 1px solid #e5a00d;">
                <div style="display: flex; align-items: start;">
                    <svg style="width: 24px; height: 24px; margin-right: 12px; fill: #e5a00d; flex-shrink: 0;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                    <div>
                        <div style="font-weight: 600; color: #e5a00d; margin-bottom: 8px; font-size: 15px;">Getting Started</div>
                        <div style="font-size: 14px; color: #d1d4d6; line-height: 1.6;">
                            Configure your MCP-compatible client (Claude Desktop, Continue, etc.) to connect to this server using the endpoint above. Refer to the
                            <a href="https://modelcontextprotocol.io" target="_blank" style="color: #e5a00d; text-decoration: none; font-weight: 600; border-bottom: 1px solid #e5a00d;">Model Context Protocol documentation</a>
                            for setup instructions.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 24px 32px; background: #1f2326; border-top: 1px solid #3a3d41; text-align: center;">
            <p style="margin: 0; font-size: 13px; color: #9fa4a8;">
                Powered by <span style="font-weight: 600; color: #e5a00d;">Laravel MCP</span>
                <span style="margin: 0 8px; color: #3a3d41;">•</span>
                <a href="https://modelcontextprotocol.io" target="_blank" style="color: #e5a00d; text-decoration: none; font-weight: 500;">Learn about MCP</a>
            </p>
        </div>
    </div>
</body>
</html>
