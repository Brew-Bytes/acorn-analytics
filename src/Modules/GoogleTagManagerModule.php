<?php

declare(strict_types=1);

namespace BrewAndBytes\AcornAnalytics\Modules;

class GoogleTagManagerModule extends AbstractModule
{
    public function handle(): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (empty($this->id())) {
            return;
        }

        $this
            ->action('wp_head', 'printHead', 5)
            ->action('wp_body_open', 'printNoscript', 5);
    }

    public function printHead(): void
    {
        $id = $this->id();
        $consentRequired = $this->consentRequired();
        $consentEvent = $this->consentEvent();

        if ($consentRequired) {
            $event = esc_js($consentEvent);
            $loader = "window.addEventListener('{$event}', function(){"
                .$this->loaderJs($id)
                .'}, { once: true });';
            echo "<!-- Google Tag Manager (deferred) -->\n<script>{$loader}</script>\n";

            return;
        }

        echo "<!-- Google Tag Manager -->\n<script>".$this->loaderJs($id)."</script>\n";
    }

    public function printNoscript(): void
    {
        $id = esc_attr($this->id());
        echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$id
            .'" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>'."\n";
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

    protected function loaderJs(string $id): string
    {
        $idJs = esc_js($id);

        return "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),"
            ."event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),"
            ."dl=l!='dataLayer'?'&l='+l:'';j.async=true;"
            ."j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;"
            ."f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$idJs}');";
    }
}
