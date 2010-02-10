<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../assessment/displayq2.php");
	
 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Test Question";
$asid = 0;
 
	//CHECK PERMISSIONS AND SET FLAGS
if ($myrights<20) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {
	//data manipulation here
	$useeditor = 1;
	if (isset($_GET['seed'])) {
		$seed = $_GET['seed'];
		$attempt = 0;
	} else if (!isset($_POST['seed']) || isset($_POST['regen'])) {
		$seed = rand(0,10000);
		$attempt = 0;
	} else {
		$seed = $_POST['seed'];
		$attempt = $_POST['attempt']+1;
	}
	if (isset($_GET['onlychk']) && $_GET['onlychk']==1) {
		$onlychk = 1;
	} else {
		$onlychk = 0;
	}
	if (isset($_GET['formn']) && isset($_GET['loc'])) {
		$formn = $_GET['formn'];
		$loc = $_GET['loc'];
		if (isset($_GET['checked']) || isset($_GET['usecheck'])) {
			$chk = "&checked=0";
		} else {
			$chk = '';
		}
		if ($onlychk==1) {
		  $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','{$_GET['loc']}',true);";
		} else {
		  $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','{$_GET['loc']}');";	
		}
	}

	$lastanswers = array('');

	if (isset($_POST['seed'])) {
		$score = scoreq(0,$_GET['qsetid'],$_POST['seed'],$_POST['qn0']);
		$lastanswers[0] = stripslashes($lastanswers[0]);
		$page_scoreMsg =  "<p>Score on last answer: $score/1</p>\n";
	} else {
		$page_scoreMsg = "";
	}
	
	$page_formAction = "testquestion.php?cid={$_GET['cid']}&qsetid={$_GET['qsetid']}";
	if (isset($_POST['usecheck'])) {
		$page_formAction .=  "&checked=".$_GET['usecheck'];
	} else if (isset($_GET['checked'])) {
		$page_formAction .=  "&checked=".$_GET['checked'];
	}
	if (isset($_GET['formn'])) {
		$page_formAction .=  "&formn=".$_GET['formn'];
		$page_formAction .=  "&loc=".$_GET['loc'];
	}
	if (isset($_GET['onlychk'])) {
		$page_formAction .=  "&onlychk=".$_GET['onlychk'];
	}
	
	
	$query = "SELECT imas_users.email,imas_questionset.author,imas_questionset.description,imas_questionset.lastmoddate,imas_questionset.ancestors ";
	$query .= "FROM imas_users,imas_questionset WHERE imas_users.id=imas_questionset.ownerid AND imas_questionset.id='{$_GET['qsetid']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$email = mysql_result($result,0,0);
	$author = mysql_result($result,0,1);
	$descr = mysql_result($result,0,2);
	$lastmod = date("m/d/y g:i a",mysql_result($result,0,3));
	$ancestors = mysql_result($result,0,4);
	if (isset($CFG['AMS']['showtips'])) {
		$showtips = $CFG['AMS']['showtips'];
	} else {
		$showtips = 1;
	}
	
	$query = "SELECT imas_libraries.name FROM imas_libraries,imas_library_items WHERE imas_libraries.id=imas_library_items.libid AND imas_library_items.qsetid='{$_GET['qsetid']}'";
	$resultLibNames = mysql_query($query) or die("Query failed : " . mysql_error());
}

/******* begin html output ********/
$sessiondata['coursetheme'] = $coursetheme;
$flexwidth = true; //tells header to use non _fw stylesheet
if ($showtips==2) {
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqntips.js?v=012810\"></script>";
}
require("../assessment/header.php");

if ($overwriteBody==1) {
	echo $body;
} else { //DISPLAY BLOCK HERE
	$useeditor = 1;

	if (isset($_GET['formn']) && isset($_GET['loc'])) {
		echo '<p>';
		echo "<script type=\"text/javascript\">";
		echo "var numchked = -1;";
		echo "if (window.opener && !window.opener.closed) {";
		echo $page_onlyChkMsg;
		echo "	  if (prevnext[0][1]>0){
				  document.write('<a href=\"testquestion.php?cid={$_GET['cid']}$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[0][0]+'&qsetid='+prevnext[0][1]+'\">Prev</a> ');
			  } else {
				  document.write('Prev ');
			  }
			  if (prevnext[1][1]>0){
				  document.write('<a href=\"testquestion.php?cid={$_GET['cid']}$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[1][0]+'&qsetid='+prevnext[1][1]+'\">Next</a> ');
			  } else {
				  document.write('Next ');
			  }
			  if (prevnext[2]!=null) {
			  	document.write(' <span id=\"numchked\">'+prevnext[2]+'</span> checked');
				numchked = prevnext[2];
			  }
			  if (prevnext[3]!=null) {
			  	document.write(' '+prevnext[3]+' remaining');
			  }
			}
			</script>";
		echo '</p>';
	}

	if (isset($_GET['checked'])) {
		echo "<p><input type=\"checkbox\" name=\"usecheck\" id=\"usecheck\" value=\"Mark Question for Use\" onclick=\"parentcbox.checked=this.checked;togglechk(this.checked)\" ";
		echo "/> Mark Question for Use</p>";
		echo "
		  <script type=\"text/javascript\">
		  var parentcbox = opener.document.getElementById(\"{$_GET['loc']}\");
		  document.getElementById(\"usecheck\").checked = parentcbox.checked;
		  function togglechk(ischk) {
			  if (numchked!=-1) {
				if (ischk) {
					numchked++;	
				} else {
					numchked--;
				}
				document.getElementById(\"numchked\").innerHTML = numchked;
			  }
		  }
		  </script>";
	}

	echo $page_scoreMsg;
	echo '<script type="text/javascript"> function whiteout() { e=document.getElementsByTagName("div");';
	echo 'for (i=0;i<e.length;i++) { if (e[i].className=="question") {e[i].style.backgroundColor="#fff";}}}</script>';
	echo "<form method=post enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit()\">\n";
	echo "<input type=hidden name=seed value=\"$seed\">\n";
	echo "<input type=hidden name=attempt value=\"$attempt\">\n";

	displayq(0,$_GET['qsetid'],$seed,true,true,$attempt);
	echo "<input type=submit value=\"Submit\"><input type=submit name=\"regen\" value=\"Submit and Regen\">\n";
	echo "<input type=button value=\"White Background\" onClick=\"whiteout()\"/>";
	echo "</form>\n";
	
	echo "<p>Question id: {$_GET['qsetid']}.  <a href=\"mailto:$email\">E-mail owner</a> to report problems</p>";
	echo "<p>Description: $descr</p><p>Author: $author</p>";
	echo "<p>Last Modified: $lastmod</p>";

	echo '<p>Question is in these libraries:';
	echo '<ul>';
	while ($row = mysql_fetch_row($resultLibNames)) {
		echo '<li>'.$row[0].'</li>';
	}
	echo '</ul></p>';
	if ($ancestors!='') {
		echo "<p>Derived from: $ancestors</p>";
	}
}
require("../footer.php");
	
?>
	
