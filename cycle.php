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
$guest_account = true;
include_once("./include/auth.php");
include_once("./plugins/cycle/general_header.php");

if (!isset($_SESSION["sess_cycle_legend"])) {
	$_SESSION["sess_cycle_legend"] = read_config_option("cycle_legend");
}

if (!isset($_SESSION["sess_cycle_delay"])) {
	$_SESSION["sess_cycle_delay"] = read_config_option("cycle_delay");
}

if (!isset($_SESSION["sess_cycle_graphs_pp"])) {
	$_SESSION["sess_cycle_graphs_pp"] = read_config_option("cycle_graphs");
}

if (!isset($_SESSION["sess_cycle_graph_cols"])) {
	$_SESSION["sess_cycle_graph_cols"] = read_config_option("cycle_columns");
}

if (empty($_SESSION["sess_cycle_delay"])) {
	db_execute("REPLACE INTO settings SET name='cycle_delay', value='5'");
}

if (empty($_SESSION["sess_cycle_graphs_pp"])) {
	db_execute("REPLACE INTO settings SET name='cycle_graphs', value='4'");
}

$graphs_array = array(
    1  => "1 Graphs",
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

$legend = $_SESSION["sess_cycle_legend"];
?>
<center><!-- Timespan - Refresh - Prev - Stop - Next links -->
	<table>
		<tr>
			<td>
				<div id="outter">
					<div id="inner">
						<div style="margin:5px;">
							<select id='timespan' name='timespan'>
								<?php
								if (sizeof($graph_timespans)) {
								foreach($graph_timespans as $key=>$value) {
										print "<option value='$key'"; if (read_config_option("cycle_timespan") == $key) { print " selected"; } print ">" . title_trim($value, 40) . "</option>\n";
								}
								}
								?>
							</select>
							<select id='refresh' name='refresh'>
								<?php
								if (sizeof($page_refresh_interval)) {
								foreach($page_refresh_interval as $key=>$value) {
										print "<option value='$key'"; if ($_SESSION["sess_cycle_delay"] == $key) { print " selected"; } print ">" . title_trim($value, 40) . "</option>\n";
								}
								}
								?>
							</select>
							<select id='graphs' name='graphs'>
								<?php
								foreach($graphs_array as $key=>$value) {
										print "<option value='$key'"; if ($_SESSION["sess_cycle_graphs_pp"] == $key) { print " selected"; } print ">" . $value . "</option>\n";
								}
								?>
							</select>
							<select id='cols' name='cols'>
								<?php
								foreach($graph_cols as $key=>$value) {
										print "<option value='$key'"; if ($_SESSION["sess_cycle_graph_cols"] == $key) { print " selected"; } print ">" . $value . "</option>\n";
								}
								?>
							</select>
							<input type='button' id='prev' value='Prev' name='prev'>
							<input type='button' id='cstop' value='Stop' name='cstop'>
							<input type='button' id='cstart' value='Start' name='cstart' style='display:none;'>
							<input type='button' id='next' value='Next' name='next'>
							<input type="checkbox" id='legend' name='legend' <?php ($legend=="on" ? print ' checked=yes' : "" ); ?>>
							<label for='legend' style='vertical-align:25%'>Display Legend</label>
							<input type='button' id='refreshb' value='Refresh' name='refreshb'>
							<br>
						</div>
						<div style="margin:4px;">
							<span id="html"></span>
						</div>
					</div>
				</div>
			</td>
		</tr>
	</table>
	<!-- Ticker -->
	Next Update In <span id="countdown"></span><br>
	<!-- Image -->
	<span id="image"></span><br>
</center>
<script type="text/javascript">
	rtime=<?php echo read_config_option("cycle_delay")*1000;?>;
	$().ready(function() {
		startTime();
		refreshTime();
		getnext();
	});
	$('#timespan').change(function(){newTimespan()});
	$('#refresh').change(function(){newRefresh()});
	$('#graphs').change(function(){newRefresh()});
	$('#cols').change(function(){newRefresh()});
	$('#prev').click(function(){getprev()});
	$('#next').click(function(){getnext()});
	$('#cstop').click(function(){stopTime()});
	$('#cstart').click(function(){startTime()});
	$('#legend').change(function(){newRefresh()});
	$('#refreshb').click(function(){newRefresh()});
</script>
<?php
include("./include/bottom_footer.php");

