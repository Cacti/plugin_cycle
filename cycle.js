var xmlHttp
var timerID = null
var image   = ""
var title   = ""
var next    = -1
var prev    = 1
var time    = 5
var ltime   = 0
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
	document.getElementById("cstop").style.display="inline"
	document.getElementById("cstart").style.display="none"
}

function stopTime() {
	self.clearInterval(timerID)
	document.getElementById("cstart").style.display="inline"
	document.getElementById("cstop").style.display="none"
}

function refreshTime() {
	ltime++
	document.getElementById("countdown").innerHTML=formattime(time)
	if (time == 0) {
		time=rtime/1000+1
		url="?id="+next
		getfromserver()
	}
	time=time-1
}

function getfromserver() {
	xmlHttp=GetXmlHttpObject()
	if (xmlHttp==null) {
		alert ("Get Firefox!")
		return
	}

	url="ajax.php"+url
	xmlHttp.onreadystatechange=stateChanged
	xmlHttp.open("GET",url,true)
	xmlHttp.send(null)
}

function getnext() {
	time=rtime/1000
	url="?id="+next
	getfromserver()
}

function getprev() {
	time=rtime/1000
	url="?id="+prev
	getfromserver()
}

function stateChanged() {
	if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete") {
		reply = xmlHttp.responseText
		reply = reply.split("!!!");
		image = reply[0]
		title = reply[1]
		next  = reply[2]
		prev  = reply[3]
		document.getElementById("image").innerHTML=image
		document.getElementById("title").innerHTML=title
	}
}

function GetXmlHttpObject() {
	var objXMLHttp=null
	if (window.XMLHttpRequest) {
		objXMLHttp=new XMLHttpRequest()
	}
	else if (window.ActiveXObject) {
		objXMLHttp=new ActiveXObject("Microsoft.XMLHTTP")
	}
	return objXMLHttp
}
