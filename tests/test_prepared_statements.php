<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | Regression checks for prepared DB helper migration in cycle plugin      |
 |                                                                         |
 | Run: php tests/test_prepared_statements.php                             |
 +-------------------------------------------------------------------------+
 */

$pass = 0;
$fail = 0;

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

$cycle_file = __DIR__ . '/../cycle.php';
$setup_file = __DIR__ . '/../setup.php';

$cycle_contents = file_get_contents($cycle_file);
$setup_contents = file_get_contents($setup_file);

assert_true('cycle.php is readable', $cycle_contents !== false);
assert_true('setup.php is readable', $setup_contents !== false);

$cycle_contents = ($cycle_contents === false ? '' : $cycle_contents);
$setup_contents = ($setup_contents === false ? '' : $setup_contents);

assert_true(
	'cycle.php uses prepared tree-id lookup',
	preg_match_all('/db_fetch_cell_prepared\s*\(\s*\'SELECT id\s+FROM graph_tree/s', $cycle_contents, $cycle_matches) >= 2
);
assert_true(
	'cycle.php no longer uses raw tree-id db_fetch_cell lookup',
	preg_match('/db_fetch_cell\s*\(\s*[\'"]SELECT\s+id\s+FROM\s+graph_tree\s+ORDER\s+BY\s+name\s+LIMIT\s+1[\'"]/i', $cycle_contents) === 0
);
assert_true(
	'setup.php uses prepared plugin_config row lookup',
	preg_match('/db_fetch_row_prepared\s*\(\s*\'SELECT \*\s+FROM plugin_config/s', $setup_contents) === 1
);
assert_true(
	'setup.php uses prepared plugin_realm lookup',
	preg_match('/db_fetch_cell_prepared\s*\(\s*\'SELECT id\s+FROM plugin_realms/s', $setup_contents) === 1
);
assert_true(
	'setup.php uses prepared user realm list lookup',
	preg_match('/db_fetch_assoc_prepared\s*\(\s*\'SELECT user_id\s+FROM user_auth_realm/s', $setup_contents) === 1
);
assert_true(
	'setup.php uses prepared realm insert/delete updates',
	preg_match_all('/db_execute_prepared\s*\(/', $setup_contents, $setup_execute_matches) >= 3
);
assert_true(
	'setup.php preserves legacy realm migration semantics',
	preg_match('/\$legacy_realm_id\s*=\s*42\s*;/', $setup_contents) === 1
	&& preg_match('/INSERT INTO user_auth_realm\s*\(realm_id,\s*user_id\)\s*VALUES\s*\(\?,\s*\?\)/s', $setup_contents) === 1
	&& preg_match('/DELETE FROM user_auth_realm[\s\S]*WHERE user_id = \?[\s\S]*AND realm_id = \?/s', $setup_contents) === 1
	&& preg_match('/array\(\$u\[\'user_id\'\],\s*\$legacy_realm_id\)/', $setup_contents) === 1
);
assert_true(
	'setup.php no longer concatenates user_id in SQL strings',
	strpos($setup_contents, "WHERE user_id=' . \$u['user_id'] . '") === false
);

echo "\n";
echo "Results: $pass passed, $fail failed\n";

exit($fail > 0 ? 1 : 0);
