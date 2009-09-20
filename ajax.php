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

$legend  = $_REQUEST["legend"];
$rrd_id  = $_REQUEST["rrdid"];
$tree_id = $_REQUEST["tree_id"];

if (isset($_GET["hide"])) {
	if (($_GET["hide"] == "0") || ($_GET["hide"] == "1")) {
		/* only update expand/contract info is this user has rights to keep their own settings */
		if ((isset($current_user)) && ($current_user["graph_settings"] == "on")) {
			db_execute("delete from settings_tree where graph_tree_item_id=" . $_GET["branch_id"] . " and user_id=" . $_SESSION["sess_user_id"]);
			db_execute("insert into settings_tree (graph_tree_item_id,user_id,status) values (" . $_GET["branch_id"] . "," . $_SESSION["sess_user_id"] . "," . $_GET["hide"] . ")");
		}
	}
}

if (!isset($_SESSION["sess_cycle_tree_id"])) {
	$_SESSION["sess_cycle_tree_id"] = read_config_option("cycle_custom_graphs_tree");
}

$_REQUEST['action']='view';

if (!empty($tree_id)) {
	$_SESSION["sess_cycle_tree_id"] = $tree_id;
}

if (!empty($legend)) {
	$_SESSION["sess_cycle_legend"] = $legend;
}

$graph_tree = $_SESSION["sess_cycle_tree_id"];
$graphid    = 0;

get_next_graphid();

$tree_list = get_graph_tree_array();

if (sizeof($tree_list) > 1) {
	$html ="<select id='tree' name='tree' onChange='newTree()'>\n";

	foreach ($tree_list as $tree) {
		$html .= "<option value='".$tree["id"]."'".($graph_tree == $tree["id"] ? " selected" : "").">".title_trim($tree["name"], 30)."</option>\n";
	}

	$html .= "</select>\n";
}

if (!empty($html)) {
	$tree_dropdown_html = $html;
} else {
	$tree_dropdown_html = '';
}

if (!(read_config_option("cycle_custom_graphs") == "on" && read_config_option("cycle_custom_graphs_type") != "1")) {
	$graph_drop_down = "<select id='graph' name='graph' onChange='newGraph()'>";
	if (read_config_option("cycle_custom_graphs") == "on") {
		if (read_config_option("cycle_custom_graphs_type") == "1" && read_config_option("cycle_custom_graphs_list")!="")  {
			$graphs   = explode(",", read_config_option("cycle_custom_graphs_list"));

			foreach($graphs as $graphs_id) {
				$sql = "SELECT
					graph_local.id,
					graph_templates_graph.title_cache
					FROM graph_local
					INNER JOIN graph_templates_graph
					ON graph_local.id=graph_templates_graph.local_graph_id ". ((!empty($graphs_id)) ? " WHERE graph_local.id=". $graphs_id : "" );

				$row     = db_fetch_row($sql);

				$graph_drop_down .= "<option value='" . $row['id'] . "'" . ($graphid == $row['id'] ? " selected" : "") . ">" . title_trim($row['title_cache'], 70) . "</option>\n";
			}
		} else {
			print $tree_dropdown_html;

			$graphs = get_tree_graphs($graph_tree);

			if ($_REQUEST['rrdid'] == -1) {
				$temp_array = array_values($graphs);
				$graphid    = $temp_array[0]['graph_data'][0]['graph_id'];
			}

			foreach($graphs as $data) {
				foreach($data as $subdata) {
					if (is_array($subdata)) {
						foreach($subdata as $graph) {
							$graph_drop_down .= "<option value='" . $graph['graph_id'] . "'" . ($graphid == $graph['graph_id'] ? " selected" : "") . ">" . title_trim($graph['graph_title'], 70) . "</option>\n";
						}
					}
				}
			}
		}
	} else {
		$sql = "SELECT
			graph_local.id,
			graph_templates_graph.title_cache
			FROM graph_local
			INNER JOIN graph_templates_graph
			ON graph_local.id=graph_templates_graph.local_graph_id ORDER BY graph_local.id ASC";

		$rows = db_fetch_assoc($sql);

		// mage a graph selector
		$graph_drop_down = "<select id='graph' name='graph' onChange='newGraph()'>";

		// add item
		foreach($rows as $graph) {
			$graph_drop_down .= "<option value='".$graph['id']."'";
			if ($graphid == $graph['id']) {
				$graph_drop_down .= " selected";
			}
			$graph_drop_down .= ">" . title_trim($graph['title_cache'], 70)."</option>\n";
		}
	}

	$graph_drop_down .= "</select>";

	print $graph_drop_down;
} else {
	print $tree_dropdown_html;
}

/* get the start and end times  for the graph */
$timespan        = array();
$first_weekdayid = read_graph_config_option("first_weekdayid");
get_timespan($timespan, time(), $_REQUEST["timespan"] , $first_weekdayid);

if (isset($graphid) && is_array($graphid)) {
	$out       = '<table cellpadding="0" cellspacing="0" border="0">';
	$max_cols  = read_config_option('cycle_columns');
	$col_count = 1;

	for ($x=0; $x < count($graphid); $x++) {
		if ($col_count == 1)
			$out .= '<tr>';

		$out .= '<td align="center" class="graphholder">'
			.'<a href = ../../graph.php?local_graph_id='.$graphid[$x]['graph_id'].'&rra_id=all>'
			."<img border='0''"." src='../../graph_image.php?local_graph_id=".$graphid[$x]['graph_id']."&rra_id=0&graph_start=".$timespan["begin_now"]
			.'&graph_end='.time().'&graph_width='.read_config_option('cycle_width').'&graph_height='.read_config_option('cycle_height').($legend=='false' ? '&graph_nolegend=true' : '')."'>"
			.'</a></td>';

		if ($col_count == $max_cols) {
			$out .= '</tr>';
			$col_count=1;
		} else {
			$col_count++;
		}
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


	print "!!!".$nextgraphid."!!!".$prevgraphid."!!!".$cur_leaf_id."!!!";
	print $out;
} else if ($graphid != 0) {
	print "!!!".$nextgraphid."!!!".$prevgraphid."!!!".$graphid."!!!";
	?>
	<br>
	<table width='1' cellpadding='0'>
		<tbody>
			<tr>
				<td valign='top'>
					<a href ='../../graph.php?local_graph_id=<? echo $graphid; ?>&rra_id=all'>
						<img border='0' src='../../graph_image.php?local_graph_id=<? echo $graphid; ?>&rra_id=0&graph_start=<? echo $timespan["begin_now"];?>&graph_end=<? echo time(); ?>&graph_width=<? echo read_config_option('cycle_width'); ?>&graph_height=<? echo read_config_option('cycle_height').($legend=='false' ? '&graph_nolegend=true' : '');?>'>
					</a>
				</td>
				<td valign='top' style='padding: 3px;' class='noprint' width='10px'>
					<a href='./../../graph.php?action=zoom&local_graph_id=<? echo $graphid; ?>&rra_id=5&view_type='><img src='./../../images/graph_zoom.gif' border='0' alt='Zoom Graph' title='Zoom Graph' style='padding: 3px;'></a><br>
					<a href='./../../graph_xport.php?local_graph_id=<? echo $graphid; ?>&rra_id=5&view_type='><img src='./../../images/graph_query.png' border='0' alt='CSV Export' title='CSV Export' style='padding: 3px;'></a><br>
					<a href='./../../graph.php?action=properties&local_graph_id=<? echo $graphid; ?>&rra_id=5&view_type='><img src='./../../images/graph_properties.gif' border='0' alt='Graph Source/Properties' title='Graph Source/Properties' style='padding: 3px;'></a><br>
					<?php api_plugin_hook('graph_buttons', array('hook' => 'view', 'local_graph_id' =>$graphid, 'rra' => '5', 'view_type' => "")) ?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
} else {
	print "!!!".$nextgraphid."!!!".$prevgraphid."!!!".$graphid."!!!";
}

function get_next_graphid() {
	global $rrd_id, $graphid, $nextgraphid, $prevgraphid, $graph_tree, $cur_leaf_id;

	if (read_config_option("cycle_custom_graphs") == "on") {
		if (read_config_option("cycle_custom_graphs_type") == "1" && read_config_option("cycle_custom_graphs_list") != "") {
			$graphs   = explode(",", read_config_option("cycle_custom_graphs_list"));
			$graph_id = $_REQUEST["id"];

			if ($graph_id == -1) {
				if (isset($graphs[1])) {
					$nextgraphid = $graphs[1];
				}else{
					$nextgraphid = $graphs[0];
				}

				$prevgraphid = $graphs[count($graphs)-1];
				$graph_id    = $graphs[0];
			} else {
				if ($rrd_id) {
					$graph_id    = $rrd_id;
				}

				$where = array_search($_GET['id'], $graphs);
				if (count($graphs)-1 > $where) {
					$nextgraphid = $graphs[$where+1];
				}

				if (0<$where) {
					$prevgraphid = $graphs[$where-1];
				}
			}

			if (empty($nextgraphid)) {
				$nextgraphid = $graphs[0];
			}

			if (empty($prevgraphid)) {
				$prevgraphid = $graphs[count($graphs)-1];
			}

			$sql = "SELECT
				graph_local.id,
				graph_templates_graph.title_cache
				FROM graph_local
				INNER JOIN graph_templates_graph
				ON graph_local.id=graph_templates_graph.local_graph_id ". ((!empty($graph_id)) ? " WHERE graph_local.id=". $graph_id : "" );

			$row     = db_fetch_row($sql);
			$graphid = $row['id'];
			$title   = $row['title_cache'];
		} elseif (read_config_option("cycle_custom_graphs_type") != "1") {
			$graphs      = get_tree_graphs($graph_tree);
			$cur_leaf_id = $_REQUEST["id"];
			$prevgraphid = null;
			$nextgraphid = null;
			$leaf_found  = false;
			$first_leaf  = null;
			$leaf_name   = "";

			if (sizeof($graphs)) {
				foreach ($graphs as $leaf_id => $leaf_data) {
					if (is_null($first_leaf)) {
						$first_leaf = $leaf_id;
					}

					if ($cur_leaf_id == -1) {
						$cur_leaf_id = $leaf_id;
						$prevgraphid = $leaf_id;
						$leaf_found  = true;
					} elseif ($cur_leaf_id == $leaf_id) {
						$leaf_found  = true;
					} elseif ($leaf_found == true) {
						$nextgraphid = $leaf_id;
						break;
					} else {
						$prevgraphid = $leaf_id;
						continue;
					}

					if (isset($leaf_data['graph_data'])) {
						$graphid = $leaf_data['graph_data'];
						$title   = "Tree View";
					}
				}
			}

			if (is_null($nextgraphid)) {
				$nextgraphid = $first_leaf;
			}
		} else {
			$graph_data = get_tree_graphs($graph_tree);
			$graph_id   = $_REQUEST["id"];

			$graphs = array();
			$count  = 0;

			if (sizeof($graph_data)>0) {
				foreach($graph_data as $data) {
					foreach($data as $subdata) {
						if (is_array($subdata)) {
							foreach($subdata as $key=>$graph) {
									$graphs[$count]=$graph['graph_id'];
									$count = $count + 1;
							}
						}
					}
				}

				if ($graph_id == -1) {
					if (isset($graphs[1])) {
						$nextgraphid = $graphs[1];
					}else{
						$nextgraphid = $graphs[0];
					}

					$prevgraphid = $graphs[count($graphs)-1];
					$graph_id    = $graphs[0];
				} else {
					$where = array_search($_GET['id'], $graphs);

					if (count($graphs)-1 > $where) {
						$nextgraphid = $graphs[$where+1];
					}

					if (0 < $where) {
						$prevgraphid = $graphs[$where-1];
					}
				}

				if (empty($nextgraphid)) {
					$nextgraphid = $graphs[0];
				}

				if (empty($prevgraphid)) {
					$prevgraphid = $graphs[count($graphs)-1];
				}
			} else {
				$_SESSION["sess_cycle_tree_id"] = read_config_option("cycle_custom_graphs_tree");
			}

			if ($rrd_id) {
				$graph_id    = $rrd_id;
			}

			$graphid = $graph_id;
		}
	} else {
		if (isset($_REQUEST["id"])) {
			$graph_id = $_REQUEST["id"];
		}else{
			$graph_id = -1;
		}

		if ($graph_id < 0) $graph_id = 0;

		if ($rrd_id) {
			$graph_id    = $rrd_id;
		}

		$sql = "SELECT
			graph_local.id,
			graph_templates_graph.title_cache
			FROM graph_local
			INNER JOIN graph_templates_graph
			ON graph_local.id=graph_templates_graph.local_graph_id ".((!empty($graph_id)) ? " WHERE graph_local.id>=". $graph_id : "" )." ORDER BY graph_local.id ASC";

		$rows = db_fetch_assoc($sql);

		$graphid     = 0;
		$nextgraphid = 0;
		$prevgraphid = 0;
		$title       = "";
		$curr_found  = false;
		$next_found  = false;

		if (sizeof($rows)) {
			foreach($rows as $row) {
				if (is_graph_allowed($row["id"])) {
					if (!$curr_found) {
						$graphid = $row['id'];
						$title   = $row['title_cache'];

						$curr_found = true;
					}else{
						$nextgraphid = $row['id'];

						$next_found  = true;
						break;
					}
				}
			}
		}

		$sql    = "SELECT id FROM graph_local " . ((!empty($graph_id)) ? " WHERE id<" . $graph_id : "" ) . " ORDER BY id DESC";
		$rows   = db_fetch_assoc($sql);

		if (sizeof($rows)) {
			foreach($rows as $row) {
				if (is_graph_allowed($row['id'])) {
					$prevgraphid = $row['id'];
					break;
				}
			}
		}
	}
}

function get_tree_graphs($treeid) {
	$sql = "SELECT *
		FROM graph_tree_items
		WHERE graph_tree_items.graph_tree_id=$treeid
		ORDER BY graph_tree_items.order_key";

	$rows     = db_fetch_assoc($sql);
	$outArray = array();

	if (count($rows)) {
		$title_id = null;
		foreach ($rows as $row) {
			if (((!empty($row['title'])) && ($row['host_id'] == 0)) && ($row['local_graph_id'] == 0)) {
//				$outArray[$row['id']]['title'] = $row['title'];
//				$title_id = $row['id'];
			} elseif ((empty($row['title'])) && ($row['local_graph_id'] > 0 )) {
				$outArray[$title_id]['graph_data'][] = array( 'graph_id' => $row['local_graph_id'], 'graph_title' => get_graph_title($row['local_graph_id']));
			}
		}
	}

	return($outArray);
}
