<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2008 The Cacti Group                                 |
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

chdir('../../');

include_once("./include/global.php");
include_once("./lib/time.php");
include_once("./lib/html_tree.php");
include_once("./lib/api_graph.php");
include_once("./lib/api_tree.php");
include_once("./lib/api_data_source.php");

$graphs_array = array(
	1  => "1 Graph",
	2  => "2 Graphs",
	4  => "4 Graphs",
	6  => "6 Graphs",
	8  => "8 Graphs",
	10 => "10 Graphs"
);

$graph_cols = array(
	1  => "1 Column",
	2  => "2 Columns",
	3  => "3 Columns",
	4  => "4 Columns",
	5  => "5 Columns"
);

/* ================= input validation ================= */
input_validate_input_number(get_request_var_request("tree_id"));
input_validate_input_number(get_request_var_request("leaf_id"));
input_validate_input_number(get_request_var_request("timespan"));
input_validate_input_number(get_request_var_request("graphs"));
input_validate_input_number(get_request_var_request("graph"));
input_validate_input_number(get_request_var_request("cols"));
input_validate_input_number(get_request_var_request("id"));
input_validate_input_number(get_request_var_request("width"));
input_validate_input_number(get_request_var_request("height"));
/* ==================================================== */

/* clean up search string */
if (isset($_REQUEST["clear"])) {
	unset($_REQUEST["filter"]);
	$_SESSION["sess_cycle_filter"] = "";
} elseif (isset($_REQUEST["set"])) {
	/* If the user pushed the 'Set' button */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = cycle_sanitize_search_string(get_request_var_request("filter"));

		if (empty($_REQUEST["filter"])) {
			unset($_REQUEST["filter"]);
			$_SESSION["sess_cycle_filter"] = "";
		}
	}
} else {
	/* If the user is not setting or clearing the search filter,
	 * then clear the request. Later code will pick up the
	 * session variable, or the default, and use that as the
	 * request instead.
	 */
	unset($_REQUEST["filter"]);
}

/* clean up legend */
if (isset($_REQUEST["legend"])) {
	$_REQUEST["legend"] = sanitize_search_string(get_request_var_request("legend"));
}

$changed = false;
$changed += cycle_check_changed("filter", "sess_cycle_filter");
$changed += cycle_check_changed("tree_id", "sess_cycle_tree_id");
$changed += cycle_check_changed("leaf_id", "sess_cycle_leaf_id");
$changed += cycle_check_changed("graphs", "sess_cycle_graphs_pp");

if ($changed) {
	$_REQUEST["id"] = -1;
}

/* remember these search fields in session vars so we don't have to keep passing them around */
load_current_session_value("filter",   "sess_cycle_filter",   "");
load_current_session_value("tree_id",  "sess_cycle_tree_id",  read_config_option("cycle_custom_graphs_tree"));
load_current_session_value("leaf_id",  "sess_cycle_leaf_id",  "-2");
load_current_session_value("graphs",   "sess_cycle_graphs_pp",  read_config_option("cycle_graphs"));
load_current_session_value("cols",     "sess_cycle_graph_cols", read_config_option("cycle_columns"));
load_current_session_value("legend",   "sess_cycle_legend",   read_config_option("cycle_legend"));
load_current_session_value("action",   "sess_cycle_action",   "view");
load_current_session_value("width",    "sess_cycle_width",    read_config_option("cycle_width"));
load_current_session_value("height",   "sess_cycle_height",   read_config_option("cycle_height"));
load_current_session_value("timespan", "sess_cycle_timespan", read_config_option("cycle_timespan"));
load_current_session_value("refresh",  "sess_cycle_delay",    read_config_option("cycle_delay"));

$legend  = get_request_var_request("legend");
$tree_id = get_request_var_request("tree_id");
$leaf_id = get_request_var_request("leaf_id");
$graphpp = get_request_var_request("graphs");
$cols    = get_request_var_request("cols");
$filter  = get_request_var_request("filter");
$id      = get_request_var_request("id");
$width   = get_request_var_request("width");
$height  = get_request_var_request("height");

if (empty($tree_id)) $tree_id = db_fetch_cell("SELECT id FROM graph_tree ORDER BY name LIMIT 1");
if (empty($id))      $id      = -1;

/* get the start and end times for the graph */
$timespan        = array();
$first_weekdayid = read_graph_config_option("first_weekdayid");
get_timespan($timespan, time(), get_request_var_request("timespan") , $first_weekdayid);

$graph_tree = $tree_id;
$html       = "";
$out        = "";

/* detect the next graph regardless of type */
get_next_graphid($graphpp, $filter, $graph_tree, $leaf_id);

switch(read_config_option("cycle_custom_graphs_type")) {
case "0":
case "1":
	/* will only use the filter for full rotation */

	break;
case "2":
	$tree_list = get_graph_tree_array();
	if (sizeof($tree_list) > 1) {
		$html ="<select id='tree_id' name='tree_id' onChange='newTree()' title='Select Graph Tree to View'>\n";

		foreach ($tree_list as $tree) {
			$html .= "<option value='".$tree["id"]."'".($graph_tree == $tree["id"] ? " selected" : "").">".title_trim($tree["name"], 30)."</option>\n";
		}

		$html .= "</select>\n";

		$leaves = db_fetch_assoc("SELECT * FROM graph_tree_items WHERE title!='' AND graph_tree_id='$graph_tree' ORDER BY order_key");

		if (sizeof($leaves)) {
			$html .= "<select id='leaf_id' name='leaf_id' onChange='newTree()' title='Select Tree Leaf to Display'>\n";

			$html .= "<option value='-1'" . ($leaf_id == -1 ? " selected" : "") . ">All Levels</option>\n";
			$html .= "<option value='-2'" . ($leaf_id == -2 ? " selected" : "") . ">Top Level</option>\n";

			foreach ($leaves as $leaf) {
				$html .= "<option value='" . $leaf["id"] . "'" . ($leaf_id == $leaf["id"] ? " selected":"") . ">" . $leaf["title"] . "</option>\n";
			}

			$html .= "</select>\n";
		}
	}
}

/* process the filter section */
$html .= "<input id='filter' name='filter' type='textbox' title='Enter Regular Expression Match (only alpha, numeric, and special characters \"(^_|?)\" permitted)' size='40' onkeypress='processReturn(event)' value='" . $filter . "'>";

/* create the graph structure and output */
$out       = '<table cellpadding="5" cellspacing="5" border="0">';
$max_cols  = $cols;
$col_count = 1;

if (sizeof($graphs)) {
	foreach($graphs as $graph) {
		if ($col_count == 1)
			$out .= '<tr>';

		$out .= '<td align="center" class="graphholder" style="width:' . (read_config_option('cycle_width')) . 'px;">'
			.'<a href = ../../graph.php?local_graph_id='.$graph['graph_id'].'&rra_id=all>'
			."<img border='0' src='../../graph_image.php?local_graph_id=".$graph['graph_id']."&rra_id=0&graph_start=".$timespan["begin_now"]
			.'&graph_end='.time().'&graph_width='.$width.'&graph_height='.$height.($legend==0 || $legend=='' ? '&graph_nolegend=true' : '')."'>"
			.'</a></td>';

		$out .= "<td valign='top' style='padding: 3px;' class='noprint' width='10px'>" . 
			"<a href='./../../graph.php?action=zoom&local_graph_id=" . $graph['graph_id'] . "&rra_id=5&view_type='><img src='./../../images/graph_zoom.gif' border='0' alt='Zoom Graph' title='Zoom Graph' style='padding: 3px;'></a><br>" . 
			"<a href='./../../graph_xport.php?local_graph_id=" . $graph['graph_id'] . "&rra_id=5&view_type='><img src='./../../images/graph_query.png' border='0' alt='CSV Export' title='CSV Export' style='padding: 3px;'></a><br>" . 
			"<a href='./../../graph.php?action=properties&local_graph_id=" . $graph['graph_id'] . "&rra_id=5&view_type='><img src='./../../images/graph_properties.gif' border='0' alt='Graph Source/Properties' title='Graph Source/Properties' style='padding: 3px;'></a><br>";

		ob_start();
		api_plugin_hook('graph_buttons', array('hook' => 'view', 'local_graph_id' =>$graph['graph_id'], 'rra' => '5', 'view_type' => ""));
		$out .= ob_get_clean();

		if ($col_count == $max_cols) {
			$out .= '</tr>';
			$col_count=1;
		} else {
			$col_count++;
		}
	}
}else{
	$out = "<h1>No Graphs Found Matching Criteria</h1>";
}

if ($col_count  <= $max_cols) {
	$col_count--;
	$addcols = $max_cols - $col_count;

	for($x=1; $x <= $addcols; $x++) {
		$out .= '<td class="graphholder">&nbsp;</td>';
	}

	$out .= '</tr>';
}

$out .= '</table>';

$output = array("html" => $html, "graphid" => $graph_id, "nextgraphid" => $next_graph_id, "prevgraphid" => $prev_graph_id, "image" => base64_encode($out));
print json_encode($output);

exit;

function cycle_check_changed($request, $session) {
	if ((isset($_REQUEST[$request])) && (isset($_SESSION[$session]))) {
		if ($_REQUEST[$request] != $_SESSION[$session]) {
			return 1;
		}
	}
}

function get_next_graphid($graphpp, $filter, $graph_tree, $leaf_id) {
	global $id, $graph_id, $graphs, $next_graph_id, $prev_graph_id;

	/* if no default graph list has been specified, default to 0 */
	$type = read_config_option("cycle_custom_graphs_type");
	if ($type == 1 && !strlen(read_config_option("cycle_custom_graphs_list"))) {
		$type = 0;
	}

	switch($type) {
	case "0":
	case "1":
	case "2":
		$graph_id = $id;

		if ($graph_id <= 0) {
			$graph_id = 0;
		}

		$sql_where = "WHERE gl.id>=$graph_id";

		if (strlen($filter)) $sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gtg.title_cache RLIKE '$filter'";

		if ($type == 1) {
			$cases = explode(",", read_config_option("cycle_custom_graphs_list"));
			sort($cases);
			$newcase = "";
			foreach($cases as $case) {
				$newcase .= (is_numeric($case) ? (strlen($newcase) ? ",":"") . $case:"");
			}

			if (strlen($newcase)) $sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gl.id IN($newcase)";
		}elseif ($type == 2) {
			$graph_data = get_tree_graphs($graph_tree, $leaf_id);
			$local_graph_ids = array_keys($graph_data);
			sort($local_graph_ids);
			if (sizeof($local_graph_ids)) {
				$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gl.id IN(" . implode(",", $local_graph_ids) . ")";
			}else{
				break;
			}
		}

		$done          = false;
		$next_found    = false;
		$start         = 0;
		$next_graph_id = 0;
		$prev_graph_id = 0;
		$title         = "";
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

			//cacti_log(str_replace("\n", " ", str_replace("\t", " ", "First Pass:" . $sql)));

			if ($graph_id > 0) {
				$curr_found    = true;
			}else{
				$curr_found    = false;
			}

			if (sizeof($rows)) {
			foreach ($rows as $row) {
				if (is_graph_allowed($row["id"])) {
					if (!$curr_found) {
						$graph_id   = $row['id'];
						$title      = $row['title_cache'];
						$curr_found = true;
						//cacti_log("Found (1) current graph id '" . $row['id'] . "'", false);
						$graphs[$graph_id]['graph_id'] = $graph_id;
						$i++;
					}else{
						if (sizeof($graphs) < $graphpp) {
							//cacti_log("Found (1) graph id '" . $row['id'] . "'", false);
							$graphs[$row['id']]['graph_id'] = $row['id'];
							$i++;
						}else{
							//cacti_log("Found (1) next graph id '" . $row['id'] . "'", false);
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
			$sql_where = "";

			/* setup the standard filters less the starting range, in other words start from the first graph */
			if (strlen($filter)) $sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gtg.title_cache RLIKE '$filter'";

			if (isset($local_graph_ids) && sizeof($local_graph_ids)) {
				$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gl.id IN(" . implode(",", $local_graph_ids) . ")";
			}

			if (isset($newcase) && strlen($newcase)) $sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gtg.title_cache RLIKE '$filter'";

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

				//cacti_log(str_replace("\n", " ", str_replace("\t", " ", "Second Pass:" . $sql)));

				if (sizeof($rows)) {
				foreach ($rows as $row) {
					if (is_graph_allowed($row["id"])) {
						if (!$curr_found) {
							$graph_id   = $row['id'];
							$title      = $row['title_cache'];
							$curr_found = true;
							//cacti_log("Found (2) current graph id '" . $row['id'] . "'", false);
							$graphs[$graph_id]['graph_id'] = $graph_id;
							$i++;
						}else{
							if (sizeof($graphs) < $graphpp) {
								//cacti_log("Found (2) graph id '" . $row['id'] . "'", false);
								$graphs[$row['id']]['graph_id'] = $row['id'];
								$i++;
							}else{
								//cacti_log("Found (2) next graph id '" . $row['id'] . "'", false);
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
		if (strlen($filter)) $sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gtg.title_cache RLIKE '$filter'";

		if (isset($local_graph_ids) && sizeof($local_graph_ids)) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gl.id IN(" . implode(",", $local_graph_ids) . ")";
		}

		if (isset($newcase) && strlen($newcase)) $sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gtg.title_cache RLIKE '$filter'";

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
						//cacti_log("Found (1) prev graph id '" . $row['id'] . "'", false);
						$pgraphs[] = $row['id'];
					}else{
						//cacti_log("Found (1) prev prev graph id '" . $row['id'] . "'", false);
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
			$sql_where = "";
			if (strlen($filter)) $sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " gtg.title_cache RLIKE '$filter'";

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
							//cacti_log("Found (2) prev graph id '" . $row['id'] . "'", false);
							$pgraphs[] = $row['id'];
						}else{
							//cacti_log("Found (2) prev prev graph id '" . $row['id'] . "'", false);
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
	/* graph permissions */
	if (read_config_option("auth_method") != 0) {
		/* at this point this user is good to go... so get some setting about this
		user and put them into variables to save excess SQL in the future */
		$current_user = db_fetch_row("SELECT * FROM user_auth WHERE id=" . $_SESSION["sess_user_id"]);
	
		/* find out if we are logged in as a 'guest user' or not */
		if (db_fetch_cell("SELECT id FROM user_auth WHERE username='" . read_config_option("guest_user") . "'") == $_SESSION["sess_user_id"]) {
			$using_guest_account = true;
		}
	
		/* find out if we should show the "console" tab or not, based on this user's permissions */
		if (sizeof(db_fetch_assoc("SELECT realm_id FROM user_auth_realm WHERE realm_id=8 AND user_id=" . $_SESSION["sess_user_id"])) == 0) {
			$show_console_tab = false;
		}
	}
	
	/* check permissions */
	if (read_config_option("auth_method") != 0) {
		/* get policy information for the sql where clause */
		$sql_where = get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_hosts"], $current_user["policy_graph_templates"]);
		$sql_where = (empty($sql_where) ? "" : "WHERE (" . $sql_where . " OR graph_tree_items.local_graph_id=0)");
			
		$sql_join = "LEFT JOIN graph_templates_graph ON (graph_templates_graph.local_graph_id=graph_tree_items.local_graph_id)
			LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id
			AND user_auth_perms.type=1 AND user_auth_perms.user_id=".$_SESSION["sess_user_id"].")
			OR (graph_tree_items.host_id=user_auth_perms.item_id AND user_auth_perms.type=3 AND user_auth_perms.user_id=".$_SESSION["sess_user_id"].")
			OR (graph_templates_graph.graph_template_id=user_auth_perms.item_id AND user_auth_perms.type=4 AND user_auth_perms.user_id=".$_SESSION["sess_user_id"]."))";
			
	}else{
		$sql_where = "";
		$sql_join = "LEFT JOIN graph_templates_graph ON (graph_templates_graph.local_graph_id=graph_tree_items.local_graph_id)";
	}

	if ($leaf_id == -2) { // Base items only
		$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . "order_key LIKE '___000%'";
	}elseif ($leaf_id > 0) { 
		$order_key = db_fetch_cell("SELECT order_key FROM graph_tree_items WHERE id=$leaf_id");
		if (strlen($order_key)) {
			$search_key = rtrim($order_key, '0');
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . "order_key LIKE '$search_key%'";
		}
	}

	$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . "graph_tree_items.title=''";

	$sql = "SELECT DISTINCT graph_tree_items.local_graph_id, graph_tree_items.host_id
		FROM graph_tree_items
		$sql_join
		$sql_where 
		" . (empty($sql_where) ? "WHERE" : "AND") . " graph_tree_items.graph_tree_id=$tree_id
		ORDER BY graph_tree_items.order_key";

	$rows     = db_fetch_assoc($sql);
	$outArray = array();

	if (sizeof($rows)) {
		foreach ($rows as $row) {
			if ((empty($row['title'])) && ($row['local_graph_id'] > 0 )) {
				/* graph on tree */
				$outArray[$row['local_graph_id']] = get_graph_title($row['local_graph_id']);
			} elseif ($row['host_id'] > 0) {
				/* host on tree */
				
				/* Check Permission */
				if (read_config_option("auth_method") != 0) {
					/* get policy information for the sql where clause */
					$sql_where = "WHERE " . get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_hosts"], $current_user["policy_graph_templates"]);
						
					$sql_join = "LEFT JOIN graph_templates_graph ON (graph_templates_graph.local_graph_id=graph_local.id)
						LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id
						AND user_auth_perms.type=1 AND user_auth_perms.user_id=".$_SESSION["sess_user_id"].")
						OR (graph_local.host_id=user_auth_perms.item_id AND user_auth_perms.type=3 AND user_auth_perms.user_id=".$_SESSION["sess_user_id"].")
						OR (graph_local.graph_template_id=user_auth_perms.item_id AND user_auth_perms.type=4 AND user_auth_perms.user_id=".$_SESSION["sess_user_id"]."))";
						
				}else{
					$sql_where = "";
					$sql_join = "LEFT JOIN graph_templates_graph ON (graph_templates_graph.local_graph_id=graph_tree_items.local_graph_id)";
				}

				$sql = "SELECT DISTINCT graph_local.id
					FROM graph_local
					$sql_join
					$sql_where 
					" . (empty($sql_where) ? "WHERE" : "AND") . " graph_local.host_id=" . $row['host_id']."
					ORDER BY graph_templates_graph.title";
								
				$rows2     = db_fetch_assoc($sql);
				if (count($rows2)) {
					$title_id = null;
					foreach ($rows2 as $row2) {
						if ($row2['id'] > 0) {
							$outArray[$row2['id']] = get_graph_title($row2['id']);
						}
					}
				}
			}
		}
	}
	
	return($outArray);
}

/* cycle_sanitize_search_string - cleans up a search string submitted by the user to be passed
   it has been modified from the Cacti version to allow specific other characters to allow
   for limited regular expression matches.
   @arg $string - the original raw search string
   @returns - the sanitized search string */
function cycle_sanitize_search_string($string) {
	static $drop_char_match =   array('$', '<', '>', '`', '\'', '"', ',', '~', '+', '[', ']', '{', '}', '#', ';', '!', '=');
	static $drop_char_replace = array(' ', ' ', ' ',  '',   '', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ');

	/* Replace line endings by a null */
	$string = preg_replace('/[\n\r]/is', '', $string);
	/* HTML entities like &nbsp; */
	$string = preg_replace('/\b&[a-z]+;\b/', '', $string);
	/* Remove URL's */
	$string = preg_replace('/\b[a-z0-9]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/]+)?/', '', $string);

	/* Filter out strange characters like ^, $, &, change "it's" to "its" */
	for($i = 0; $i < count($drop_char_match); $i++) {
		$string =  str_replace($drop_char_match[$i], $drop_char_replace[$i], $string);
	}

	$string = str_replace('*', '', $string);

	return $string;
}

