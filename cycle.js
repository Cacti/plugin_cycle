/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2007-2019 The Cacti Group                                 |
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
var image   = ''
var html    = ''
var nextid  = -1
var previd  = 1
var time    = 5
var ltime   = 0
var current = 0
var setfilter   = 0
var clearfilter = 0
var timed
var newtime
var stime
var url

var graph_width  = parseInt($(window).width() / $('#cols').val());
var graph_height = parseInt($('#height') * (graph_width / $('#width').val()));

$(window).resize(function() {
	resizeGraphs();
});

function calcage(secs, num1, num2) {
	return ((Math.floor(secs/num1))%num2).toString()
}

function formattime(secs) {
	days    = calcage(secs,86400,100000)
	hours   = calcage(secs,3600,24)
	minutes = calcage(secs,60,60)
	seconds = calcage(secs,1,60)
	newtime = ''

	if (days==1) { newtime=days+' Day ' }
	if (days>1)  { newtime=days+' Days ' }

	if (hours==1) { newtime=newtime+hours+' Hour ' }
	if (hours>1)  { newtime=newtime+hours+' Hours ' }

	if (minutes==1) { newtime=newtime+minutes+' Minute ' }
	if (minutes>1)  { newtime=newtime+minutes+' Minutes ' }

	if (seconds==1) { newtime=newtime+seconds+' Second ' }
	if (seconds>1)  { newtime=newtime+seconds+' Seconds ' }

	if (newtime=='') { return '0 Seconds' }

	return newtime
}

function startTime() {
	timerID = setInterval(refreshTime, 1000)
	$('#cstop').css('display', 'inline');
	$('#cstart').css('display', 'none');
}

function stopTime() {
	clearInterval(timerID)
	$('#cstop').css('display', 'none');
	$('#cstart').css('display', 'inline');
}

function resizeGraphs() {
	graph_width  = parseInt(($(window).width() - 60) / $('#cols').val());
	if (graph_width > $('#width').val()) {
		graph_width = $('#width').val();
	}
	graph_height = parseInt($('#height').val() * (graph_width / $('#width').val()));
	$('.cycle_image').css('width', graph_width).css('height', graph_height).css('padding', '3px');
}

function loadGraphs(id) {
	if ($('#tree_id').length) {
		tree=$('#tree_id').val();
	}else{
		tree='';
	}

	if ($('#leaf_id').length) {
		leaf=$('#leaf_id').val();
	}else{
		leaf='';
	}

	strURL = 'cycle.php?action=graphs' +
		'&id='       + id +
		'&rfilter='  + $('#rfilter').val() +
		'&cols='     + $('#cols').val() +
		'&timespan=' + $('#timespan').val() +
		'&graphs='   + $('#graphs').val() +
		'&tree_id='  + tree +
		'&leaf_id='  + leaf +
		'&legend='   + $('#legend').is(':checked') +
		'&width='    + $('#width').val() +
		'&height='   + $('#height').val() +
		'&delay='    + $('#delay').val();

	$.get(strURL, function(data) {
		data = $.parseJSON(data);

		if (data.image) {
			image = base64_decode(data.image);
		}

		if (data.graphid) {
			current = data.graphid;
		}

		if (data.nextgraphid) {
			nextid = data.nextgraphid;
		}

		if (data.prevgraphid) {
			previd = data.prevgraphid;
		}

		$('#image').html(image);

		resizeGraphs();
	});
}

function saveFilter() {
	if ($('#tree_id').length) {
		tree=$('#tree_id').val();
	}else{
		tree='';
	}

	if ($('#leaf_id').length) {
		leaf=$('#leaf_id').val();
	}else{
		leaf='';
	}

	url='cycle.php?action=save' +
		'&rfilter='  + $('#rfilter').val() +
		'&cols='     + $('#cols').val() +
		'&timespan=' + $('#timespan').val() +
		'&graphs='   + $('#graphs').val() +
		'&tree_id='  + tree +
		'&leaf_id='  + leaf +
		'&legend='   + $('#legend').is(':checked') +
		'&width='    + $('#width').val() +
		'&height='   + $('#height').val() +
		'&delay='    + $('#delay').val();

	$.get(url, function(data) {
		$('#text').show().text('Filter Settings Saved').fadeOut(2000);
	});
}

function refreshTime() {
	ltime++
	$('#countdown').html(formattime(time));

	if (time == 0)
		getNext();

	time=time-1
}

function newRefresh() {
	rtime=$('#delay').val() * 1000;
	time=rtime/1000;
	loadGraphs(current);
}

function newTimespan() {
	time=rtime/1000;
	loadGraphs(current);
}

function newGraph() {
	rtime=$('#delay').val() * 1000;
	time=rtime/1000;
	loadGraphs(current);
}

function getNext() {
	rtime=$('#delay').val() * 1000;
	time=rtime/1000;
	loadGraphs(nextid);
}

function getPrev() {
	rtime=$('#delay').val() * 1000;
	time=rtime/1000;
	loadGraphs(previd);
}

function clearFilter() {
	strURL = 'cycle.php?action=view&clear=true&header=false';
	loadPageNoHeader(strURL, function() {
		loadGraphs(current);
	});
}

function applyFilter() {
	if ($('#tree_id').length) {
		tree=$('#tree_id').val();
	}else{
		tree='';
	}

	if ($('#leaf_id').length) {
		leaf=$('#leaf_id').val();
	}else{
		leaf='';
	}

	strURL = 'cycle.php?action=view' +
		'&header=false' +
		'&id='       + nextid +
		'&rfilter='  + $('#rfilter').val() +
		'&cols='     + $('#cols').val() +
		'&timespan=' + $('#timespan').val() +
		'&graphs='   + $('#graphs').val() +
		'&tree_id='  + tree +
		'&leaf_id='  + leaf +
		'&legend='   + $('#legend').is(':checked') +
		'&width='    + $('#width').val() +
		'&height='   + $('#height').val() +
		'&delay='    + $('#delay').val();

	loadPageNoHeader(strURL);
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
	var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
	var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
		ac = 0,
		dec = '',
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
    // +   improved by: Norman 'zEh' Fuchs
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
