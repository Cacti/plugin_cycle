<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for cycle_config_arrays() in setup.php - populates the
 * lookup arrays used by the settings and graph-cycling pages.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

it('populates the graph/column/dimension option arrays and returns true', function () {
	global $cycle_graphs, $cycle_cols, $cycle_width, $cycle_height;

	expect(cycle_config_arrays())->toBeTrue();

	expect($cycle_graphs)->toHaveKey(4);
	expect($cycle_cols)->toHaveKey(2);
	expect($cycle_height)->toHaveKey(100);
	expect($cycle_width)->toHaveKey(400);
});
