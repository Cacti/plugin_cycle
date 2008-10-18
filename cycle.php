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
include_once("./include/auth.php");

$_SESSION['custom'] = false;
include_once("./include/top_graph_header.php");

?>
<script src="cycle.js"></script>
<style type="text/css">
#title {
	font-size:<?php echo read_config_option("cycle_font_size"); ?>px;
	font-family:<?php echo read_config_option("cycle_font_face"); ?>;
	font-weight:bold;color:<?php echo read_config_option("cycle_font_color"); ?>;
}

.graphholder {

}
</style>
<body onload="rtime=<?php echo read_config_option("cycle_delay")*1000; ?>;startTime();refreshTime();getnext();">
<p>
<center>
<span id="title"></span><br>
<!-- Prev - Stop - Next links -->
| <a href="#" onclick="getprev();">&lt; Prev</a> |
<a id="cstop" href="#" onclick="stopTime()">Stop</a>
<a id="cstart" style="display:none;" href="#" onclick="startTime()">Start</a>
 | <a href="#" onclick="getnext();"> Next &gt;</a> |
<br>
<!-- Ticker -->
Next Update In <span id="countdown"></span><br><br>
<!-- Image -->
<span id="image"></span><br>
</center>
</body></html>