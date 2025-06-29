<?php
/**
 * Cookie consent initialization JavaScript
 * This snippet generates the JavaScript code that initializes the cookie consent library
 * with configuration from the Kirby panel and proper error handling
 * 
 * @var \Kirby\Cms\Page $page The Crumble page instance
 * @var \Kirby\Cms\Plugin $plugin The Crumble plugin instance
 */

$jsUrl = $plugin->asset('cookieconsent.esm.js')->url();
$config = $page->config();

?>
import * as CookieConsent from '<?= $jsUrl ?>';

(async () => {
  CookieConsent.run({
    ...<?= json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    onFirstConsent: () => {
      fetch('/crumble/consent', { method: 'POST' });
    },
    onChange: () => {
      fetch('/crumble/consent', { method: 'POST' });
    }
  });
  window.CookieConsent = CookieConsent;
})();