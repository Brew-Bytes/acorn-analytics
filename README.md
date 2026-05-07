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

## Automatic event tracking

`acorn-analytics` ships an `auto-tracking` module that wires delegated DOM
listeners for the four interactions every site ends up tracking by hand.
Events flow through the same bridge as your custom events — they reach
every enabled provider with no extra wiring.

| Sub-feature | Default | Event name | Sample payload |
| --- | --- | --- | --- |
| `phone-clicks` | ✅ on | `phone_click` | `{ phone_number, link_text }` |
| `email-clicks` | ✅ on | `email_click` | `{ email, link_text }` |
| `file-downloads` | ✅ on | `file_download` | `{ file_url, file_extension, link_text }` |
| `scroll-depth` | ✅ on | `scroll` | `{ percent_scrolled }` (one of `[25, 50, 75, 100]`) |
| `outbound-links` | ❌ off | `click` | `{ outbound: true, link_url, link_domain, link_text }` |

Outbound-link tracking defaults off because GA4's Enhanced Measurement
covers it natively — enabling both would double-count.

Configure in `config/analytics.php`:

```php
'auto-tracking' => [
    'enabled' => true,
    'phone-clicks' => true,
    'email-clicks' => true,
    'file-downloads' => true,
    'download-extensions' => ['pdf', 'doc', 'docx', 'zip', /* ... */],
    'scroll-depth' => true,
    'scroll-depth-thresholds' => [25, 50, 75, 100],
    'outbound-links' => false,
],
```

Set the top-level `enabled` to `false` to disable the entire module, or
flip individual sub-features.

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

## Troubleshooting

**Provider scripts aren't appearing in `<head>`.** Most likely your environment
isn't in the configured `environments` list. Defaults to `['production']` only.
Two common surprises:

- **Local-by-Flywheel** sites set `WP_ENVIRONMENT_TYPE=local`, not `development`.
  Add `'local'` to your `environments` array (or set `environments => []` to fire
  in every environment for testing).
- **Bedrock's `WP_ENV` constant** takes precedence if defined.

**`env()` calls return `null` on a non-Bedrock WP install.** The default
`config/analytics.php` reads IDs from environment variables (`env('GA4_MEASUREMENT_ID')`
etc.). On a vanilla WordPress install (no Bedrock `.env` loader), those calls
return `null` and modules silently no-op. Either:

- Set the IDs as PHP constants in `wp-config.php` and read them with `defined()`,
  or
- Hardcode IDs directly in your published `config/analytics.php`:
  ```php
  'google-analytics' => ['id' => 'G-XXXXXXX'],
  ```

**Provider just installed but isn't loading.** Acorn caches the package
manifest at `wp-content/cache/acorn/framework/cache/packages.php`. If
`composer require` was run from outside a shell that has WP-CLI DB access
(common on Local-by-Flywheel), the post-autoload-dump's cache invalidation
silently fails. Delete that file plus its sibling `services.php` to force a
rebuild.

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
