<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
 */

if (!function_exists('cycle_get_default_tree_id')) {
	function cycle_get_default_tree_id($tree_id) {
		if (empty($tree_id)) {
			$tree_id = db_fetch_cell('SELECT id
				FROM graph_tree
				ORDER BY name
				LIMIT 1');
		}

		return $tree_id;
	}
}

if (!function_exists('cycle_get_request_context')) {
	function cycle_get_request_context() {
		$context = array(
			'legend'  => get_request_var('legend'),
			'tree_id' => get_request_var('tree_id'),
			'leaf_id' => get_request_var('leaf_id'),
			'graphs'  => get_request_var('graphs'),
			'cols'    => get_request_var('cols'),
			'rfilter' => get_request_var('rfilter'),
			'id'      => get_request_var('id'),
			'width'   => get_request_var('width'),
			'height'  => get_request_var('height')
		);

		$context['tree_id'] = cycle_get_default_tree_id($context['tree_id']);
		$context['id']      = (empty($context['id']) ? -1 : $context['id']);

		return $context;
	}
}
