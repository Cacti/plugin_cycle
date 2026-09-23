<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Integration coverage for plugin_cycle_install(): verifies every hook and
 * the realm the plugin depends on at runtime are actually registered.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_registered_hooks']  = array();
	$GLOBALS['__test_registered_realms'] = array();
});

it('registers every hook cycle depends on and its realm', function () {
	plugin_cycle_install();

	$hooks = array();
	foreach ($GLOBALS['__test_registered_hooks'] as $registered) {
		$hooks[$registered['hook']] = $registered;
	}

	foreach (array('top_header_tabs', 'top_graph_header_tabs', 'config_arrays', 'draw_navigation_text', 'config_settings', 'api_graph_save', 'page_head') as $expected) {
		expect($hooks)->toHaveKey($expected);
		expect($hooks[$expected]['name'])->toBe('cycle');
		expect($hooks[$expected]['file'])->toBe('setup.php');
	}

	expect($GLOBALS['__test_registered_realms'])->toHaveCount(1);
	expect($GLOBALS['__test_registered_realms'][0]['file'])->toBe('cycle.php,cycle_ajax.php');
});
