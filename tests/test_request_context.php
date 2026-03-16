<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | Regression checks for shared cycle request-context helpers              |
 |                                                                         |
 | Run: php tests/test_request_context.php                                 |
 +-------------------------------------------------------------------------+
 */

$pass = 0;
$fail = 0;
$request_values = array();
$db_queries = array();

function assert_true($label, $value) {
	global $pass, $fail;

	if ($value) {
		echo "PASS  $label\n";
		$pass++;
	} else {
		echo "FAIL  $label\n";
		$fail++;
	}
}

function get_request_var($key) {
	global $request_values;

	return (array_key_exists($key, $request_values) ? $request_values[$key] : '');
}

function db_fetch_cell($sql) {
	global $db_queries;
	$db_queries[] = $sql;

	return 777;
}

require_once __DIR__ . '/../cycle_helpers.php';

$request_values = array(
	'legend'  => 'true',
	'tree_id' => '',
	'leaf_id' => 19,
	'graphs'  => 8,
	'cols'    => 3,
	'rfilter' => 'prod',
	'id'      => '',
	'width'   => 500,
	'height'  => 200
);
$db_queries = array();

$context = cycle_get_request_context();

assert_true('empty tree_id falls back to first tree query', $context['tree_id'] === 777);
assert_true('empty id normalizes to -1', $context['id'] === -1);
assert_true('legend is preserved', $context['legend'] === 'true');
assert_true('fallback query executes once', count($db_queries) === 1);

$request_values['tree_id'] = 123;
$request_values['id'] = 456;
$db_queries = array();

$context = cycle_get_request_context();

assert_true('provided tree_id is preserved', $context['tree_id'] === 123);
assert_true('provided id is preserved', $context['id'] === 456);
assert_true('no fallback query when tree_id exists', count($db_queries) === 0);

$cycle_source = file_get_contents(__DIR__ . '/../cycle.php');
assert_true('cycle.php is readable', $cycle_source !== false);
$cycle_source = ($cycle_source === false ? '' : $cycle_source);

assert_true(
	'cycle.php uses shared request context helper',
	preg_match('/cycle_get_request_context\\s*\\(/', $cycle_source) === 1
);

echo "\n";
echo "Results: $pass passed, $fail failed\n";

exit($fail > 0 ? 1 : 0);
