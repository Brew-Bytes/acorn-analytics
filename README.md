# Acorn Analytics

A theme-agnostic [Acorn](https://roots.io/acorn/) package that wires Google Tag
Manager, Google Analytics 4, and Plausible into a [Sage](https://roots.io/sage/)
theme via one config file. Drop in your IDs, ship to production — no per-project
script tag boilerplate, no staging-data pollution, no manual consent plumbing.

## What it does

- **Three providers** — GTM, GA4, Plausible. Each module is independently enabled
  by setting its ID; missing IDs no-op silently.
- **Production-only by default** — reads Bedrock's `WP_ENV`. Configurable.
- **Skips logged-in users** — admin QA doesn't pollute prod stats.
- **Respects DNT** — clients who opt out via Do-Not-Track aren't tracked.
- **Consent-ready** — opt in via `consent.required => true` and the package
  defers script injection until your consent UI emits the configured JS event.
- **Unified custom-events API** — fire one `analytics:event` from anywhere in
  your front-end and the bridge fans it out to whichever providers happen to
  be enabled.

## Installation

```bash
composer require brew-bytes/acorn-analytics
```

Add the IDs you want to your `.env`:

```env
ANALYTICS_ENABLED=true
GTM_ID=GTM-XXXXXXX
GA4_MEASUREMENT_ID=G-XXXXXXXX
PLAUSIBLE_DOMAIN=mysite.com
```

That's it — Acorn auto-discovers the provider, defaults gate to `production`,
and only providers with non-empty IDs render.

### Customizing

Publish the config:

```bash
wp acorn vendor:publish --tag=analytics-config
```

Then edit `config/analytics.php` to flip flags, change environments, or wire
up consent integration.

## Custom events

From anywhere on the front-end:

```js
window.dispatchEvent(new CustomEvent('analytics:event', {
    detail: { name: 'newsletter_signup', tier: 'free' }
}));
```

…or from Alpine:

```html
<form @submit="$dispatch('analytics:event', { name: 'newsletter_signup' })">
```

…or from Livewire:

```php
$this->dispatch('analytics:event', name: 'contact_form_submitted', form: 'sales');
```

The bridge dispatches to all enabled providers — `gtag('event', ...)` for GA4,
`plausible(...)` for Plausible, `dataLayer.push(...)` for GTM — so the same
event call works regardless of which provider(s) are configured.

You can also use the package's tiny PHP-free JS API:

```js
window.AcornAnalytics.track('purchase', { value: 49 });
```

## Cookie consent integration

Set in `config/analytics.php`:

```php
'consent' => [
    'required' => true,
    'event' => 'cookie-consent:granted',  // emitted by your consent UI
],
```

When consent is required, no provider scripts inject until that event fires on
`window`. Custom events dispatched before consent are buffered and flushed on
acceptance.

Compatible with most consent platforms — point `event` at:

| Platform     | Event name                  |
| ------------ | --------------------------- |
| Custom UI    | `cookie-consent:granted`    |
| OneTrust     | `OneTrustGroupsUpdated`     |
| Cookiebot    | `CookiebotOnAccept`         |
| Cookieyes    | `cookieyes:accepted`        |

## What it doesn't do

- **Server-side tracking / Conversions API** — Plausible/Posthog server SDKs are
  separate packages.
- **Advertising pixels** (Meta, TikTok, LinkedIn) — for now, install GTM and add
  pixels there. A separate `acorn-conversion-pixels` package may follow.
- **A consent UI** — this package only listens for events. Use `acorn-cookie-consent`
  (forthcoming) or any third-party consent tool.

## Requirements

- PHP 8.1+
- Acorn 4.x or 5.x
- WordPress 6.0+

## License

MIT &copy; Brew & Bytes
