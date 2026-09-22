<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Test bootstrap.
 *
 * Cycle's sources expect to be included by Cacti, which has already
 * defined the db_*, request-variable, and logging helpers as plain global
 * functions. Nothing here talks to a database or a network: each Cacti
 * function is declared as a stub that records the call in
 * $GLOBALS['__test_db_calls'] and hands back a safe default.
 *
 * The CI workflow checks out a pinned Cacti release next to this plugin so
 * Pest runs against Cacti's own Composer-managed vendor tree (Pest/PHPUnit)
 * instead of a vendor tree local to this plugin. The version check below
 * makes sure that checkout actually matches what tests/.cacti-version
 * expects before any plugin source is loaded.
 *
 * Guarding every declaration with function_exists() keeps this file usable
 * if a future integration suite loads real Cacti first.
 */

$cacti_root = dirname(__DIR__, 3);
$autoload   = $cacti_root . '/include/vendor/autoload.php';
$version    = $cacti_root . '/include/cacti_version';
$expected   = __DIR__ . '/.cacti-version';

if (!is_readable($autoload)) {
	throw new RuntimeException("Cacti Composer autoloader is not readable: $autoload");
}

if (!is_readable($version)) {
	throw new RuntimeException("Cacti version file is not readable: $version");
}

if (!is_readable($expected)) {
	throw new RuntimeException("Expected Cacti version file is not readable: $expected");
}

$cacti_version     = trim((string) file_get_contents($version));
$expected_version  = trim((string) file_get_contents($expected));

if ($cacti_version === '') {
	throw new RuntimeException("Cacti version file is empty: $version");
}

if ($expected_version === '') {
	throw new RuntimeException("Expected Cacti version file is empty: $expected");
}

// The CI workflow tracks a moving branch (1.2.x or develop) rather than a pinned release, so any actual version is accepted.
if (!in_array($expected_version, array('1.2.x', 'develop'), true) && $cacti_version !== $expected_version) {
	throw new RuntimeException("Expected Cacti $expected_version, found $cacti_version in $version");
}

require_once $autoload;

/*
 * base_path has to point at the Cacti root two levels above this plugin:
 * cycle's source files build include paths from it at runtime.
 */
$GLOBALS['config'] = array(
	'base_path'       => $cacti_root,
	'url_path'        => '/cacti/',
	'cacti_version'   => $cacti_version,
	'cacti_server_os' => 'unix',
);

$GLOBALS['__test_db_calls']    = array();
$GLOBALS['__test_db_fixtures'] = array();

if (!function_exists('cycle_test_mock_db')) {
	function cycle_test_mock_db($fn, $match, $result) {
		$GLOBALS['__test_db_fixtures'][] = array('fn' => $fn, 'match' => $match, 'result' => $result);
	}
}

if (!function_exists('cycle_test_reset_db_mocks')) {
	function cycle_test_reset_db_mocks() {
		$GLOBALS['__test_db_fixtures'] = array();
	}
}

if (!function_exists('cycle_test_db_result')) {
	function cycle_test_db_result($fn, $sql, $params, $default) {
		foreach (array_reverse($GLOBALS['__test_db_fixtures']) as $fixture) {
			if ($fixture['fn'] !== $fn) {
				continue;
			}

			$match = $fixture['match'];

			if (is_callable($match)) {
				if (!$match($sql, $params)) {
					continue;
				}
			} elseif (strpos($sql, $match) === false) {
				continue;
			}

			$result = $fixture['result'];

			return is_callable($result) ? $result($sql, $params) : $result;
		}

		return $default;
	}
}

if (!function_exists('db_execute')) {
	function db_execute($sql) {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'db_execute', 'sql' => $sql, 'params' => array());
		return true;
	}
}

if (!function_exists('db_execute_prepared')) {
	function db_execute_prepared($sql, $params = array()) {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'db_execute_prepared', 'sql' => $sql, 'params' => $params);
		return true;
	}
}

if (!function_exists('db_fetch_assoc')) {
	function db_fetch_assoc($sql) {
		return cycle_test_db_result('db_fetch_assoc', $sql, array(), array());
	}
}

if (!function_exists('db_fetch_assoc_prepared')) {
	function db_fetch_assoc_prepared($sql, $params = array()) {
		return cycle_test_db_result('db_fetch_assoc_prepared', $sql, $params, array());
	}
}

if (!function_exists('db_fetch_row')) {
	function db_fetch_row($sql) {
		return cycle_test_db_result('db_fetch_row', $sql, array(), array());
	}
}

if (!function_exists('db_fetch_row_prepared')) {
	function db_fetch_row_prepared($sql, $params = array()) {
		return cycle_test_db_result('db_fetch_row_prepared', $sql, $params, array());
	}
}

if (!function_exists('db_fetch_cell')) {
	function db_fetch_cell($sql) {
		return cycle_test_db_result('db_fetch_cell', $sql, array(), '');
	}
}

if (!function_exists('db_fetch_cell_prepared')) {
	function db_fetch_cell_prepared($sql, $params = array()) {
		return cycle_test_db_result('db_fetch_cell_prepared', $sql, $params, '');
	}
}

if (!function_exists('db_index_exists')) {
	function db_index_exists($table, $index) {
		return false;
	}
}

if (!function_exists('db_column_exists')) {
	function db_column_exists($table, $column) {
		return false;
	}
}

if (!function_exists('api_plugin_db_add_column')) {
	function api_plugin_db_add_column($plugin, $table, $data) {
		return true;
	}
}

if (!function_exists('api_plugin_db_table_create')) {
	function api_plugin_db_table_create($plugin, $table, $data) {
		return true;
	}
}

$GLOBALS['__test_registered_hooks'] = array();

if (!function_exists('api_plugin_register_hook')) {
	function api_plugin_register_hook($plugin, $hook, $function, $file, $subtype = '') {
		$GLOBALS['__test_registered_hooks'][] = array(
			'name'     => $plugin,
			'hook'     => $hook,
			'function' => $function,
			'file'     => $file,
		);

		return true;
	}
}

$GLOBALS['__test_registered_realms'] = array();

if (!function_exists('api_plugin_register_realm')) {
	function api_plugin_register_realm($plugin, $file, $description, $enabled) {
		$GLOBALS['__test_registered_realms'][] = array(
			'name'        => $plugin,
			'file'        => $file,
			'description' => $description,
			'enabled'     => $enabled,
		);

		return true;
	}
}

if (!function_exists('read_config_option')) {
	function read_config_option($name, $force = false) {
		return isset($GLOBALS['__test_config_options'][$name]) ? $GLOBALS['__test_config_options'][$name] : '';
	}
}

if (!function_exists('set_config_option')) {
	function set_config_option($name, $value) {
		$GLOBALS['__test_config_options'][$name] = $value;
	}
}

$GLOBALS['__test_config_options'] = array();

if (!function_exists('test_set_config_option')) {
	function test_set_config_option($name, $value) {
		$GLOBALS['__test_config_options'][$name] = $value;
	}
}

if (!function_exists('html_escape')) {
	function html_escape($string) {
		return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('__')) {
	function __($text, $domain = '') {
		return $text;
	}
}

if (!function_exists('__esc')) {
	function __esc($text, $domain = '') {
		return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('cacti_log')) {
	function cacti_log($message, $also_print = false, $log_type = '', $level = 0) {
	}
}

if (!function_exists('cacti_sizeof')) {
	function cacti_sizeof($array) {
		return is_array($array) ? count($array) : 0;
	}
}

if (!function_exists('is_realm_allowed')) {
	function is_realm_allowed($realm) {
		return true;
	}
}

if (!function_exists('raise_message')) {
	function raise_message($id, $text = '', $level = 0) {
	}
}

$GLOBALS['__test_request'] = array();

if (!function_exists('test_set_request')) {
	function test_set_request(array $vars) {
		$GLOBALS['__test_request'] = $vars;
	}
}

if (!function_exists('get_request_var')) {
	function get_request_var($name) {
		return isset($GLOBALS['__test_request'][$name]) ? $GLOBALS['__test_request'][$name] : '';
	}
}

if (!function_exists('isset_request_var')) {
	function isset_request_var($name) {
		return isset($GLOBALS['__test_request'][$name]);
	}
}

if (!function_exists('get_nfilter_request_var')) {
	function get_nfilter_request_var($name) {
		return isset($GLOBALS['__test_request'][$name]) ? $GLOBALS['__test_request'][$name] : '';
	}
}

if (!function_exists('get_filter_request_var')) {
	function get_filter_request_var($name) {
		return isset($GLOBALS['__test_request'][$name]) ? $GLOBALS['__test_request'][$name] : '';
	}
}

if (!function_exists('form_input_validate')) {
	function form_input_validate($value, $name, $regex, $optional, $error) {
		return $value;
	}
}

if (!function_exists('is_error_message')) {
	function is_error_message() {
		return false;
	}
}

if (!function_exists('sql_save')) {
	function sql_save($array, $table, $key = 'id') {
		return isset($array['id']) ? $array['id'] : 1;
	}
}

if (!function_exists('api_user_realm_auth')) {
	function api_user_realm_auth($file) {
		return true;
	}
}

if (!function_exists('get_allowed_trees')) {
	function get_allowed_trees() {
		return array();
	}
}

if (!function_exists('array_rekey')) {
	function array_rekey($array, $index, $value = null) {
		$ret = array();

		if (is_array($array)) {
			foreach ($array as $item) {
				if ($value === null) {
					$ret[$item[$index]] = $item;
				} elseif (is_array($value)) {
					$ret[$item[$index]] = array();

					foreach ($value as $v) {
						$ret[$item[$index]][$v] = $item[$v];
					}
				} else {
					$ret[$item[$index]] = $item[$value];
				}
			}
		}

		return $ret;
	}
}

if (!function_exists('plugin_test_read_source')) {
	function plugin_test_read_source($relative_file) {
		$path = realpath(__DIR__ . '/../' . $relative_file);
		if ($path === false) {
			throw new RuntimeException("Unable to resolve required file: {$relative_file}");
		}

		$contents = file_get_contents($path);
		if ($contents === false) {
			throw new RuntimeException("Unable to read required file: {$relative_file}");
		}

		return $contents;
	}
}
