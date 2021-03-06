/*
* IMathAS equation entry hint popover
*/

var ehcurel = null;
var ehclosetimer = 0;
var ehddclosetimer = 0;
var ehddcur = null;

//show expanded eqn tip
function showeh(eln) {
	el = document.getElementById(eln);
	var eh = document.getElementById('eh');
	if (eln != ehcurel) {
		ehcurel = eln;
		p = findPos(el);
		eh.style.display = "block";
		//eh.style.left = p[0] + "px";
		//eh.style.top = (p[1]-eh.offsetHeight) + "px"; // + el.offsetHeight
		eh.style.left = p[0] + "px";
		eh.style.top = (p[1] + el.offsetHeight) + "px";
	} else {
		eh.style.display = "none";
		ehcurel = null;
	}
	unhideeh(0);
	el.focus();
}

function reshrinkeh(eln) {
	if (eln==ehcurel) {
		document.getElementById("ehdd").style.display = "block";
		document.getElementById('eh').style.display = "none";
		ehcurel = null;
		unhideeh(0);
	}
}

function unhideeh(t) {
	ehcancelclosetimer();
}
function hideeh(t) {
	if (ehcurel==null) {
		ehddclosetimer = window.setTimeout(function() {curehdd = null; document.getElementById("ehdd").style.display = "none";},250);
	} else {
		ehclosetimer = window.setTimeout(reallyhideeh,250);
	}
}
function reallyhideeh() {
	var eh = document.getElementById('eh');
	eh.style.display = "none";
	ehcurel = null;
}
function ehcancelclosetimer() {
	if (ehclosetimer) {
		window.clearTimeout(ehclosetimer);
		ehclosetimer = null;
	}
}
//show eqn tip dropdown (shorttipe)
function showehdd(eln,shorttip,qn) {
	//if new el, no need to timeout, since moving it
	if (ehddclosetimer && eln!=curehdd) {
		window.clearTimeout(ehddclosetimer);
		ehddclosetimer = null;
	}
	if (eln!=ehcurel) { //if new el, change position and content
		var ehdd = document.getElementById("ehdd");
		var el = document.getElementById(eln);
		p = findPos(el);
		document.getElementById("ehddtext").innerHTML = shorttip;
		document.getElementById("eh").innerHTML = document.getElementById("tips"+qn).innerHTML;
		ehdd.style.display = "block";
		//ehdd.style.left = p[0] + "px";
		//ehdd.style.top = (p[1] - ehdd.offsetHeight) + "px";
		ehdd.style.left = p[0] + "px";
		ehdd.style.top = (p[1] + el.offsetHeight) + "px";
		//ehdd.style.top = (p[1] + el.offsetHeight) + "px";
		//ehdd.style.width = el.offsetWidth + "px";
		
	}
	curehdd = eln;
}
