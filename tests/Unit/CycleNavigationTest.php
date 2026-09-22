<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for cycle_draw_navigation_text() in setup.php.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

it('adds the cycle breadcrumb entries without disturbing existing ones', function () {
	$nav = cycle_draw_navigation_text(array('other.php:' => array('title' => 'Other')));

	expect($nav)->toHaveKey('other.php:');

	foreach (array('cycle.php:', 'cycle.php:view', 'cycle.php:graphs', 'cycle.php:save') as $key) {
		expect($nav)->toHaveKey($key);
		expect($nav[$key]['url'])->toBe('cycle.php');
		expect($nav[$key]['level'])->toBe('1');
	}
});
