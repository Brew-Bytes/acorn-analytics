<?php

declare(strict_types=1);

namespace BrewAndBytes\AcornAnalytics;

use BrewAndBytes\AcornAnalytics\Concerns\HasCollection;
use Illuminate\Support\Collection;
use Roots\Acorn\Application;

/**
 * @phpstan-consistent-constructor
 */
class Analytics
{
    use HasCollection;

    protected Application $app;

    protected Collection $config;

    /**
     * @var array<int, class-string<Modules\AbstractModule>>
     */
    protected array $modules = [
        Modules\GoogleTagManagerModule::class,
        Modules\GoogleAnalyticsModule::class,
        Modules\PlausibleModule::class,
        Modules\AutoTrackingModule::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $this
            ->collect($this->app->make('config')->get('analytics', []))
            ->map(fn ($value) => is_array($value) ? $this->collect($value) : $value);

        add_action('init', fn () => $this->bootModules(), 99);

        if ($this->shouldRun()) {
            add_action('wp_head', fn () => $this->printBridge(), 1);
        }
    }

    public function bootModules(): void
    {
        $this->collect($this->modules)->each(
            fn (string $module) => $module::make($this->app, $this->config)
        );
    }

    public static function make(Application $app): self
    {
        return new static($app);
    }

    /**
     * Whether any tracking should fire on this request — mirrors AbstractModule::shouldRun().
     * Used to decide whether to emit the bridge at all.
     */
    protected function shouldRun(): bool
    {
        if (! (bool) $this->config->get('enabled', false)) {
            return false;
        }

        $environments = $this->config->get('environments', ['production']);
        if ($environments instanceof Collection) {
            $environments = $environments->all();
        }
        $environments = (array) $environments;
        if ($environments !== [] && ! in_array($this->currentEnvironment(), $environments, true)) {
            return false;
        }

        if ((bool) $this->config->get('exclude_logged_in', true) && is_user_logged_in()) {
            return false;
        }

        if ((bool) $this->config->get('respect_dnt', true) && isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] === '1') {
            return false;
        }

        return true;
    }

    protected function currentEnvironment(): string
    {
        if (defined('WP_ENV')) {
            return (string) constant('WP_ENV');
        }

        if (function_exists('wp_get_environment_type')) {
            return wp_get_environment_type();
        }

        return 'production';
    }

    /**
     * Emit the unified custom-events bridge. One inline script that listens for
     * `analytics:event` CustomEvents and fans them out to whatever providers
     * happen to be on the page (gtag / plausible / dataLayer).
     */
    protected function printBridge(): void
    {
        $consent = (array) $this->config->get('consent', []);
        $consentRequired = ! empty($consent['required']);
        $consentEvent = (string) ($consent['event'] ?? 'cookie-consent:granted');

        $script = <<<'JS'
(function(){
  var queue = [];
  var ready = !__CONSENT_REQUIRED__;

  function dispatch(name, data){
    if (typeof window.gtag === 'function') {
      window.gtag('event', name, data);
    }
    if (typeof window.plausible === 'function') {
      window.plausible(name, { props: data });
    }
    if (Array.isArray(window.dataLayer)) {
      var payload = Object.assign({ event: name }, data);
      window.dataLayer.push(payload);
    }
  }

  function flush(){
    while (queue.length) {
      var ev = queue.shift();
      dispatch(ev.name, ev.data);
    }
  }

  window.AcornAnalytics = window.AcornAnalytics || {};
  window.AcornAnalytics.track = function(name, data){
    if (!name) return;
    var detail = { name: name, data: data || {} };
    if (!ready) { queue.push(detail); return; }
    dispatch(detail.name, detail.data);
  };

  window.addEventListener('analytics:event', function(e){
    var d = e && e.detail || {};
    if (!d.name) return;
    var data = Object.assign({}, d);
    delete data.name;
    window.AcornAnalytics.track(d.name, data);
  });

  if (!ready) {
    window.addEventListener('__CONSENT_EVENT__', function(){
      ready = true;
      flush();
    }, { once: true });
  }
})();
JS;

        $script = str_replace(
            ['__CONSENT_REQUIRED__', '__CONSENT_EVENT__'],
            [$consentRequired ? 'true' : 'false', esc_js($consentEvent)],
            $script
        );

        echo "<script id=\"acorn-analytics-bridge\">\n{$script}\n</script>\n";
    }
}
