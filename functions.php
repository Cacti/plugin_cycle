<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2016 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

$graphs_array = array(
    1   => __('%d Graph', 1),
    2   => __('%d Graphs', 2),
    4   => __('%d Graphs', 4),
    6   => __('%d Graphs', 6),
    8   => __('%d Graphs', 8),
    9   => __('%d Graphs', 9),
    10  => __('%d Graphs', 10),
    12  => __('%d Graphs', 12),
    15  => __('%d Graphs', 15),
    16  => __('%d Graphs', 16),
    18  => __('%d Graphs', 18),
    20  => __('%d Graphs', 20),
    24  => __('%d Graphs', 24),
    25  => __('%d Graphs', 25),
    28  => __('%d Graphs', 28),
    30  => __('%d Graphs', 30),
    32  => __('%d Graphs', 32),
    35  => __('%d Graphs', 35),
    36  => __('%d Graphs', 36),
    40  => __('%d Graphs', 40),
    42  => __('%d Graphs', 42),
    48  => __('%d Graphs', 48),
    50  => __('%d Graphs', 50),
    60  => __('%d Graphs', 60),
    70  => __('%d Graphs', 70),
    80  => __('%d Graphs', 80),
    90  => __('%d Graphs', 90),
    100 => __('%d Graphs', 100)
);

$graph_cols = array(
	1  => __('%d Column', 1),
	2  => __('%d Columns', 2),
	3  => __('%d Columns', 3),
	4  => __('%d Columns', 4),
	5  => __('%d Columns', 5),
	6  => __('%d Columns', 6),
	7  => __('%d Columns', 7),
	8  => __('%d Columns', 8)
);

function save_settings() {
	validate_request_vars();

	if (sizeof($_REQUEST)) {
	foreach($_REQUEST as $var => $value) {
		switch($var) {
		case 'timespan':
			set_user_setting('cycle_timespan', get_request_var('timespan'));
			break;
		case 'refresh':
			set_user_setting('cycle_delay', get_request_var('refresh'));
			break;
		case 'graphs':
			set_user_setting('cycle_graphs', get_request_var('graphs'));
			break;
		case 'cols':
			set_user_setting('cycle_columns', get_request_var('cols'));
			break;
		case 'height':
			set_user_setting('cycle_height', get_request_var('height'));
			break;
		case 'width':
			set_user_setting('cycle_width', get_request_var('width'));
			break;
		case 'legend':
			set_user_setting('cycle_legend', get_request_var('legend'));
			break;
		case 'filter':
			set_user_setting('cycle_filter', get_request_var('filter'));
			break;
		}
	}
	}

	validate_request_vars(true);
}

function validate_request_vars($force = false) {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			),
		'tree_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_config_option('cycle_custom_graphs_tree', $force),
			),
		'leaf_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-2'
			),
		'graphs' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_user_setting('cycle_graphs', read_config_option('cycle_graphs'), $force)
			),
		'cols' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_user_setting('cycle_columns', read_config_option('cycle_columns'), $force)
			),
		'width' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_user_setting('cycle_width', read_config_option('cycle_width'), $force)
			),
		'height' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_user_setting('cycle_height', read_config_option('cycle_height'), $force)
			),
		'timespan' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_user_setting('cycle_timespan', read_config_option('cycle_timespan'), $force)
			),
		'delay' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_user_setting('cycle_delay', read_config_option('cycle_delay'), $force)
			),
		'legend' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => read_user_setting('cycle_legend', read_config_option('cycle_legend'), $force),
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => read_user_setting('cycle_filter', '', $force),
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_cycle');
	/* ================= input validation ================= */
}

function cycle_set_defaults() {
	$user = $_SESSION['sess_user_id'];

	if (!isset($_SESSION['sess_cycle_defaults'])) {
		$defaults = array(
			'cycle_delay'      => '60',
			'cycle_timespan'   => '5',
			'cycle_columns'    => '2',
			'cycle_graphs'     => '4',
			'cycle_height'     => '100',
			'cycle_width'      => '400',
			'cycle_font_size'  => '8',
			'cycle_font_face'  => '',
			'max_length'       => '100',
			'cycle_font_color' => '1',
			'cycle_legend'     => '',
			'cycle_custom_graphs_type' => '2'
		);

		foreach($defaults as $name => $value) {
			$current = db_fetch_cell("SELECT value FROM settings_user WHERE name='$name' AND user_id=$user");
			if ($current === false) {
				db_execute("REPLACE INTO settings_user (user_id,name,value) VALUES ($user, '$name', '$value')");
			}
		}

		$_SESSION['sess_cycle_defaults'] = true;
	}
}

function get_next_graphid($graphpp, $filter, $graph_tree, $leaf_id) {
	global $id, $graph_id, $graphs, $next_graph_id, $prev_graph_id;

	/* if no default graph list has been specified, default to 0 */
	$type = read_config_option('cycle_custom_graphs_type');
	if ($type == 1 && !strlen(read_config_option('cycle_custom_graphs_list'))) {
		$type = 0;
	}

	switch($type) {
	case '0':
	case '1':
	case '2':
		$graph_id = $id;

		if ($graph_id <= 0) {
			$graph_id = 0;
		}

		$sql_where = "WHERE gl.id>=$graph_id";

		if (strlen($filter)) $sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " gtg.title_cache RLIKE '$filter'";

		if ($type == 1) {
			$cases = explode(',', read_config_option('cycle_custom_graphs_list'));
			sort($cases);
			$newcase = '';
			foreach($cases as $case) {
				$newcase .= (is_numeric($case) ? (strlen($newcase) ? ',':'') . $case:'');
			}

			if (strlen($newcase)) $sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " gl.id IN($newcase)";
		}elseif ($type == 2) {
			$graph_data = get_tree_graphs($graph_tree, $leaf_id);
			$local_graph_ids = array_keys($graph_data);
			sort($local_graph_ids);

			if (sizeof($local_graph_ids)) {
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' gl.id IN(' . implode(',', $local_graph_ids) . ')';
			}else{
				break;
			}
		}

		$done          = false;
		$next_found    = false;
		$start         = 0;
		$next_graph_id = 0;
		$prev_graph_id = 0;
		$title         = '';
		$graphs        = array();
		$i             = 0;

		/* Build a graphs array of the number of graphs requested
		 * this graph array will be used for rendering.  In addition
		 * when the user hit's next, or the graphs cycle, we need
		 * to know the next graph id to display.  Calculate that
		 * based upon the offset $graphpp.  If we overflow,  start
		 * from the beginning, which is the second section until
		 * we either run out of rows, or reach the $graphpp limit.
		 *
		 * Finally, don't try to grap all graphs at once.  It takes
		 * too much memory on big systems.
		 */

		/* first pass is moving up in ids */
		while (!$done) {
			$sql = "SELECT
				gl.id,
				gtg.title_cache
				FROM graph_local AS gl
				INNER JOIN graph_templates_graph AS gtg
				ON (gtg.local_graph_id=gl.id)
				$sql_where
				ORDER BY gl.id ASC
				LIMIT $start, $graphpp";

			$rows = db_fetch_assoc($sql);

			if ($graph_id > 0) {
				$curr_found    = true;
			}else{
				$curr_found    = false;
			}

			if (sizeof($rows)) {
			foreach ($rows as $row) {
				if (is_graph_allowed($row['id'])) {
					if (!$curr_found) {
						$graph_id   = $row['id'];
						$title      = $row['title_cache'];
						$curr_found = true;

						$graphs[$graph_id]['graph_id'] = $graph_id;
						$i++;
					}else{
						if (sizeof($graphs) < $graphpp) {
							$graphs[$row['id']]['graph_id'] = $row['id'];
							$i++;
						}else{
							$next_graph_id = $row['id'];
							$next_found    = true;

							break;
						}
					}
				}
			}
			}

			if ($next_graph_id > 0) {
				$done = true;
			}elseif (sizeof($rows) == 0) {
				$done = true;
			}else{
				$start += $graphpp;
			}
		}

		/* If we did not find all the graphs requested,
		 * move backwards from lowest graph id until the
		 * array is fully populated or we run out of graphs.
		 */
		if (sizeof($graphs) < $graphpp || $next_graph_id == 0) {
			$sql_where = '';

			/* setup the standard filters less the starting range, in other words start from the first graph */
			if (strlen($filter)) $sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " gtg.title_cache RLIKE '$filter'";

			if (isset($local_graph_ids) && sizeof($local_graph_ids)) {
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' gl.id IN(' . implode(',', $local_graph_ids) . ')';
			}

			if (isset($newcase) && strlen($newcase)) $sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " gtg.title_cache RLIKE '$filter'";

			$start      = 0;
			$done       = false;
			$next_found = false;

			while (!$done) {
				$sql = "SELECT
					gl.id,
					gtg.title_cache
					FROM graph_local AS gl
					INNER JOIN graph_templates_graph AS gtg
					ON (gtg.local_graph_id=gl.id)
					$sql_where
					ORDER BY gl.id ASC
					LIMIT $start, $graphpp";

				$rows = db_fetch_assoc($sql);

				if (sizeof($rows)) {
				foreach ($rows as $row) {
					if (is_graph_allowed($row['id'])) {
						if (!$curr_found) {
							$graph_id   = $row['id'];
							$title      = $row['title_cache'];
							$curr_found = true;
							$graphs[$graph_id]['graph_id'] = $graph_id;
							$i++;
						}else{
							if (sizeof($graphs) < $graphpp) {
								$graphs[$row['id']]['graph_id'] = $row['id'];
								$i++;
							}else{
								$next_graph_id = $row['id'];
								$next_found    = true;
		
								break;
							}
						}
					}
				}
				}

				if ($next_graph_id > 0) {
					$done = true;
				}elseif (sizeof($rows) == 0) {
					$done = true;
				}else{
					$start += $graphpp;
				}
			}
		}

		/* When a user hits the 'Prev' button, we have to go backwards.
		 * Therefore, find the graph_id that would have to be used as 
		 * the starting point if the user were to hit the 'Prev' button.
		 *
		 * Just like the 'Next' button, we need to scan the database until
		 * we reach the $graphpp variable or until we run out of rows.  We
		 * also have to adjust for underflow in this case.
		 */
		$sql_where = "WHERE gl.id<$graph_id";

		/* setup the standard filters less the starting range, in other words start from the first graph */
		if (strlen($filter)) $sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " gtg.title_cache RLIKE '$filter'";

		if (isset($local_graph_ids) && sizeof($local_graph_ids)) {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' gl.id IN(' . implode(',', $local_graph_ids) . ')';
		}

		if (isset($newcase) && strlen($newcase)) $sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " gtg.title_cache RLIKE '$filter'";

		$done    = false;
		$start   = 0;
		$pgraphs = array();

		while (!$done) {
			$sql = "SELECT gl.id,
				gtg.title_cache
				FROM graph_local AS gl
				INNER JOIN graph_templates_graph AS gtg
				ON (gtg.local_graph_id=gl.id)
				$sql_where
				ORDER BY id DESC
				LIMIT $start, $graphpp";

			$rows = db_fetch_assoc($sql);

			if (sizeof($rows)) {
			foreach ($rows as $row) {
				if (is_graph_allowed($row['id'])) {
					if (sizeof($pgraphs) < ($graphpp-1)) {
						$pgraphs[] = $row['id'];
					}else{
						$prev_graph_id = $row['id'];
						break;
					}
				}
			}
			}

			if ($prev_graph_id > 0) {
				$done = true;
			}elseif (sizeof($rows) == 0) {
				$done = true;
			}else{
				$start += $graphpp;
			}
		}

		/* Now handle the underflow case, when we have not
		 * completed building the $pgraphs array to the
		 * correct size.
		 */
		if ($prev_graph_id == 0) {
			$sql_where = '';
			if (strlen($filter)) $sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " gtg.title_cache RLIKE '$filter'";

			$start = 0;
			$done  = false;

			while (!$done) {
				$sql = "SELECT gl.id,
					gtg.title_cache
					FROM graph_local AS gl
					INNER JOIN graph_templates_graph AS gtg
					ON (gtg.local_graph_id=gl.id)
					$sql_where
					ORDER BY id DESC
					LIMIT $start, $graphpp";

				$rows = db_fetch_assoc($sql);

				if (sizeof($rows)) {
				foreach ($rows as $row) {
					if (is_graph_allowed($row['id'])) {
						if (sizeof($pgraphs) < ($graphpp-1)) {
							$pgraphs[] = $row['id'];
						}else{
							$prev_graph_id = $row['id'];
							break;
						}
					}
				}
				}

				if ($prev_graph_id > 0) {
					$done = true;
				}elseif (sizeof($rows) == 0) {
					$done = true;
				}else{
					$start += $graphpp;
				}
			}
		}

		break;
	}
}

function get_tree_graphs($tree_id, $leaf_id) {
	if (is_tree_allowed($tree_id)) {
		if ($leaf_id == -2) {
			$sql_leaf = 'parent=0 AND';
		}elseif ($leaf_id > 0) {
			$sql_leaf = 'parent=' . $leaf_id . ' AND';
		}else{
			$sql_leaf = '';
		}

		$items = db_fetch_assoc('SELECT *
			FROM graph_tree_items AS gti
			WHERE ' . $sql_leaf . ' graph_tree_id=' . $tree_id);

		$graphs   = array();
		$hosts    = array();
		$outArray = array();

		if (sizeof($items)) {
			foreach($items as $i) {
				if ((empty($i['title'])) && ($i['local_graph_id'] > 0 )) {
					$graphs[$i['local_graph_id']] = $i['local_graph_id'];
				} elseif ($i['host_id'] > 0 && is_device_allowed($i['host_id'])) {
					$hosts[$i['host_id']] = $i['host_id'];
				}elseif ($leaf_id > -2) {
					$outArray += get_tree_graphs($tree_id, $i['id']);	
				}
			}
		}

		if (sizeof($hosts) && sizeof($graphs)) {
			$graphs = get_allowed_graphs('(h.id IN(' . implode(',', $hosts) . ') OR gl.id IN(' . implode(',', $graphs) . '))');
		}elseif(sizeof($hosts)) {
			$graphs = get_allowed_graphs('(h.id IN(' . implode(',', $hosts) . '))');
		}elseif(sizeof($graphs)) {
			$graphs = get_allowed_graphs('(gl.id IN(' . implode(',', $graphs) . '))');
		}
	}
	
	if (sizeof($graphs)) {
		foreach($graphs as $i) {
			$outArray[$i['local_graph_id']] = $i['title_cache'];
		}
	}

	return($outArray);
}

