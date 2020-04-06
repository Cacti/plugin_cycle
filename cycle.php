<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2007-2020 The Cacti Group                                 |
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

$guest_account = true;

chdir('../../');

include_once('./include/auth.php');
include_once('./lib/time.php');
include_once('./lib/html_tree.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_tree.php');
include_once('./lib/utility.php');
include_once('./lib/api_data_source.php');
include_once('./plugins/cycle/functions.php');

set_default_action();

cycle_set_defaults();

validate_request_vars();

switch(get_request_var('action')) {
case 'save':
	save_settings();

	break;
case 'graphs':
	cycle_graphs();

	break;
default:
	cycle();

	break;
}

function cycle_graphs() {
	global $graphs_ppage, $graph_cols, $graphs;
	global $page_refresh_interval, $graph_timespans;
	global $cycle_width, $cycle_height;
	global $id, $graph_id, $next_graph_id, $prev_graph_id;

	$tree_list = get_allowed_trees();
	$legend    = get_request_var('legend');
	$tree_id   = get_request_var('tree_id');
	$leaf_id   = get_request_var('leaf_id');
	$graphpp   = get_request_var('graphs');
	$cols      = get_request_var('cols');
	$rfilter   = get_request_var('rfilter');
	$id        = get_request_var('id');
	$width     = get_request_var('width');
	$height    = get_request_var('height');

	if (empty($tree_id)) $tree_id = db_fetch_cell('SELECT id FROM graph_tree ORDER BY name LIMIT 1');
	if (empty($id))      $id      = -1;

	/* get the start and end times for the graph */
	$timespan        = array();
	$first_weekdayid = read_user_setting('first_weekdayid');
	get_timespan($timespan, time(), get_request_var('timespan'), $first_weekdayid);

	$graph_tree = $tree_id;
	$html       = '';
	$out        = '';

	get_next_graphid($graphpp, $rfilter, $graph_tree, $leaf_id);

	/* create the graph structure and output */
	$out       = '<table style="margin-left:auto;margin-right:auto;">';
	$max_cols  = $cols;
	$col_count = 1;

	if ($graphs !== null && $graphs !== false && sizeof($graphs)) {
		foreach($graphs as $graph) {
			if ($col_count == 1)
				$out .= '<tr>';

			$out .= '<td align="center" class="graphholder">'
				. "<a href='../../graph.php?local_graph_id=" . $graph['graph_id'] . "&rra_id=all'>"
				. "<img class='cycle_image' "
				. "src='../../graph_image.php?image_format=png&disable_cache=true&local_graph_id="
				. $graph['graph_id'] . "&rra_id=0&graph_start=" . $timespan['begin_now']
				. "&graph_end=" . time() . "&graph_width=" . $width . "&graph_height=" . $height
				. ($legend == '' || $legend=='false' ? "&graph_nolegend=true" : "") . "'>"
				. "</a></td>";

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

	$output = array('graphid' => $graph_id, 'nextgraphid' => $next_graph_id, 'prevgraphid' => $prev_graph_id, 'image' => base64_encode($out));

	print json_encode($output);
}

function cycle() {
	global $graphs_ppage, $graph_cols;
	global $page_refresh_interval, $graph_timespans;
	global $cycle_width, $cycle_height;

	general_header();

	global $config;

	if (function_exists('get_md5_include_js')) {
		print get_md5_include_js('plugin/cycle/cycle.js');
	} else {
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/cycle/cycle.js'></script>\n";
	}

	$tree_list = get_allowed_trees();
	$legend    = get_request_var('legend');
	$tree_id   = get_request_var('tree_id');
	$leaf_id   = get_request_var('leaf_id');
	$graphpp   = get_request_var('graphs');
	$cols      = get_request_var('cols');
	$rfilter   = get_request_var('rfilter');
	$id        = get_request_var('id');
	$width     = get_request_var('width');
	$height    = get_request_var('height');

	if (empty($tree_id)) {
		$tree_id = db_fetch_cell('SELECT id 
			FROM graph_tree 
			ORDER BY name 
			LIMIT 1');
	}

	if (empty($id)) {
		$id      = -1;
	}

	/* get the start and end times for the graph */
	$timespan        = array();
	$first_weekdayid = read_user_setting('first_weekdayid');
	get_timespan($timespan, time(), get_request_var('timespan') , $first_weekdayid);

	$graph_tree = $tree_id;
	$html       = '';
	$out        = '';

	html_start_box(__('Cycle Graph Filter', 'cycle') . ' [ ' . __('Next Update In', 'cycle') . " <i id='countdown'></i> ]", '100%', '', 3, 'center', '');
	?>
	<tr class='odd'><td>
		<table class='filterTable'>
			<tr>
				<td>
					<script type='text/javascript'>
						var rtime=<?php echo get_request_var('delay')*1000;?>;
					</script>
					<select id='timespan' title='<?php print __esc('Graph Display Timespan', 'cycle');?>'>
						<?php
						if (sizeof($graph_timespans)) {
							foreach($graph_timespans as $key => $value) {
								print "<option value='$key'"; if (get_request_var('timespan') == $key) { print ' selected'; } print '>' . title_trim($value, 40) . "</option>\n";
							}
						}
						?>
					</select>
				</td>
				<td>
					<select id='delay' title='<?php print __esc('Cycle Rotation Refresh Frequency', 'cycle');?>'>
						<?php
						if (sizeof($page_refresh_interval)) {
							foreach($page_refresh_interval as $key => $value) {
								print "<option value='$key'"; if (get_request_var('delay') == $key) { print ' selected'; } print '>' . title_trim($value, 40) . "</option>\n";
							}
						}
						?>
					</select>
				</td>
				<td>
					<select id='graphs' title='<?php print __esc('Number of Graphs per Page', 'cycle');?>'>
						<?php
						foreach($graphs_ppage as $key => $value) {
							print "<option value='$key'"; if (get_request_var('graphs') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
						}
						?>
					</select>
				</td>
				<td>
					<span class='nowrap'>
						<input type='button' id='prev' value='<?php print __esc('Prev', 'cycle');?>' title='<?php print __esc('Cycle to Previous Graphs', 'cycle');?>'>
						<input type='button' id='cstop' value='<?php print __esc('Stop', 'cycle');?>' title='<?php print __esc('Stop Cycling', 'cycle');?>'>
						<input type='button' id='cstart' value='<?php print __esc('Start', 'cycle');?>' style='display:none;' title='<?php print __esc('Resume Cycling', 'cycle');?>'>
						<input type='button' id='next' value='<?php print __esc('Next', 'cycle');?>' title='<?php print __esc('Cycle to Next Graphs', 'cycle');?>'>
						<input type='button' id='refresh' value='<?php print __esc('Refresh', 'cycle');?>' title='<?php print __esc('Refresh Graphs Now', 'cycle');?>'>
						<input type='button' id='clear' value='<?php print __esc('Clear', 'cycle');?>' title='<?php print __esc('Clear Filter', 'cycle');?>'>
						<input type='button' id='save' value='<?php print __esc('Save', 'cycle');?>' title='<?php print __esc('Save Filter Settings', 'cycle');?>'>
						<i id='text'></i>
					</span>
				</td>
			</table>
			<table class='filterTable'>
				<td>
					<select id='cols' title='<?php print __esc('Number of Graph Columns', 'cycle');?>'>
						<?php
						foreach($graph_cols as $key=>$value) {
							print "<option value='$key'"; if (get_request_var('cols') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
						}
						?>
					</select>
				</td>
				<td>
					<select id='height' title='<?php print __esc('Graph Height', 'cycle');?>'>
						<?php
						foreach($cycle_height as $key=>$value) {
							print "<option value='$key'"; if (get_request_var('height') == $key) { print ' selected'; } print '>' . $key . "</option>\n";
						}
						?>
					</select>
				</td>
				<td>
					<span style='vertical-align:center;'>X</span>
				</td>
				<td>
					<select id='width' title='<?php print __esc('Graph Width', 'cycle');?>'>
						<?php
						foreach($cycle_width as $key=>$value) {
							print "<option value='$key'"; if (get_request_var('width') == $key) { print ' selected'; } print '>' . $key . "</option>\n";
						}
						?>
					</select>
				</td>
				<td>
					<input type='checkbox' id='legend' <?php (get_request_var('legend') == 'true' || get_request_var('legend') == 'on' ? print " checked='checked'" : '' );?> title='<?php print __esc('Display Graph Legend', 'cycle');?>'>
				</td>
				<td>
					<label for='legend' style='vertical-align:25%' title='<?php print __esc('Display Graph Legend', 'cycle');?>'><?php print __esc('Legend', 'cycle');?> </label>
				</td>
			</tr>
		</table>
		<table class='filterTable'>
			<tr>
				<?php
				switch(read_config_option('cycle_custom_graphs_type')) {
				case '0':
				case '1':
					/* will only use the rfilter for full rotation */

					break;
				case '2':
					if (sizeof($tree_list)) {
						$html ="<td><select id='tree_id' title='" . __esc('Select Tree to View', 'cycle') . "'>\n";

						foreach ($tree_list as $tree) {
							$html .= "<option value='" . $tree['id'] . "'" . ($graph_tree == $tree['id'] ? ' selected' : '') . '>' . title_trim($tree['name'], 30)."</option>\n";
						}

						$html .= "</select>\n";

						$leaves = db_fetch_assoc_prepared('SELECT * 
							FROM graph_tree_items 
							WHERE title != "" 
							AND graph_tree_id = ?
							ORDER BY parent, position', 
							array($graph_tree));

						if (sizeof($leaves)) {
							$html .= "<select id='leaf_id' title='" . __esc('Select Tree Leaf to Display', 'cycle') . "'>\n";

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

				/* process the rfilter section */
				$html .= "<td><input id='rfilter' type='textbox' title='" . __esc('Enter Regular Expression Match (only alpha, numeric, and special characters \"(^_|?)\" permitted)', 'cycle') . "' size='30' value='" . $rfilter . "'></td>";

				print $html;
				?>
			</tr>
		</table>
		<table class='filterTable'>
			<tr id='izone'>
				<td>
				</td>
			</tr>
		</table>
	</td></tr>
	<?php html_end_box();?>
	<?php html_start_box(__('Cycle Graphs', 'cycle'), '100%', '', '3', 'center', '');?>
	<tr>
		<td>
			<span style='text-align:center;' id='image'></span>
		</td>
	</tr>
	<tr>
		<td>
		<script type='text/javascript'>
		$(function() {
			$('#timespan').change(function(){
				newTimespan()
			});

			$('#prev').click(function(){
				getPrev()
			});

			$('#next').click(function(){
				getNext()
			});

			$('#cstop').click(function(){
				stopTime()
			});

			$('#cstart').click(function(){
				startTime()
			});

			$('#clear').click(function(){
				clearFilter()
			});

			$('#save').click(function(){
				saveFilter()
			});

			$('#tree_id, #leaf_id, #height, #width, #cols, #graphs, #delay').change(function() {
				applyFilter();
			});

			$('#refresh, #legend').click(function() {
				applyFilter();
			});

			$('input, label, button').tooltip();

			stopTime();
			startTime();
			newGraph();
		});
		</script>
		</td>
	</tr>
	<?php 

	html_end_box();

	bottom_footer();
}

