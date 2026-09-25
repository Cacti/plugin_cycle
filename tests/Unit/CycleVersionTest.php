<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for plugin_cycle_version() in setup.php.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

it('parses the plugin INFO file into an info array', function () {
	$info = plugin_cycle_version();

	expect($info)->toBeArray();
	expect($info)->toHaveKey('name');
	expect($info)->toHaveKey('version');
	expect($info['name'])->toBe('cycle');
});

it('returns an empty array when the INFO file has no [info] section', function () {
	$tmp_root = sys_get_temp_dir() . '/cycle_test_' . uniqid();
	mkdir($tmp_root . '/plugins/cycle', 0777, true);
	file_put_contents($tmp_root . '/plugins/cycle/INFO', "[not_info]\nname = cycle\n");

	$original_base_path       = $GLOBALS['config']['base_path'];
	$GLOBALS['config']['base_path'] = $tmp_root;

	try {
		$info = plugin_cycle_version();
	} finally {
		$GLOBALS['config']['base_path'] = $original_base_path;
		unlink($tmp_root . '/plugins/cycle/INFO');
		rmdir($tmp_root . '/plugins/cycle');
		rmdir($tmp_root . '/plugins');
		rmdir($tmp_root);
	}

	expect($info)->toBe([]);
});

it('returns an empty array when the INFO file is missing entirely', function () {
	$tmp_root = sys_get_temp_dir() . '/cycle_test_' . uniqid();
	mkdir($tmp_root . '/plugins/cycle', 0777, true);

	$original_base_path       = $GLOBALS['config']['base_path'];
	$GLOBALS['config']['base_path'] = $tmp_root;

	try {
		$info = @plugin_cycle_version();
	} finally {
		$GLOBALS['config']['base_path'] = $original_base_path;
		rmdir($tmp_root . '/plugins/cycle');
		rmdir($tmp_root . '/plugins');
		rmdir($tmp_root);
	}

	expect($info)->toBe([]);
});

