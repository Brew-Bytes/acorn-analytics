<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When false, no scripts are injected and the events bridge is not printed.
    | Default to env('ANALYTICS_ENABLED') so staging/dev can disable everything
    | with one env var regardless of which providers are configured.
    |
    */

    'enabled' => env('ANALYTICS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | Tracking only fires when WP_ENV matches one of these values. Reads
    | Bedrock's WP_ENV constant first, then falls back to wp_get_environment_type().
    | Set to an empty array to fire in every environment.
    |
    */

    'environments' => ['production'],

    /*
    |--------------------------------------------------------------------------
    | Privacy guards
    |--------------------------------------------------------------------------
    |
    | Skip tracking for logged-in users (avoids polluting prod stats during
    | admin QA) and respect the legacy DNT header.
    |
    */

    'exclude_logged_in' => true,
    'respect_dnt' => true,

    /*
    |--------------------------------------------------------------------------
    | Consent gating
    |--------------------------------------------------------------------------
    |
    | When `required` is true, the events bridge buffers any `analytics:event`
    | dispatches and only fires them after the configured JS event is observed
    | on `window`. Provider modules also defer their script injection until
    | consent fires. The `event` key matches whatever your consent UI emits —
    | examples: 'cookie-consent:granted', 'OneTrustGroupsUpdated', 'cookieyes:accepted'.
    |
    */

    'consent' => [
        'required' => false,
        'event' => 'cookie-consent:granted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Tag Manager
    |--------------------------------------------------------------------------
    |
    | Set GTM_ID to enable. Container script renders in <head>, noscript
    | fallback in <body>. GTM is the most flexible provider — once installed,
    | tags (Meta Pixel, LinkedIn Insight, etc.) can be added via GTM's UI
    | without further deploys.
    |
    */

    'google-tag-manager' => [
        'id' => env('GTM_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Analytics 4
    |--------------------------------------------------------------------------
    |
    | Set GA4_MEASUREMENT_ID (e.g. "G-XXXXXXX") to enable. Direct gtag.js
    | injection — skip this provider if you're already using GTM and managing
    | GA4 there.
    |
    */

    'google-analytics' => [
        'id' => env('GA4_MEASUREMENT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plausible
    |--------------------------------------------------------------------------
    |
    | Set PLAUSIBLE_DOMAIN to the domain registered with Plausible (without
    | protocol). Optionally point `script` at a self-hosted Plausible URL.
    |
    */

    'plausible' => [
        'domain' => env('PLAUSIBLE_DOMAIN'),
        'script' => env('PLAUSIBLE_SCRIPT_URL', 'https://plausible.io/js/script.js'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic event tracking
    |--------------------------------------------------------------------------
    |
    | Wires delegated DOM listeners that emit `analytics:event` CustomEvents
    | for common interactions — phone/email link clicks, file downloads,
    | scroll-depth milestones, and (optionally) outbound link clicks. Events
    | flow through the same bridge as your custom events, so they reach every
    | enabled provider with no extra wiring.
    |
    | Outbound-link tracking defaults OFF because GA4's Enhanced Measurement
    | covers it natively — enabling both would double-count.
    |
    */

    'auto-tracking' => [
        'enabled' => true,

        'phone-clicks' => true,
        'email-clicks' => true,

        'outbound-links' => false,

        'file-downloads' => true,
        'download-extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'rar', '7z', 'csv', 'txt',
        ],

        'scroll-depth' => true,
        'scroll-depth-thresholds' => [25, 50, 75, 100],
    ],

];
