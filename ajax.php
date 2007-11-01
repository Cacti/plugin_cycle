<?php
/*******************************************************************************

    Author ......... Matt Emerick-Law
    Contact ........ matt@emericklaw.co.uk
    Home Site ...... http://emericklaw.co.uk
    Program ........ Cycle Graphs
    Version ........ 0.3
    Purpose ........ Automatically cycle through cacti graphs

*******************************************************************************/

chdir('../../');
include_once("./include/global.php");

$_SESSION['custom'] = false;

if (read_config_option("cycle_custom_graphs") == "on") {
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

	$row = db_fetch_row($sql);

	$graphid = $row['id'];
	$title   = $row['title_cache'];
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
?>
<img src="../../graph_image.php?local_graph_id=<?php echo $graphid; ?>&rra_id=0&graph_start=<?php echo time() - 86400; ?>&graph_end=<?php echo time(); ?>&graph_width=<?php echo read_config_option('cycle_width'); ?>&graph_height=<?php echo read_config_option('cycle_height'); ?>">
<?php
echo "!!!".$title."!!!".$nextgraphid."!!!".$prevgraphid;
?>