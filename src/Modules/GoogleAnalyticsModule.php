<?php

declare(strict_types=1);

namespace BrewAndBytes\AcornAnalytics\Modules;

class GoogleAnalyticsModule extends AbstractModule
{
    public function handle(): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (empty($this->id())) {
            return;
        }

        $this->action('wp_head', 'printHead', 5);
    }

    public function printHead(): void
    {
        $id = $this->id();

        if ($this->consentRequired()) {
            $event = esc_js($this->consentEvent());
            $idJs = esc_js($id);
            $loader = "window.addEventListener('{$event}', function(){"
                ."var s=document.createElement('script');s.async=true;"
                ."s.src='https://www.googletagmanager.com/gtag/js?id={$idJs}';"
                .'document.head.appendChild(s);'
                .'window.dataLayer=window.dataLayer||[];'
                .'window.gtag=function(){window.dataLayer.push(arguments);};'
                ."window.gtag('js',new Date());window.gtag('config','{$idJs}');"
                .'}, { once: true });';
            echo "<!-- Google Analytics (deferred) -->\n<script>{$loader}</script>\n";

            return;
        }

        $idAttr = esc_attr($id);
        $idJs = esc_js($id);

        echo "<!-- Google Analytics -->\n";
        echo '<script async src="https://www.googletagmanager.com/gtag/js?id='.$idAttr."\"></script>\n";
        echo '<script>window.dataLayer=window.dataLayer||[];'
            .'window.gtag=function(){window.dataLayer.push(arguments);};'
            ."window.gtag('js',new Date());window.gtag('config','{$idJs}');</script>\n";
    }

    protected function id(): string
    {
        return (string) $this->config->get('id', '');
    }

    protected function consentEvent(): string
    {
        $consent = (array) $this->globals->get('consent', []);

        return (string) ($consent['event'] ?? 'cookie-consent:granted');
    }
}
