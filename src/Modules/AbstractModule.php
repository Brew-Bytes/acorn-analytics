<?php

declare(strict_types=1);

namespace BrewAndBytes\AcornAnalytics\Modules;

use BrewAndBytes\AcornAnalytics\Contracts\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Roots\Acorn\Application;

/**
 * @phpstan-consistent-constructor
 */
abstract class AbstractModule implements Module
{
    protected Application $app;

    protected Collection $config;

    protected Collection $globals;

    public function __construct(Application $app, Collection $config)
    {
        $this->app = $app;
        $this->globals = $config;
        $this->config = Collection::make($config->get($this->getKey(), []));

        $this->boot();
    }

    public static function make(Application $app, Collection $config): self
    {
        return new static($app, $config);
    }

    protected function boot(): void
    {
        if ($this->config->isEmpty()) {
            return;
        }

        if (! $this->shouldRun()) {
            return;
        }

        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        $this->app->call([$this, $method]);
    }

    /**
     * Module-level enable check (every module's local `enabled` flag + a non-empty ID).
     */
    protected function enabled(): bool
    {
        return (bool) $this->config->get('enabled', true);
    }

    /**
     * Package-wide gating: respects environment list, logged-in skip, and DNT header.
     */
    protected function shouldRun(): bool
    {
        if (! (bool) $this->globals->get('enabled', false)) {
            return false;
        }

        $environments = (array) $this->globals->get('environments', ['production']);
        if ($environments !== [] && ! in_array($this->currentEnvironment(), $environments, true)) {
            return false;
        }

        if ((bool) $this->globals->get('exclude_logged_in', true) && is_user_logged_in()) {
            return false;
        }

        if ((bool) $this->globals->get('respect_dnt', true) && $this->doNotTrackEnabled()) {
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

    protected function doNotTrackEnabled(): bool
    {
        return isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] === '1';
    }

    protected function getKey(): string
    {
        return (string) Str::of(static::class)
            ->afterLast('\\')
            ->beforeLast('Module')
            ->snake('-');
    }

    protected function action(string $hook, string $method, int $priority = 10, int $args = 1): self
    {
        add_action($hook, [$this, $method], $priority, $args);

        return $this;
    }

    protected function filter(string $hook, string $method, int $priority = 10, int $args = 1): self
    {
        add_filter($hook, [$this, $method], $priority, $args);

        return $this;
    }

    /**
     * Whether the package is in deferred (consent-required) mode.
     */
    protected function consentRequired(): bool
    {
        $consent = (array) $this->globals->get('consent', []);

        return ! empty($consent['required']);
    }
}
