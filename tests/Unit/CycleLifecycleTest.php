<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the plugin lifecycle contract functions in setup.php:
 * plugin_cycle_check_config(), plugin_cycle_upgrade(),
 * plugin_cycle_uninstall(), cycle_check_dependencies(), and
 * cycle_check_upgrade()'s page-guard and version-drift branches.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	cycle_test_reset_db_mocks();
	$GLOBALS['__test_db_calls']         = array();
	$GLOBALS['__test_registered_hooks'] = array();
	$_SERVER['PHP_SELF']                = '/cycle.php';
});

it('reports the config as always valid', function () {
	expect(plugin_cycle_check_config())->toBeTrue();
});

it('reports that no upgrade is pending', function () {
	expect(plugin_cycle_upgrade())->toBeFalse();
});

it('reports that its dependencies are always satisfied', function () {
	expect(cycle_check_dependencies())->toBeTrue();
});

it('performs no work on uninstall without raising an error', function () {
	expect(plugin_cycle_uninstall())->toBeNull();
});

it('skips the version check on pages that do not need it', function () {
	$_SERVER['PHP_SELF'] = '/graphs.php';

	cycle_check_upgrade();

	expect($GLOBALS['__test_db_calls'])->toBeEmpty();
});

it('does nothing when the plugin has never been installed', function () {
	cycle_check_upgrade();

	expect($GLOBALS['__test_registered_hooks'])->toBeEmpty();
});

it('re-registers hooks and updates plugin_config when an installed plugin version drifts', function () {
	$info = plugin_cycle_version();

	cycle_test_mock_db('db_fetch_row', 'plugin_config', array('version' => '0.0.0', 'status' => '1'));
	cycle_test_mock_db('db_fetch_cell', 'plugin_realms', '5');
	cycle_test_mock_db('db_fetch_cell', 'plugin_config', '42');

	cycle_check_upgrade();

	expect($GLOBALS['__test_registered_hooks'])->not->toBeEmpty();
	expect($GLOBALS['__test_registered_realms'])->not->toBeEmpty();

	$updates = array_values(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute_prepared' && stripos($call['sql'], 'UPDATE plugin_config') !== false;
	}));

	expect($updates)->toHaveCount(1);
	expect($updates[0]['params'])->toBe(array($info['longname'], $info['author'], $info['homepage'], $info['version'], '42'));
});
