<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

// Unit coverage for get_next_graphid() custom graph list pagination.

beforeAll(function () {
	require_once __DIR__ . '/../../includes/functions.php';
});

beforeEach(function () {
	cycle_test_reset_db_mocks();
	$GLOBALS['__test_allowed_graphs'] = [];
	test_set_config_option('cycle_custom_graphs_type', '1');
	test_set_config_option('cycle_custom_graphs_list', '10,20');

	$GLOBALS['id']             = 20;
	$GLOBALS['graph_id']       = 0;
	$GLOBALS['graphs']         = [];
	$GLOBALS['next_graph_id']  = 0;
	$GLOBALS['prev_graph_id']  = 0;
});

it('keeps the custom graph list when wrapping pagination', function () {
	$sql_calls = [];
	cycle_test_mock_db('db_fetch_assoc', function () {
		return true;
	}, function ($sql) use (&$sql_calls) {
		$sql_calls[] = $sql;

		return [];
	});

	get_next_graphid(2, '', 0, 0);

	$wrap_queries = array_filter($sql_calls, function ($sql) {
		return strpos($sql, 'gl.id>=20') === false;
	});

	expect($wrap_queries)->not->toBeEmpty();

	foreach ($wrap_queries as $sql) {
		expect($sql)->toContain('gl.id IN(10,20)');
	}
});

it('matches no graphs when the custom list contains no numeric ids', function () {
	test_set_config_option('cycle_custom_graphs_list', 'invalid');
	$sql_calls = [];
	cycle_test_mock_db('db_fetch_assoc', function () {
		return true;
	}, function ($sql) use (&$sql_calls) {
		$sql_calls[] = $sql;

		return [];
	});

	get_next_graphid(2, '', 0, 0);

	expect($sql_calls)->not->toBeEmpty();

	foreach ($sql_calls as $sql) {
		expect($sql)->toContain('gl.id IN(0)');
	}
});

it('uses the first matching graph when the requested current graph is outside the selection', function () {
	$GLOBALS['id'] = 5;
	cycle_test_mock_db('db_fetch_assoc', function () {
		return true;
	}, function ($sql) {
		if (strpos($sql, 'ORDER BY gl.id ASC') !== false && strpos($sql, 'LIMIT 0, 2') !== false) {
			return [
				['id' => 10, 'title_cache' => 'First'],
				['id' => 20, 'title_cache' => 'Last'],
			];
		}

		if (strpos($sql, 'ORDER BY id DESC') !== false) {
			return [
				['id' => 20, 'title_cache' => 'Last'],
				['id' => 10, 'title_cache' => 'First'],
			];
		}

		return [];
	});

	get_next_graphid(2, '', 0, 0);

	expect($GLOBALS['graph_id'])->toBe(10);
	expect(array_keys($GLOBALS['graphs']))->toBe([10, 20]);
});

it('keeps the graph tree filter when previous-page pagination wraps', function () {
	test_set_config_option('cycle_custom_graphs_type', '2');
	test_set_config_option('cycle_custom_graphs_list', '');
	$GLOBALS['id']                    = 10;
	$GLOBALS['__test_allowed_graphs'] = [
		['local_graph_id' => 10, 'title_cache' => 'First'],
		['local_graph_id' => 20, 'title_cache' => 'Last'],
	];
	$sql_calls = [];
	cycle_test_mock_db('db_fetch_assoc', function () {
		return true;
	}, function ($sql) use (&$sql_calls) {
		$sql_calls[] = $sql;

		if (strpos($sql, 'FROM graph_tree_items') !== false) {
			return [
				['title' => '', 'local_graph_id' => 10],
				['title' => '', 'local_graph_id' => 20],
			];
		}

		if (strpos($sql, 'gl.id<10') !== false) {
			return [];
		}

		if (strpos($sql, 'ORDER BY id DESC') !== false) {
			if (strpos($sql, 'gl.id IN(10,20)') !== false) {
				return [
					['id' => 20, 'title_cache' => 'Last'],
					['id' => 10, 'title_cache' => 'First'],
				];
			}

			return [
				['id' => 99, 'title_cache' => 'Outside tree'],
				['id' => 98, 'title_cache' => 'Outside tree'],
			];
		}

		return [];
	});

	get_next_graphid(2, '', 1, 0);

	$underflow_queries = array_filter($sql_calls, function ($sql) {
		return strpos($sql, 'ORDER BY id DESC') !== false && strpos($sql, 'gl.id<10') === false;
	});

	expect($underflow_queries)->not->toBeEmpty();

	foreach ($underflow_queries as $sql) {
		expect($sql)->toContain('gl.id IN(10,20)');
	}

	expect($GLOBALS['prev_graph_id'])->toBe(10);
});
