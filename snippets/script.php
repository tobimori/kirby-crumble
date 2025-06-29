<?php

/**
 * Crumble script snippet
 * Outputs the necessary HTML tags to load the cookie consent library
 *
 * Usage: <?php snippet('crumble/script') ?>
 */

use Kirby\Cms\App;

$page = site()->find(option('tobimori.crumble.page'));
if (!$page) {
	return;
} ?>

<link rel="stylesheet" href="<?= App::instance()->plugin('tobimori/crumble')->asset('cookieconsent.css')->url() ?>">
<script type="module" src="/crumble/consent.<?= $page->revision() ?>.js"></script>