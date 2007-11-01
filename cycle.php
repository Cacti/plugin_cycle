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
include_once("./include/auth.php");

$_SESSION['custom'] = false;
include_once("./include/top_graph_header.php");

?>
<script src="cycle.js"></script>
<body onload="rtime=<? echo read_config_option("cycle_delay")*1000; ?>;parent.startTime();parent.refreshTime();parent.getnext();">
<p>
<center>
<span id="title" style="font-size:<? echo read_config_option("cycle_font_size"); ?>px;font-family:<? echo read_config_option("cycle_font_face"); ?>;font-weight:bold;color:<? echo read_config_option("cycle_font_color"); ?>;"></span><br>
| <a href="#" onclick="parent.getprev();">&lt; Prev</a> |
<a id="cstop" href="#" onclick="stopTime()">Stop</a>
<a id="cstart" style="display:none;" href="#" onclick="startTime()">Start</a>
 | <a href="#" onclick="parent.getnext();"> Next &gt;</a> |
<br>
Next Update In <span id="countdown"></span><br><br>
<span id="image"></span><br>
</center>
</body></html>