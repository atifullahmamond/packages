<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jitsi Meet Domain
    |--------------------------------------------------------------------------
    | The domain to use for Jitsi Meet. Defaults to the public meet.jit.si.
    | Set this to your own Jitsi server domain for a private deployment.
    */
    'jitsi_domain' => env('JITSI_DOMAIN', 'meet.jit.si'),

    /*
    |--------------------------------------------------------------------------
    | Jitsi JWT Authentication
    |--------------------------------------------------------------------------
    | For private Jitsi servers that require JWT authentication.
    | Leave null if using the public meet.jit.si server.
    */
    'jitsi_jwt_app_id'     => env('JITSI_JWT_APP_ID'),
    'jitsi_jwt_app_secret' => env('JITSI_JWT_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    | The URL prefix for the meeting room routes.
    */
    'route_prefix' => env('FILAMENT_MEET_ROUTE_PREFIX', 'meet'),

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    | Configure real-time updates for participant lists.
    | Requires a broadcasting driver (Pusher, Reverb, etc.) to be configured.
    */
    'broadcasting_enabled' => env('FILAMENT_MEET_BROADCASTING', true),

    /*
    |--------------------------------------------------------------------------
    | Meeting Room JWT Token TTL
    |--------------------------------------------------------------------------
    | How long a Jitsi JWT token is valid (in minutes).
    */
    'jwt_ttl_minutes' => env('FILAMENT_MEET_JWT_TTL', 120),

    /*
    |--------------------------------------------------------------------------
    | JWT on Public meet.jit.si (not recommended)
    |--------------------------------------------------------------------------
    | By default JWTs are not sent when the domain is meet.jit.si, because they
    | are not validated there and typically break embedding. Enable only when
    | you know what you are doing (e.g. debugging).
    */
    'jwt_on_public_meet_jit_si' => env('FILAMENT_MEET_JWT_ON_PUBLIC_JIT_SI', false),

    /*
    |--------------------------------------------------------------------------
    | Open join (scheduled / active meetings)
    |--------------------------------------------------------------------------
    | When true, any authenticated user may open /meet/room/{uuid}. When false,
    | only the host and users synced as participants may join (invite-only).
    */
    'open_join_for_authenticated_users' => env('FILAMENT_MEET_OPEN_JOIN', true),

    /*
    |--------------------------------------------------------------------------
    | AI Summary
    |--------------------------------------------------------------------------
    | Enable the AI summary stub. Requires the MeetingAISummary job to be
    | implemented with your own AI provider integration.
    */
    'ai_summary_enabled' => env('FILAMENT_MEET_AI_SUMMARY', false),
];
