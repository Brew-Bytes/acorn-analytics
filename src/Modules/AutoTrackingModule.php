<?php

declare(strict_types=1);

namespace BrewAndBytes\AcornAnalytics\Modules;

use Illuminate\Support\Collection;

class AutoTrackingModule extends AbstractModule
{
    public function handle(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->action('wp_head', 'printListeners', 2);
    }

    public function printListeners(): void
    {
        $cfg = [
            'phoneClicks' => (bool) $this->config->get('phone-clicks', true),
            'emailClicks' => (bool) $this->config->get('email-clicks', true),
            'outboundLinks' => (bool) $this->config->get('outbound-links', false),
            'fileDownloads' => (bool) $this->config->get('file-downloads', true),
            'downloadExtensions' => $this->normalizeList(
                $this->config->get('download-extensions', [])
            ),
            'scrollDepth' => (bool) $this->config->get('scroll-depth', true),
            'scrollDepthThresholds' => array_values(array_filter(array_map(
                'intval',
                $this->normalizeList($this->config->get('scroll-depth-thresholds', [25, 50, 75, 100]))
            ))),
        ];

        $cfgJson = (string) wp_json_encode($cfg);

        $script = <<<'JS'
(function(){
  var cfg = __CFG__;
  function emit(name, data){
    window.dispatchEvent(new CustomEvent('analytics:event', {
      detail: Object.assign({ name: name }, data || {})
    }));
  }

  document.addEventListener('click', function(e){
    var link = e.target.closest && e.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!href) return;
    var text = (link.textContent || '').trim();

    if (cfg.phoneClicks && href.indexOf('tel:') === 0) {
      emit('phone_click', { phone_number: href.slice(4), link_text: text });
      return;
    }
    if (cfg.emailClicks && href.indexOf('mailto:') === 0) {
      emit('email_click', { email: href.slice(7).split('?')[0], link_text: text });
      return;
    }
    if (cfg.fileDownloads && cfg.downloadExtensions.length) {
      var match = href.match(/\.([a-z0-9]+)(?:[?#]|$)/i);
      if (match) {
        var ext = match[1].toLowerCase();
        if (cfg.downloadExtensions.indexOf(ext) !== -1) {
          emit('file_download', { file_url: link.href, file_extension: ext, link_text: text });
          return;
        }
      }
    }
    if (cfg.outboundLinks) {
      try {
        var url = new URL(link.href, window.location.href);
        if (url.hostname && url.hostname !== window.location.hostname) {
          emit('outbound_click', { link_url: link.href, link_domain: url.hostname, link_text: text });
        }
      } catch (err) {
        console.warn('[acorn-analytics] outbound URL parse failed:', err);
      }
    }
  });

  if (cfg.scrollDepth && cfg.scrollDepthThresholds.length) {
    var hit = {};
    var ticking = false;
    function check(){
      ticking = false;
      var doc = document.documentElement;
      var height = doc.scrollHeight;
      if (height <= 0) return;
      var pct = Math.round(((window.scrollY + window.innerHeight) / height) * 100);
      cfg.scrollDepthThresholds.forEach(function(t){
        if (pct >= t && !hit[t]) {
          hit[t] = true;
          emit('scroll', { percent_scrolled: t });
        }
      });
    }
    window.addEventListener('scroll', function(){
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(check);
      }
    }, { passive: true });
  }
})();
JS;

        $script = str_replace('__CFG__', $cfgJson, $script);

        echo "<script id=\"acorn-analytics-auto-tracking\">\n{$script}\n</script>\n";
    }

    /**
     * Normalize a config value into a plain list array.
     */
    protected function normalizeList(mixed $value): array
    {
        if ($value instanceof Collection) {
            return array_values($value->all());
        }

        return array_values((array) $value);
    }
}
