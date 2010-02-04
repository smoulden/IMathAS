<?php
//IMathAS:  Checks user's login - prompts if none. 
//(c) 2006 David Lippman
 header('P3P: CP="ALL CUR ADM OUR"');
 $curdir = rtrim(dirname(__FILE__), '/\\');
 if (!file_exists("$curdir/config.php")) {
	 header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/install.php");
 }
 require_once("$curdir/config.php");
 if (isset($sessionpath) && $sessionpath!='') { session_save_path($sessionpath);}
 ini_set('session.gc_maxlifetime',86400);
 ini_set('auto_detect_line_endings',true);
 session_start();
 $sessionid = session_id();
 
 $myrights = 0;
 $ispublic = false;
 //domain checks for special themes, etc. if desired
 $requestaddress = $_SERVER['HTTP_HOST'] .$_SERVER['PHP_SELF'];
 if (isset($CFG['CPS']['theme'])) {
 	 $defaultcoursetheme = $CFG['CPS']['theme'][0];
 } else if (!isset($defaultcoursetheme)) {
	 $defaultcoursetheme = "default.css";
 }
 $coursetheme = $defaultcoursetheme; //will be overwritten later if set
 
 
 //check for bad sessionids.  
 if (strlen($sessionid)<10) { 
	 if (function_exists('session_regenerate_id')) { session_regenerate_id(); }
	echo "Error.  Please <a href=\"$imasroot/index.php\">Home</a>try again</a>";
	exit;	 
 }
 $sessiondata = array();
 $query = "SELECT userid,tzoffset,sessiondata,time FROM imas_sessions WHERE sessionid='$sessionid'";
 $result = mysql_query($query) or die("Query failed : " . mysql_error());
 if (mysql_num_rows($result)>0) {
	 $userid = mysql_result($result,0,0);
	 $tzoffset = mysql_result($result,0,1);
	 $enc = mysql_result($result,0,2);
	 if ($enc!='0') {
		 $sessiondata = unserialize(base64_decode($enc));
		 //delete own session if old and not posting
		 if ((time()-mysql_result($result,0,3))>24*60*60 && (!isset($_POST) || count($_POST)==0)) {
			$query = "DELETE FROM imas_sessions WHERE userid='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			unset($userid);
		 }
	 } else {
		 if (isset($_SERVER['QUERY_STRING'])) {
			 $querys = '?'.$_SERVER['QUERY_STRING'];
		 } else {
			 $querys = '';
		 }
		 if (isset($_POST['skip']) || isset($_POST['isok'])) {
			 $sessiondata['mathdisp'] = $_POST['mathdisp'];
			 $sessiondata['graphdisp'] = $_POST['graphdisp'];
			 $sessiondata['useed'] = checkeditorok();
			 if (isset($_POST['savesettings'])) {
				 setcookie('mathgraphprefs',$_POST['mathdisp'].'-'.$_POST['graphdisp'],2000000000);
			 }
			 $enc = base64_encode(serialize($sessiondata));
			 $query = "UPDATE imas_sessions SET sessiondata='$enc' WHERE sessionid='$sessionid'";
			 mysql_query($query) or die("Query failed : " . mysql_error());
			 
			 header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . $querys);
			 exit;
		 } else {
			 require("header.php");
			 echo "<h2>Browser check</h2>\n";
			 echo "<p>For fastest, most accurate, and prettiest math and graph display, this system recommends:";
			 echo "<ul><li>Windows: Internet Explorer 6+ with MathPlayer and AdobeSVGViewer, or Firefox 1.5+</li>\n";
			 echo "<li>Mac: Firefox 1.5+ or Camino 1.0+</li></ul>\n";
			 echo "<form method=post action=\"{$_SERVER['PHP_SELF']}$querys\">\n";
			 echo "<div id=settings></div>";
			 echo <<<END
<script type="text/javascript"> 
	function preparenotices() {
		setnode = document.getElementById("settings");
		var html = "";
		html += '<h4>Math Display</h4>';
		if (AMnoMathML) {
			if (AMisGecko && AMnoTeX) {
				html += '<p><input type=hidden name="mathdisp" value="2">It appears you are using a Mozilla-based browser (FireFox, Camino, etc) ';
				html += 'but do not have the necessary math fonts installed.  Browser based Math display is faster and prettier than using image-based math display. ';
				html += 'To enable browser based Math display, <a href="http://www.mozilla.org/projects/mathml/fonts/">download math fonts</a></p>';
			} else {
				html += '<p><input type=hidden name="mathdisp" value="2">It appears you do not have browser-based Math display support.';
				html += 'Browser based Math display is faster and prettier than using image-based math display.  To install browser-based ';
				html += 'math display:</p>';
				html += '<p>Windows Internet Explorer users: <a href="http://www.dessci.com/en/dl/MathPlayerSetup.asp">Install MathPlayer plugin</a></p>';
				html += '<p>Mac users or non-IE windows users: <a href="http://www.mozilla.com/firefox/">Install Firefox 1.5+</a> or ';
				html += '<a href="http://www.caminobrowser.org/">Camino</a>.  With either of these browsers, if you find formulas not displaying ';
				html += 'correctly you may need to <a href="http://www.mozilla.org/projects/mathml/fonts/">install Math fonts</a></p>';
			}
		} else {
			html += '<p><input type=hidden name="mathdisp" value="1">Your browser is set up for browser-based math display.</p>';
		}
		html += '<h4>Graph Display</h4>';
		if (ASnoSVG) {
			html += '<p><input type=hidden name="graphdisp" value="2">It appears you do not have browser-based Graph display support. ';
			html += 'Browser based Graph display is faster and prettier than using image-based graph display.  To install browser-based ';
			html += 'graph display:</p>';
			html += '<p>Windows Internet Explorer users: <a href="http://download.adobe.com/pub/adobe/magic/svgviewer/win/3.x/3.03/en/SVGView.exe">Install AdobeSVGPlugin plugin</a></p>';
			html += '<p>Mac users or non-IE windows users: <a href="http://www.mozilla.com/firefox/">Install Firefox 1.5+</a> or <a href="http://www.caminobrowser.org/">Camino</a></p>';
		} else {
			html += '<p><input type=hidden name="graphdisp" value="1">Your browser is set up for browser-based graph display.</p>';
		}
		
		html += '<p><input type="checkbox" name="savesettings" checked="1"> Don\'t show me this screen again on this computer and browser.  If you update your browser, you can get back to this page by selecting Visual Display when you login.</p>';
		if (AMnoMathML || ASnoSVG) {
			html += '<p><input type=submit name=recheck value="Recheck Setup"><input type=submit name=skip value="Continue with image-based display"></p>';
		} else {
			html += '<p><input type=submit name=isok value="Browser setup OK - Continue"></p>';
		}
			
		setnode.innerHTML = html;
	}
	var existingonload = window.onload;
	if (existingonload) {
		window.onload = function() {existingonload(); preparenotices();}
	} else {
		window.onload = preparenotices;
	}
	
</script>
END;
			 echo "</form>\n";
			 require("footer.php");
			 exit;
		 }
	 }
			 
 }
 $hasusername = isset($userid);
 $haslogin = isset($_POST['password']);

 $verified = false; 
 if ($haslogin && !$hasusername) {
	  //clean up old sessions
	 $now = time();
	 $old = $now - 25*60*60;
	 $query = "DELETE FROM imas_sessions WHERE time<$old";
	 $result = mysql_query($query) or die("Query failed : " . mysql_error());
	 
	 $query = "SELECT id,password,rights,groupid FROM imas_users WHERE SID = '{$_POST['username']}'";
	 $result = mysql_query($query) or die("Query failed : " . mysql_error());
	 $line = mysql_fetch_array($result, MYSQL_ASSOC);
	 
	// if (($line != null) && ($line['password'] == md5($_POST['password']))) {
	 if (($line != null) && ((md5($line['password'].$_SESSION['challenge']) == $_POST['password']) ||($line['password'] == md5($_POST['password'])) )) {
		 unset($_SESSION['challenge']); //challenge is used up - forget it.
		 $userid = $line['id'];
		 $groupid = $line['groupid'];
		 //for upgrades times:
		// if ($line['rights']<100) {
		//	 echo "The system is currently down for maintenence.  Please try again later.";
		//	 exit;
		// }
		 //
		 if ($line['rights']==0) {
			require("header.php");
			echo "You have not yet confirmed your registration.  You must respond to the email ";
			echo "that was sent to you by IMathAS.";
			require("footer.php");
			exit;
		 }
		 
		 //$sessiondata['mathdisp'] = $_POST['mathdisp'];
		 //$sessiondata['graphdisp'] = $_POST['graphdisp'];
		 //$sessiondata['useed'] = $_POST['useed'];
		 if ($_POST['access']==1) { //text-based
			 $sessiondata['mathdisp'] = $_POST['mathdisp'];
			 $sessiondata['graphdisp'] = 0;
			 $sessiondata['useed'] = 0; 
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['access']==2) { //img graphs
			 $sessiondata['mathdisp'] = 2-$_POST['mathdisp'];
			 $sessiondata['graphdisp'] = 2;
			 $sessiondata['useed'] = checkeditorok(); 
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['access']==4) { //img math
			 $sessiondata['mathdisp'] = 2;
			 $sessiondata['graphdisp'] = $_POST['graphdisp'];
			 $sessiondata['useed'] = checkeditorok(); 
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['access']==3) { //img all
			 $sessiondata['mathdisp'] = 2;  
			 $sessiondata['graphdisp'] = 2;
			 $sessiondata['useed'] = checkeditorok(); 
			 $enc = base64_encode(serialize($sessiondata));
		 } else if ($_POST['isok']) {
			 $sessiondata['mathdisp'] = 1;  
			 $sessiondata['graphdisp'] = 1;
			 $sessiondata['useed'] = checkeditorok(); 
			 $enc = base64_encode(serialize($sessiondata));
		 } else {
			 $enc = 0; //give warning
		 }
		 
		 $query = "INSERT INTO imas_sessions (sessionid,userid,time,tzoffset,sessiondata) VALUES ('$sessionid','$userid',$now,'{$_POST['tzoffset']}','$enc')";
		 $result = mysql_query($query) or die("Query failed : " . mysql_error());
		 
		 $query = "UPDATE imas_users SET lastaccess=$now WHERE id=$userid";
		 $result = mysql_query($query) or die("Query failed : " . mysql_error());
		 
		 if (isset($_SERVER['QUERY_STRING'])) {
			 $querys = '?'.$_SERVER['QUERY_STRING'];
		 } else {
			 $querys = '';
		 }
		 header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . $querys);	 
	 } else {
		 if (empty($_SESSION['challenge'])) {
			 $badsession = true;
		 }
		 /*  For login error tracking - requires add'l table
		 if ($line==null) {
			 $err = "Bad SID";
		 } else if ($_SESSION['challenge']!=$_POST['challenge']) {
			 $err = "Bad Challenge (post:{$_POST['challenge']}, sess: ".addslashes($_SESSION['challenge']).")";	 
		 } else {
			 $err = "Bad PW";
		 }
		 $err .= ','.addslashes($_SERVER['HTTP_USER_AGENT']);
		 $query = "INSERT INTO imas_failures (SID,challenge,sent,type) VALUES ";
		 $query .= "('{$_POST['username']}','{$_SESSION['challenge']}','{$_POST['password']}','$err')";
		 mysql_query($query) or die("Query failed : " . mysql_error());
		 */
		 
	 }
	
 }
 if ($hasusername) {
	//check validity, if desired
	//$username = $_COOKIE['username'];
	$query = "SELECT SID,rights,groupid,LastName,FirstName,deflib FROM imas_users WHERE id='$userid'"; 
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$username = $line['SID'];
	$myrights = $line['rights'];
	$groupid = $line['groupid'];
	$userdeflib = $line['deflib'];
	$userfullname = $line['FirstName'] . ' ' . $line['LastName'];
	$previewshift = -1;
	$basephysicaldir = rtrim(dirname(__FILE__), '/\\');
	if (isset($sessiondata['isdiag']) && strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false) {
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php");
	}
	if (isset($sessiondata['ltiitemtype'])) {
		if ($sessiondata['ltiitemtype']==1) {
			if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false && isset($_GET['cid']) && $sessiondata['ltiitemid']!=$_GET['cid']) {
				echo "You do not have access to this page";
				echo "<a href=\"$imasroot/course/course.php?cid={$sessiondata['ltiitemid']}\">Return to course page</a>";
				exit;
			}
		} else if ($sessiondata['ltiitemtype']==0) {
			if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false && strpos(basename($_SERVER['PHP_SELF']),'printtest.php')===false) {
				$query = "SELECT courseid FROM imas_assessments WHERE id='{$sessiondata['ltiitemid']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$cid = mysql_result($result,0,0);
				header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}");
				exit;
			}
		}
		$breadcrumbbase = "";
	} else {
		$breadcrumbbase = "<a href=\"$imasroot/index.php\">Home</a> &gt; ";
	}
	if (isset($_GET['cid']) && $_GET['cid']!="admin" && $_GET['cid']>0) {
		$cid = $_GET['cid'];
		$query = "SELECT id,locked,timelimitmult,section FROM imas_students WHERE userid='$userid' AND courseid='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($line != null) {
			$studentid = $line['id'];
			$studentinfo['timelimitmult'] = $line['timelimitmult'];
			$studentinfo['section'] = $line['section'];
			if ($line['locked']==1) {
				require("header.php");
				echo "<p>You have been locked out of this course by your instructor.  Please see your instructor for more information.</p>";
				echo "<p><a href=\"$imasroot/index.php\">Home</a></p>";
				require("footer.php");
				exit;
			}
		} else {
			$query = "SELECT id FROM imas_teachers WHERE userid='$userid' AND courseid='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			if ($line != null) {
				if ($myrights>19) {
					$teacherid = $line['id'];
					if (isset($_GET['stuview'])) {
						$sessiondata['stuview'] = $_GET['stuview'];
						writesessiondata();
					}
					if (isset($_GET['teachview'])) {
						unset($sessiondata['stuview']);
						writesessiondata();
					}
					if (isset($sessiondata['stuview'])) {
						$previewshift = $sessiondata['stuview'];
						unset($teacherid);
						$studentid = $line['id'];
					}
				} else {
					$tutorid = $line['id'];
				}
			} else if ($myrights==100) {
				$teacherid = $userid;
				$adminasteacher = true;
			} else {
				
				$query = "SELECT id,section FROM imas_tutors WHERE userid='$userid' AND courseid='{$_GET['cid']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$line = mysql_fetch_array($result, MYSQL_ASSOC);
				if ($line != null) {
					$tutorid = $line['id'];
					$tutorsection = trim($line['section']);
				}
		
			}
		}
		$query = "SELECT imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_courses.copyrights,imas_users.groupid,imas_courses.theme,imas_courses.newflag,imas_courses.msgset,imas_courses.topbar ";
		$query .= "FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['cid']}' AND imas_users.id=imas_courses.ownerid";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			$crow = mysql_fetch_row($result);
			$coursename = $crow[0]; //mysql_result($result,0,0);
			$coursetheme = $crow[5]; //mysql_result($result,0,5);
			$coursenewflag = $crow[6]; //mysql_result($result,0,6);
			$coursemsgset = $crow[7]%5;
			$coursetopbar = explode('|',$crow[8]);
			$coursetopbar[0] = explode(',',$coursetopbar[0]);
			$coursetopbar[1] = explode(',',$coursetopbar[1]);
			if (!isset($coursetopbar[2])) { $coursetopbar[2] = 0;}
			if ($coursetopbar[0][0] == null) {unset($coursetopbar[0][0]);}
			if ($coursetopbar[1][0] == null) {unset($coursetopbar[1][0]);}
			if (isset($studentid) && $previewshift==-1 && (($crow[1])&1)==1) {
				echo "This course is not available at this time";
				exit;
			}
			$lockaid = $crow[2]; //ysql_result($result,0,2);
			if (isset($studentid) && $lockaid>0) {
				if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false) {
					header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid={$_GET['cid']}&id=$lockaid");
					exit;
				}
			}
			unset($lockaid);
			if ($myrights==75 && !isset($teacherid) && !isset($studentid) && $crow[4]==$groupid) {
				//group admin access
				$teacherid = $userid;
				$adminasteacher = true;
			} else if ($myrights>19 && !isset($teacherid) && !isset($studentid) && !isset($tutorid) && $previewshift==-1) {
				if ($crow[3]==2) {
					$guestid = $userid;
				} else if ($crow[3]==1 && $crow[4]==$groupid) {
					$guestid = $userid;
				}
			}
		}
	}
	$verified = true;
	
 }
 
 if (!$verified) {
	if (strpos(basename($_SERVER['SCRIPT_NAME']),'directaccess.php')===false) {
		if (!isset($loginpage)) {
			 $loginpage = "loginpage.php";
		}
		require($loginpage);
		exit;
	} 
 }
 
 function tzdate($string,$time) {
	  global $tzoffset;
	  //$dstoffset = date('I',time()) - date('I',$time);
	  //return gmdate($string, $time-60*($tzoffset+60*$dstoffset));	
	  $serveroffset = date('Z') + $tzoffset*60;
	  return date($string, $time-$serveroffset);
	  //return gmdate($string, $time-60*$tzoffset);
  }
  
  function writesessiondata() {
	  global $sessiondata,$sessionid;
	  $enc = base64_encode(serialize($sessiondata));
	  $query = "UPDATE imas_sessions SET sessiondata='$enc' WHERE sessionid='$sessionid'";
	  mysql_query($query) or die("Query failed : " . mysql_error());
  }
  function checkeditorok() {
	  $ua = $_SERVER['HTTP_USER_AGENT'];
	  if (strpos($ua,'iPhone')!==false) {
		  return 0;
	  } else {
		  return 1;
	  }
  }
  if (!isset($coursename)) {
	  $coursename = "Course Page";
  } 
 
?>
