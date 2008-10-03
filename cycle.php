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