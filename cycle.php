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
$legend = $_SESSION["sess_cycle_legend"];
?>
<script src="cycle.js"></script>
<style type="text/css">
#title {
	font-size:<?php echo read_config_option("cycle_font_size"); ?>px;
	font-family:<?php echo read_config_option("cycle_font_face"); ?>;
	font-weight:bold;color:#<?php echo db_fetch_cell("SELECT hex FROM colors WHERE id='" . read_config_option("cycle_font_color") . "'"); ?>;
}

.graphholder {

}

#outter {background-color: #f5f5f5; border: 1px solid #bbbbbb;}
#inner {background-color: #ddd; border: 2px solid #f5f5f5;}

</style>
<body onload="rtime=<?php echo read_config_option("cycle_delay")*1000; ?>;startTime();refreshTime();getnext();">
	<center><!-- Timespan - Refresh - Prev - Stop - Next links -->
		<table>
			<tr>
				<td>
					<div id="outter">
						<div id="inner">
							<div style="margin:5px;">
								<select id='timespan' name='timespan' onChange="newTimespan()">
									<?php
									if (sizeof($graph_timespans)) {
									foreach($graph_timespans as $key=>$value) {
											print "<option value='$key'"; if (read_config_option("cycle_timespan") == $key) { print " selected"; } print ">" . title_trim($value, 40) . "</option>\n";
									}
									}
									?>
								</select>
								<select id='refresh' name='refresh' onChange="newRefresh()">
									<?php
									if (sizeof($page_refresh_interval)) {
									foreach($page_refresh_interval as $key=>$value) {
											print "<option value='$key'"; if ($_SESSION["sess_cycle_delay"] == $key) { print " selected"; } print ">" . title_trim($value, 40) . "</option>\n";
									}
									}
									?>
								</select>
								<input type='button' id='prev' value='Prev' name='prev' onClick='getprev()'>
								<input type='button' id='cstop' value='Stop' name='cstop' onClick='stopTime()'>
								<input type='button' id='cstart' value='Start' name='cstart' onClick='startTime()' style='display:none;'>
								<input type='button' id='next' value='Next' name='next' onClick='getnext()'>
								<input type="checkbox" id='legend' name='legend' onClick='newRefresh()' <?php ($legend=="on" ? print ' checked=yes' : "" ); ?> >Display Legend
								<input type='button' id='' value='Refresh' name='refreshing' onClick='newRefresh()'>
								<br>
							</div>
							<div style="margin:4px;">
								<span id="title"></span>
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
</body>
</html>