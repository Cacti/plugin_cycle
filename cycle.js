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

var timerID = null
var image   = ""
var html    = ""
var next    = -1
var prev    = 1
var time    = 5
var ltime   = 0
var current = 0
var setfilter   = 0
var clearfilter = 0
var timed
var newtime
var stime
var url

function calcage(secs, num1, num2) {
	return ((Math.floor(secs/num1))%num2).toString()
}

function formattime(secs) {
	days    = calcage(secs,86400,100000)
	hours   = calcage(secs,3600,24)
	minutes = calcage(secs,60,60)
	seconds = calcage(secs,1,60)
	newtime = ""
	if (days==1) { newtime=days+" Day " }
	if (days>1)  { newtime=days+" Days " }

	if (hours==1) { newtime=newtime+hours+" Hour " }
	if (hours>1)  { newtime=newtime+hours+" Hours " }

	if (minutes==1) { newtime=newtime+minutes+" Minute " }
	if (minutes>1)  { newtime=newtime+minutes+" Minutes " }

	if (seconds==1) { newtime=newtime+seconds+" Second " }
	if (seconds>1)  { newtime=newtime+seconds+" Seconds " }

	if (newtime=="") { return "0 Seconds" }
	return newtime
}

function startTime() {
	timerID = self.setInterval('refreshTime()', 1000)
	$('#cstop').css("display", "inline");
	$('#cstart').css("display", "none");
}

function stopTime() {
	self.clearInterval(timerID)
	$('#cstop').css("display", "none");
	$('#cstart').css("display", "inline");
}

function processAjax(url) {
	$.get("cycle_ajax.php"+url, function(data) {
		data = $.parseJSON(data);
		if (data.html)         html=data.html;	
		if (data.image)        image=base64_decode(data.image);	
		if (data.graphid)      current=data.graphid;
		if (data.nextgraphid)  next=data.nextgraphid;
		if (data.prevgraphid)  prev=data.prevgraphid;
		//alert("Cur Graph ID:"+current+", Next Graph ID:"+next+", Prev Graph ID:"+prev);
		$('#html').html(html);
		$('#image').html(image);
	});
}

function formatProcessUrl(nextid) {
	if (clearfilter == 1) {
		clearfilter=0;
		filter="";
		filter=filter + "&clear";
	} else if (setfilter == 1) {
		setfilter=0;

		if ($('#filter').val()) {
			filter=$('#filter').val();
			filter=filter + "&set";
		}else{
			filter="";
			filter=filter + "&clear";
		}
	} else if ($('#filter').val()) {
		filter=$('#filter').val();
	}else{
		filter="";
	}
	if ($('#tree_id').val()) {
		tree=$('#tree_id').val();
	}else{
		tree="";
	}
	if ($('#leaf_id').val()) {
		leaf=$('#leaf_id').val();
	}else{
		leaf="";
	}

	url="?id="+nextid+"&filter="+filter+"&cols="+$('#cols').val()+"&timespan="+$('#timespan').val()+"&graphs="+$('#graphs').val()+"&tree_id="+tree+"&leaf_id="+leaf+"&legend="+$('#legend:checked').length+"&width="+$('#width').val()+"&height="+$('#height').val()+"&refresh="+$('#refresh').val();

	processAjax(url);
}

function refreshTime() {
	ltime++
	$('#countdown').html(formattime(time));
	if (time == 0) {
		time=rtime/1000+1;
		formatProcessUrl(next);
	}
	time=time-1
}

function newRefresh() {
	rtime=$('#refresh').val() * 1000;
	time=rtime/1000;
	formatProcessUrl(current);
}

function newTimespan() {
	time=rtime/1000;
	formatProcessUrl(current);
}

function newGraph() {
	time=rtime/1000;
	formatProcessUrl(current);
}

function newTree() {
	time=rtime/1000;
	formatProcessUrl(current);
}

function getnext() {
	time=rtime/1000;
	formatProcessUrl(next);
}

function getprev() {
	time=rtime/1000;
	formatProcessUrl(prev);
}

function clearFilter() {
	clearfilter=1;

	if ($('#tree').val()) {
		newTree();
	}else{
		newRefresh();
	}
}

function setFilter() {
	setfilter=1;

	if ($('#tree').val()) {
		newTree();
	}else{
		newRefresh();
	}
}

function processReturn(event) {
	if (event.which == 13) {
		setfilter=1;
		if ($('#tree').val()) {
			newTree();
		}else{
			newRefresh();
		}
	}
}

function base64_decode(data) {
	// http://kevin.vanzonneveld.net
	// +   original by: Tyler Akins (http://rumkin.com)
	// +   improved by: Thunder.m
	// +      input by: Aman Gupta
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   bugfixed by: Onno Marsman
	// +   bugfixed by: Pellentesque Malesuada
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +      input by: Brett Zamir (http://brett-zamir.me)
	// +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// -    depends on: utf8_decode
	// *     example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
	// *     returns 1: 'Kevin van Zonneveld'
	// mozilla has this native
	// - but breaks in 2.0.0.12!
	//if (typeof this.window['btoa'] == 'function') {
	//    return btoa(data);
	//}
	var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
		ac = 0,
		dec = "",
		tmp_arr = [];

	if (!data) {
		return data;
	}

	data += '';

	do { // unpack four hexets into three octets using index points in b64
		h1 = b64.indexOf(data.charAt(i++));
		h2 = b64.indexOf(data.charAt(i++));
		h3 = b64.indexOf(data.charAt(i++));
		h4 = b64.indexOf(data.charAt(i++));

		bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

		o1 = bits >> 16 & 0xff;
		o2 = bits >> 8 & 0xff;
		o3 = bits & 0xff;

		if (h3 == 64) {
			tmp_arr[ac++] = String.fromCharCode(o1);
		} else if (h4 == 64) {
			tmp_arr[ac++] = String.fromCharCode(o1, o2);
		} else {
			tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
		}
	} while (i < data.length);

	dec = tmp_arr.join('');
	dec = this.utf8_decode(dec);

	return dec;
}

function utf8_decode (str_data) {
    // http://kevin.vanzonneveld.net
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // +      input by: Aman Gupta
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Norman "zEh" Fuchs
    // +   bugfixed by: hitwork
    // +   bugfixed by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: utf8_decode('Kevin van Zonneveld');
    // *     returns 1: 'Kevin van Zonneveld'
	var tmp_arr = [],
		i = 0,
		ac = 0,
		c1 = 0,
		c2 = 0,
		c3 = 0;

	str_data += '';

	while (i < str_data.length) {
		c1 = str_data.charCodeAt(i);
		if (c1 < 128) {
            tmp_arr[ac++] = String.fromCharCode(c1);
            i++;
		} else if (c1 > 191 && c1 < 224) {
			c2 = str_data.charCodeAt(i + 1);
			tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
			i += 2;
		} else {
			c2 = str_data.charCodeAt(i + 1);
			c3 = str_data.charCodeAt(i + 2);
			tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
			i += 3;
		}
	}

	return tmp_arr.join('');
}
