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

$_SESSION['custom'] = false;

if (read_config_option("cycle_custom_graphs") == "on") {
	if (read_config_option("cycle_custom_graphs_type") == "1") {
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
	} else {
		$graph_tree  = read_config_option("cycle_custom_graphs_tree");
		$graphs      = get_tree_graphs($graph_tree);
		$cur_leaf_id = $_REQUEST["id"];
		$prevgraphid = null;
		$nextgraphid = null;
		$leaf_found  = false;
		$first_leaf  = null;
		$leaf_name   = "";

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

			$graphid = $leaf_data['graph_data'];
			$title   = $leaf_data['title'];
		}

		if (is_null($nextgraphid)) {
			$nextgraphid = $first_leaf;
		}
	}
} else {
	if (isset($_REQUEST["id"])) {
		$graph_id = $_REQUEST["id"];
	}else{
		$graph_id = -1;
	}

	if ($graph_id < 0) $graph_id = 0;

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
			.'<img src="../../graph_image.php?local_graph_id='.$graphid[$x]['graph_id'].'&rra_id=0&graph_start='.$timespan["begin_now"]
			.'&graph_end='.time().'&graph_width='.read_config_option('cycle_width').'&graph_height='.read_config_option('cycle_height').'&graph_nolegend=true">'
			.'</td>';

		if ($col_count == $max_cols) {
			$out .= '</tr>';
			$col_count=1;
		} else {
			$col_count++;
		}
	}

	if($col_count  <= $max_cols) {
		$col_count--;
		$addcols = $max_cols - $col_count;

		for($x=1; $x <= $addcols; $x++) {
			$out .= '<td class="graphholder">&nbsp;</td>';
		}

		$out .= '</tr>';
	}

	$out .= '</table>';

	print "Leaf Title: ".$title."!!!".$nextgraphid."!!!".$prevgraphid."!!!".$cur_leaf_id."!!!";
	print $out;
} else {
	echo $title."!!!".$nextgraphid."!!!".$prevgraphid."!!!".$graphid."!!!";
	?>
	<img src="../../graph_image.php?local_graph_id=<?php echo $graphid; ?>&rra_id=0&graph_start=<?php echo time() - 86400; ?>&graph_end=<?php echo time(); ?>&graph_width=<?php echo read_config_option('cycle_width'); ?>&graph_height=<?php echo read_config_option('cycle_height'); ?>&graph_nolegend=true">
	<?php
}

function get_tree_graphs($treeid) {
	$sql = "SELECT *
		FROM graph_tree_items
		WHERE graph_tree_items.graph_tree_id=$treeid
		ORDER BY graph_tree_items.order_key";

	$rows     = db_fetch_assoc($sql);
	$outArray = array();

	if (sizeof($rows)) {
	foreach ($rows as $row) {
		if ($row['title'] != "" && $row['host_id'] == 0) {
			$leaf_title = $row['title'];
			$leaf_id    = $row['id'];
		} elseif (empty($row['title']) && $row['local_graph_id'] > 0) {
			if ($row['host_grouping_type'] == 0) {
				$outArray[$leaf_id]['title'] = "Main Tree";
			}else{
				$outArray[$leaf_id]['title'] = $leaf_title;
			}

			$outArray[$leaf_id]['id']         = $leaf_id;
			$outArray[$leaf_id]['graph_data'][] = array('title' => $leaf_title, 'graph_id' => $row['local_graph_id'], 'graph_title' => get_graph_title($row['local_graph_id']));
		}
	}
	}

	return($outArray);
}
