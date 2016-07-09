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

$guest_account = true;

chdir('../../');

include_once('./include/auth.php');
include_once('./plugins/cycle/functions.php');

set_default_action();

cycle_set_defaults();

validate_request_vars();

general_header();

$legend = get_request_var('cycle_legend');

?>
<center><!-- Timespan - Refresh - Prev - Stop - Next links -->
	<table>
		<tr>
			<td>
				<select id='timespan' name='timespan' title='<?php print __('Graph Display Timespan');?>'>
					<?php
					if (sizeof($graph_timespans)) {
					foreach($graph_timespans as $key=>$value) {
						print "<option value='$key'"; if (get_request_var('timespan') == $key) { print ' selected'; } print '>' . title_trim($value, 40) . "</option>\n";
					}
					}
					?>
				</select>
			</td>
			<td>
				<select id='delay' name='delay' title='<?php print __('Cycle Rotation Refresh Frequency');?>'>
					<?php
					if (sizeof($page_refresh_interval)) {
					foreach($page_refresh_interval as $key=>$value) {
						print "<option value='$key'"; if (get_request_var('delay') == $key) { print ' selected'; } print '>' . title_trim($value, 40) . "</option>\n";
					}
					}
					?>
				</select>
			</td>
			<td>
				<select id='graphs' name='graphs' title='<?php print __('Number of Graphs per Page');?>'>
					<?php
					foreach($graphs_array as $key=>$value) {
						print "<option value='$key'"; if (get_request_var('graphs') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
					}
					?>
				</select>
			</td>
			<td>
				<select id='cols' name='cols' title='<?php print __('Number of Graph Columns');?>'>
					<?php
					foreach($graph_cols as $key=>$value) {
						print "<option value='$key'"; if (get_request_var('cols') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
					}
					?>
				</select>
			</td>
			<td>
				<select id='height' name='height' title='<?php print __('Graph Height');?>'>
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
				<select id='width' name='width' title='<?php print __('Graph Width');?>'>
					<?php
					foreach($cycle_width as $key=>$value) {
						print "<option value='$key'"; if (get_request_var('width') == $key) { print ' selected'; } print '>' . $key . "</option>\n";
					}
					?>
				</select>
			</td>
			<td>
				<input type='button' id='prev' value='<?php print __('Prev');?>' name='prev' title='<?php print __('Cycle to Previous Graphs');?>'>
			</td>
			<td>
				<input type='button' id='cstop' value='<?php print __('Stop');?>' name='cstop' title='<?php print __('Stop Cycling');?>'>
			</td>
			<td>
				<input type='button' id='cstart' value='<?php print __('Start');?>' name='cstart' style='display:none;' title='<?php print __('Resume Cycling');?>'>
			</td>
			<td>
				<input type='button' id='next' value='<?php print __('Next');?>' name='next' title='<?php print __('Cycle to Next Graphs');?>'>
			</td>
			<td>
				<input type='checkbox' id='legend' name='legend' <?php ($legend=='on' || $legend==1 ? print ' checked=yes' : '' ); ?> title='<?php print __('Display Graph Legend');?>'>
			</td>
			<td>
				<label for='legend' style='vertical-align:25%' title='<?php print __('Display Graph Legend');?>'><?php print __('Legend');?></label>
			</td>
			<td>
				<input type='button' id='refreshb' value='<?php print __('Refresh');?>' name='refreshb' title='<?php print __('Refresh Graphs Now');?>'>
			</td>
			<td>
				<input type='button' id='savedb' value='<?php print __('Save');?>' name='savedb' title='<?php print __('Save Filter Settings');?>'>
			</td>
		</tr>
	</table>
	<table>
		<tr id='izone'>
		</tr>
	</table><span id='text'></span><br>
	<?php print __('Next Update In');?> <span id='countdown'></span><br><br>
	<span id='image'></span><br>
</center>
<script type='text/javascript'>
	rtime=<?php echo get_request_var('cycle_delay')*1000;?>;
	$(function() {
		startTime();
		refreshTime();
		getnext();

		$('#timespan').change(function(){newTimespan()});
		$('#delay').change(function(){newRefresh()});
		$('#graphs').change(function(){newRefresh()});
		$('#cols').change(function(){newRefresh()});
		$('#width').change(function(){newRefresh()});
		$('#height').change(function(){newRefresh()});
		$('#prev').click(function(){getprev()});
		$('#next').click(function(){getnext()});
		$('#cstop').click(function(){stopTime()});
		$('#cstart').click(function(){startTime()});
		$('#legend').change(function(){newRefresh()});
		$('#refreshb').click(function(){newRefresh()});
		$('#go').click(function(){setFilter()});
		$('#clear').click(function(){clearFilter()});
		$('#savedb').click(function(){saveFilter()});
		$('input, label, button').tooltip();
	});
</script>
<?php
bottom_footer();

