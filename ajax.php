<?php
/*******************************************************************************

    Author ......... Matt Emerick-Law
    Contact ........ matt@emericklaw.co.uk
    Home Site ...... http://emericklaw.co.uk
    Program ........ Cycle Graphs
    Version ........ 0.6
    Purpose ........ Automatically cycle through cacti graphs

*******************************************************************************/

chdir('../../');

if (file_exists("./include/global.php")) {
	include_once("./include/global.php");
}else{
	include_once("./include/config.php");
}

$_SESSION['custom'] = false;

if (read_config_option("cycle_custom_graphs") == "on") {
	if (read_config_option("cycle_custom_graphs_type") === 1) {
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
		$leaf_found  = 0; // 0 = Cur Leaf Not found -> 1 = Cur Leaf Found
		$first_leaf  = null;

		foreach ( $graphs as $leaf_id => $leaf_data ) {
			if (is_null($first_leaf)) {
				$first_leaf = $leaf_id;
			}

			if ($cur_leaf_id == -1 ) {
				$cur_leaf_id = $leaf_id;
				$prevgraphid = $leaf_id;
				$leaf_found = 1;
			} elseif ( $cur_leaf_id == $leaf_id ) {
				$leaf_found = 1;
			} elseif ( $leaf_found == 1 ) {
				$nextgraphid = $leaf_id;
				break;
			} else {
				$prevgraphid = $leaf_id;
				continue;
			}

			$title = $leaf_data['title'];
			$graphid = $leaf_data['graph_data'];
		}

		if (is_null($nextgraphid))
			$nextgraphid = $first_leaf;
		
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

echo $title."!!!".$nextgraphid."!!!".$prevgraphid."!!!";

if (is_array($graphid)) {
	$out       = null;
	$out       = '<table cellpadding="0" cellspacing="0" border="0">';
	$max_cols  = read_config_option('cycle_columns');
	$col_count = 1;

	for ($x=0; $x < count($graphid); $x++) {
		if ($col_count == 1)
			$out .= '<tr>';
			
		$out .= '<td align="center" class="graphholder">'
			.'<img src="../../graph_image.php?local_graph_id='.$graphid[$x]['graph_id'].'&rra_id=0&graph_start='.(time() - 86400)
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

	print $out;
} else {
?>
<img src="../../graph_image.php?local_graph_id=<?php echo $graphid; ?>&rra_id=0&graph_start=<?php echo time() - 86400; ?>&graph_end=<?php echo time(); ?>&graph_width=<?php echo read_config_option('cycle_width'); ?>&graph_height=<?php echo read_config_option('cycle_height'); ?>&graph_nolegend=true">
<?php	
}

function get_tree_graphs($treeid) {
	$sql      = "SELECT
				graph_tree_items.id as id,
				graph_tree_items.title as title,
				graph_tree_items.order_key as order_key,
				graph_tree_items.host_id as host_id,
				graph_tree_items.local_graph_id as local_graph_id
				FROM graph_tree_items
				WHERE graph_tree_items.graph_tree_id=$treeid 
				ORDER BY graph_tree_items.order_key";

	$rows     = db_fetch_assoc($sql);
	$outArray = array();

	if (count($rows)) {
		$title_id = null;
		foreach ( $rows as $row ) {
			if ( !empty($row['title']) && $row['host_id'] == 0 && $row['local_graph_id'] == 0 ) {
				$outArray[$row['id']]['title'] = $row['title'];
				$title_id = $row['id'];
			} elseif ( empty($row['title']) && $row['local_graph_id'] > 0 ) {
				$outArray[$title_id]['graph_data'][] = array( 'graph_id' => $row['local_graph_id'], 'graph_title' => get_graph_title($row['local_graph_id']));
			}
		}
	}

	return($outArray);
}
