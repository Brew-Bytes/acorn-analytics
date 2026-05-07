<?php

declare(strict_types=1);

namespace BrewAndBytes\AcornAnalytics\Modules;

class PlausibleModule extends AbstractModule
{
    public function handle(): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (empty($this->domain())) {
            return;
        }

        $this->action('wp_head', 'printHead', 5);
    }

    public function printHead(): void
    {
        $domain = $this->domain();
        $script = $this->scriptUrl();

        if ($this->consentRequired()) {
            $event = esc_js($this->consentEvent());
            $domainJs = esc_js($domain);
            $scriptJs = esc_js($script);
            $loader = "window.addEventListener('{$event}', function(){"
                ."var s=document.createElement('script');s.defer=true;"
                ."s.setAttribute('data-domain','{$domainJs}');"
                ."s.src='{$scriptJs}';document.head.appendChild(s);"
                .'window.plausible=window.plausible||function(){'
                .'(window.plausible.q=window.plausible.q||[]).push(arguments);};'
                .'}, { once: true });';
            echo "<!-- Plausible (deferred) -->\n<script>{$loader}</script>\n";

            return;
        }

        $domainAttr = esc_attr($domain);
        $scriptAttr = esc_url($script);

        echo "<!-- Plausible -->\n";
        echo '<script defer data-domain="'.$domainAttr.'" src="'.$scriptAttr."\"></script>\n";
        echo '<script>window.plausible=window.plausible||function(){'
            ."(window.plausible.q=window.plausible.q||[]).push(arguments);};</script>\n";
    }

    protected function domain(): string
    {
        return (string) $this->config->get('domain', '');
    }

    protected function scriptUrl(): string
    {
        return (string) $this->config->get('script', 'https://plausible.io/js/script.js');
    }

    protected function consentEvent(): string
    {
        $consent = (array) $this->globals->get('consent', []);

        return (string) ($consent['event'] ?? 'cookie-consent:granted');
    }
}
