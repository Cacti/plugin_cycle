<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2017 The Cacti Group                                 |
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

$guest_user = true;

chdir('../../');

include_once('./include/global.php');
include_once('./lib/time.php');
include_once('./lib/html_tree.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_tree.php');
include_once('./lib/api_data_source.php');
include_once('./plugins/cycle/functions.php');

set_default_action();

validate_request_vars();

if (get_request_var('action') == 'save') {
	save_settings();
	exit;
}

$legend  = get_request_var('legend');
$tree_id = get_request_var('tree_id');
$leaf_id = get_request_var('leaf_id');
$graphpp = get_request_var('graphs');
$cols    = get_request_var('cols');
$filter  = get_request_var('filter');
$id      = get_request_var('id');
$width   = get_request_var('width');
$height  = get_request_var('height');

if (empty($tree_id)) $tree_id = db_fetch_cell('SELECT id FROM graph_tree ORDER BY name LIMIT 1');
if (empty($id))      $id      = -1;

/* get the start and end times for the graph */
$timespan        = array();
$first_weekdayid = read_user_setting('first_weekdayid');
get_timespan($timespan, time(), get_request_var('timespan') , $first_weekdayid);

$graph_tree = $tree_id;
$html       = '';
$out        = '';

/* detect the next graph regardless of type */
get_next_graphid($graphpp, $filter, $graph_tree, $leaf_id);

switch(read_config_option('cycle_custom_graphs_type')) {
case '0':
case '1':
	/* will only use the filter for full rotation */

	break;
case '2':
	$tree_list = get_allowed_trees();

	if (sizeof($tree_list)) {
		$html ="<td><select id='tree_id' name='tree_id' onChange='newTree()' title='" . __esc('Select Tree to View', 'cycle') . "'>\n";

		foreach ($tree_list as $tree) {
			$html .= "<option value='" . $tree['id'] . "'" . ($graph_tree == $tree['id'] ? ' selected' : '') . '>' . title_trim($tree['name'], 30)."</option>\n";
		}

		$html .= "</select>\n";

		$leaves = db_fetch_assoc("SELECT * FROM graph_tree_items WHERE title!='' AND graph_tree_id='$graph_tree' ORDER BY parent, position");

		if (sizeof($leaves)) {
			$html .= "<select id='leaf_id' name='leaf_id' onChange='newTree()' title='" . __esc('Select Tree Leaf to Display', 'cycle') . "'>\n";

			$html .= "<option value='-1'" . ($leaf_id == -1 ? ' selected' : '') . ">" . __('All Levels', 'cycle') . "</option>\n";
			$html .= "<option value='-2'" . ($leaf_id == -2 ? ' selected' : '') . ">" . __('Top Level', 'cycle') . "</option>\n";

			foreach ($leaves as $leaf) {
				$html .= "<option value='" . $leaf['id'] . "'" . ($leaf_id == $leaf['id'] ? ' selected':'') . '>' . $leaf['title'] . "</option>\n";
			}

			$html .= "</select>\n";
		}else{
			$html .= "</td>";
		}
	}
}

/* process the filter section */
$html .= "<td><input id='filter' name='filter' type='textbox' title='" . __esc('Enter Regular Expression Match (only alpha, numeric, and special characters \"(^_|?)\" permitted)', 'cycle') . "' size='60' onkeypress='processReturn(event)' value='" . $filter . "'></td>";

$html .= "<td><input type='button' id='go' value='" . __esc('Set', 'cycle') . "' name='go' title='" . __esc('Set Filter', 'cycle') . "'></td><td><input type='button' id='clear' value='" . __esc('Clear', 'cycle') . "' name='clear' title='" . __esc('Clear Filter', 'cycle') . "' onClick='clearFilter()'></td>";

/* create the graph structure and output */
$out       = '<table cellpadding="5" cellspacing="5" border="0">';
$max_cols  = $cols;
$col_count = 1;

if (sizeof($graphs)) {
	foreach($graphs as $graph) {
		if ($col_count == 1)
			$out .= '<tr>';

		$out .= '<td align="center" class="graphholder">'
			. '<a href = ../../graph.php?local_graph_id='.$graph['graph_id'].'&rra_id=all>'
			. "<img "
			. "src='../../graph_image.php?image_format=png&disable_cache=true&local_graph_id=" . $graph['graph_id'] . "&rra_id=0&graph_start=" . $timespan['begin_now']
			. '&graph_end=' . time() . '&graph_width=' . $width . '&graph_height=' . $height . ($legend == '' || $legend=='false' ? '&graph_nolegend=true' : '')."'>"
			. '</a></td>';

		ob_start();
		$out .= ob_get_clean();

		if ($col_count == $max_cols) {
			$out .= '</tr>';
			$col_count=1;
		} else {
			$col_count++;
		}
	}
}else{
	$out = '<h1>' . __('No Graphs Found Matching Criteria', 'cycle') . '</h1>';
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
$out .= '<script type="text/javascript">$(function() { applySkin(); $("input, label, button").tooltip(); });</script>';

$output = array('html' => $html, 'graphid' => $graph_id, 'nextgraphid' => $next_graph_id, 'prevgraphid' => $prev_graph_id, 'image' => base64_encode($out));
print json_encode($output);

exit;

