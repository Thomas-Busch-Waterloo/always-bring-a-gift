<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Configure which proxies to trust when your application is behind a
    | reverse proxy (like Traefik, Nginx, AWS ELB, etc). This is critical
    | for correctly detecting the client's IP address and protocol (HTTP/HTTPS).
    |
    | Options:
    |   - null or empty: Don't trust any proxies (default, most secure)
    |   - '*': Trust all proxies (use for Docker/Traefik deployments)
    |   - Comma-separated IPs: '192.168.1.1,10.0.0.0/8'
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
