<?php
//IMathAS:  Frontend of testing engine - manages administration of assessments
//(c) 2006 David Lippman

	require("../validate.php");
	if (isset($guestid)) {
		$teacherid=$guestid;
	}
	if (!isset($sessiondata['sessiontestid']) && !isset($teacherid) && !isset($studentid)) {
		echo "<html><body>";

		echo "You are not authorized to view this page.  If you are trying to reaccess a test you've already ";
		echo "started, access it from the course page</body></html>\n";
		exit;
	}
	$actas = false;
	$isreview = false;
	if (isset($teacherid) && isset($_GET['actas'])) {
		$userid = $_GET['actas'];
		unset($teacherid);
		$actas = true;
	}
	include("displayq2.php");
	include("testutil.php");
	include("asidutil.php");
	$inexception = false;
	//error_reporting(0);  //prevents output of error messages
	
	//check to see if test starting test or returning to test
	if (isset($_GET['id'])) {
		//check dates, determine if review
		$aid = $_GET['id'];
		$isreview = false;
		
		$query = "SELECT deffeedback,startdate,enddate,reviewdate,shuffle,itemorder,password,avail FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$adata = mysql_fetch_array($result, MYSQL_ASSOC);
		$now = time();
		
		if ($adata['avail']==0 && !isset($teacherid)) {
			echo "Assessment is closed";
			exit;
		}
		if (!$actas && ($now < $adata['startdate'] || $adata['enddate']<$now)) { //outside normal range for test
			$query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid'";
			$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result2);
			if ($row!=null) {
				if ($now<$row[0] || $row[1]<$now) { //outside exception dates
					if ($now > $adata['startdate'] && $now<$adata['reviewdate']) {
						$isreview = true;
					} else {
						if (!isset($teacherid)) {
							echo "Assessment is closed";
							exit;
						}
					}
				} else { //in exception
					if ($adata['enddate']<$now) { //exception is for past-due-date
						$inexception = true;	
					}
				}
			} else { //no exception
				if ($now > $adata['startdate'] && $now<$adata['reviewdate']) {
					$isreview = true;
				} else {
					if (!isset($teacherid)) {
						echo "Assessment is closed";
						exit;
					}
				}
			}
		}
		
		//check for password
		if (trim($adata['password'])!='' && !isset($teacherid)) { //has passwd
			$pwfail = true;
			if (isset($_POST['password'])) {
				if (trim($_POST['password'])==trim($adata['password'])) {
					$pwfail = false;
				} else {
					$out = "<p>Password incorrect.  Try again.<p>";
				}
			} 
			if ($pwfail) {
				require("../header.php");
				echo $out;
				echo "<p>Password required for access.</p>";
				echo "<form method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?cid={$_GET['cid']}&amp;id={$_GET['id']}\">";
				echo "<p>Password: <input type=text name=\"password\" /></p>";
				echo "<input type=submit value=\"Submit\" />";
				echo "</form>";
				require("../footer.php");
				exit;
			}
		}
		
		$query = "SELECT id,agroupid FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		
		if ($line == null) { //starting test
			//get question set
			
			if (trim($adata['itemorder'])=='') {
				echo "No questions in assessment!";
				exit;
			}
			
			list($qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist) = generateAssessmentData($adata['itemorder'],$adata['shuffle'],$aid);
			
			if ($qlist=='') {  //assessment has no questions!
				echo "<html><body>Assessment has no questions!";
				echo "</body></html>\n";
				exit;
			} 
			
			$bestscorelist = $scorelist;
			$bestattemptslist = $attemptslist;
			$bestseedslist = $seedlist;
			$bestlalist = $lalist;
			
			$starttime = time();
			
			$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,bestscores,bestattempts,bestseeds,bestlastanswers,reviewscores,reviewattempts,reviewseeds,reviewlastanswers) ";
			$query .= "VALUES ('$userid','{$_GET['id']}','$qlist','$seedlist','$scorelist','$attemptslist','$lalist',$starttime,'$bestscorelist','$bestattemptslist','$bestseedslist','$bestlalist','$scorelist','$attemptslist','$reviewseedlist','$lalist');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$sessiondata['sessiontestid'] = mysql_insert_id();
			$sessiondata['isreview'] = $isreview;
			if (isset($teacherid) || $actas) {
				$sessiondata['isteacher']=true;
			} else {
				$sessiondata['isteacher']=false;
			}
			if ($actas) {
				$sessiondata['actas']=$_GET['actas'];
				$sessiondata['isreview'] = false;
			} else {
				unset($sessiondata['actas']);
			}
			$sessiondata['groupid'] = 0;
			$query = "SELECT name,theme,topbar FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			$sessiondata['coursename'] = mysql_result($result,0,0);
			$sessiondata['coursetheme'] = mysql_result($result,0,1);
			$sessiondata['coursetopbar'] =  mysql_result($result,0,2);
			if (isset($studentinfo['timelimitmult'])) {
				$sessiondata['timelimitmult'] = $studentinfo['timelimitmult'];
			} else {
				$sessiondata['timelimitmult'] = 1.0;
			}
			writesessiondata();
			session_write_close();
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showtest.php");
			exit;
		} else { //returning to test
			
			$deffeedback = explode('-',$adata['deffeedback']);
			//removed: $deffeedback[0] == "Practice" || 
			if ($myrights<6 || isset($teacherid)) {  // is teacher or guest - delete out out assessment session
				require_once("../includes/filehandler.php");
				deleteasidfilesbyquery(array('userid'=>$userid,'assessmentid'=>$aid),1);
				$query = "DELETE FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='$aid' LIMIT 1";
				$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showtest.php?cid={$_GET['cid']}&id=$aid");
				exit;
			}
			//Return to test.
			$sessiondata['sessiontestid'] = $line['id'];
			$sessiondata['isreview'] = $isreview;
			if (isset($teacherid) || $actas) {
				$sessiondata['isteacher']=true;
			} else {
				$sessiondata['isteacher']=false;
			}
			if ($actas) {
				$sessiondata['actas']=$_GET['actas'];
				$sessiondata['isreview'] = false;
			} else {
				unset($sessiondata['actas']);
			}
			
			
			$sessiondata['groupid'] = $line['agroupid'];
		
			$query = "SELECT name,theme,topbar FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			$sessiondata['coursename'] = mysql_result($result,0,0);
			$sessiondata['coursetheme'] = mysql_result($result,0,1);
			$sessiondata['coursetopbar'] =  mysql_result($result,0,2);
			if (isset($studentinfo['timelimitmult'])) {
				$sessiondata['timelimitmult'] = $studentinfo['timelimitmult'];
			} else {
				$sessiondata['timelimitmult'] = 1.0;
			}
			writesessiondata();
			session_write_close();
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showtest.php");
		}
		exit;
	} 
	
	//already started test
	if (!isset($sessiondata['sessiontestid'])) {
		echo "<html><body>Error.  Access test from course page</body></html>\n";
		exit;
	}
	$testid = addslashes($sessiondata['sessiontestid']);
	$asid = $testid;
	$isteacher = $sessiondata['isteacher'];
	if (isset($sessiondata['actas'])) {
		$userid = $sessiondata['actas'];
	}
	$query = "SELECT * FROM imas_assessment_sessions WHERE id='$testid'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$questions = explode(",",$line['questions']);

	$seeds = explode(",",$line['seeds']);
	$scores = explode(",",$line['scores']);
	$attempts = explode(",",$line['attempts']);
	$lastanswers = explode("~",$line['lastanswers']);
	
	if (trim($line['reattempting'])=='') {
		$reattempting = array();
	} else {
		$reattempting = explode(",",$line['reattempting']);
	}

	$bestseeds = explode(",",$line['bestseeds']);
	$bestscores = explode(",",$line['bestscores']);
	$bestattempts = explode(",",$line['bestattempts']);
	$bestlastanswers = explode("~",$line['bestlastanswers']);
	$starttime = $line['starttime'];
	
	$query = "SELECT * FROM imas_assessments WHERE id='{$line['assessmentid']}'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$testsettings = mysql_fetch_array($result, MYSQL_ASSOC);
	$timelimitkickout = ($testsettings['timelimit']<0);
	$testsettings['timelimit'] = abs($testsettings['timelimit']);
	//do time limit mult
	$testsettings['timelimit'] *= $sessiondata['timelimitmult'];
	
	list($testsettings['testtype'],$testsettings['showans']) = explode('-',$testsettings['deffeedback']);
	
	//if submitting, verify it's the correct assessment
	if (isset($_POST['asidverify']) && $_POST['asidverify']!=$testid) {
		echo "<html><body>Error.  It appears you have opened another assessment since you opened this one. ";
		echo "Only one open assessment can be handled at a time. Please reopen the assessment and try again. ";
		echo "<a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
		echo '</body></html>';
		exit;
	}
	
	
	$now = time();
	//check for dates - kick out student if after due date
	//if (!$isteacher) {
	if ($testsettings['avail']==0 && !$isteacher) {
		echo "Assessment is Closed";
		leavetestmsg();
		exit;
	}
	if (!isset($sessiondata['actas']) && ($now < $testsettings['startdate'] || $testsettings['enddate']<$now)) { //outside normal range for test
		$query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='{$line['assessmentid']}'";
		$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result2);
		if ($row!=null) {
			if ($now<$row[0] || $row[1]<$now) { //outside exception dates
				if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
					$isreview = true;
				} else {
					if (!$isteacher) {
						echo "Assessment is closed";
						leavetestmsg();
						exit;
					}
				}
			} else { //in exception
				if ($adata['enddate']<$now) { //exception is for past-due-date
					$inexception = true;	
				}
			}
		} else { //no exception
			if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
				$isreview = true;
			} else {
				if (!$isteacher) {
					echo "Assessment is closed";
					leavetestmsg();
					exit;
				}
			}
		}
	}

	//}
	$superdone = false;
	if ($isreview) {
		if (isset($_POST['isreview']) && $_POST['isreview']==0) {
			echo "Due date has passed.  Submission rejected.";
			leavetestmsg();
			exit;
		}
		//$testsettings['displaymethod'] = "SkipAround";
		$testsettings['testtype']="Practice";
		$testsettings['defattempts'] = 0;
		$testsettings['defpenalty'] = 0;
		$testsettings['showans'] = '0';
		
		$seeds = explode(",",$line['reviewseeds']);
		$scores = explode(",",$line['reviewscores']);
		$attempts = explode(",",$line['reviewattempts']);
		$lastanswers = explode("~",$line['reviewlastanswers']);
		if (trim($line['reviewreattempting'])=='') {
			$reattempting = array();
		} else {
			$reattempting = explode(",",$line['reviewreattempting']);
		}
	} else if ($timelimitkickout) {
		$now = time();
		$timelimitremaining = $testsettings['timelimit']-($now - $starttime);
		//check if past timelimit
		if ($timelimitremaining<1 || isset($_GET['superdone'])) {
			$superdone = true;
			$_GET['done']=true;
		}
		//check for past time limit, with some leniency for javascript timing.
		//want to reject if javascript was bypassed
		if ($timelimitremaining < -1*max(0.05*$testsettings['timelimit'],5)) {
			echo "Time limit has expired.  Submission rejected";
			leavetestmsg();
			exit;
		}
		
		
	}
	$qi = getquestioninfo($questions,$testsettings);
	
	//check for withdrawn
	for ($i=0; $i<count($questions); $i++) {
		if ($qi[$questions[$i]]['withdrawn']==1 && $qi[$questions[$i]]['points']>0) {
			$bestscores[$i] = $qi[$questions[$i]]['points'];
		}
	}
	
	$allowregen = (!$superdone && ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework"));
	$showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
	$showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && is_numeric($testsettings['showans']));
	$showansafterlast = ($testsettings['showans']==='F' || $testsettings['showans']==='J');
	$noindivscores = ($testsettings['testtype']=="EndScore" || $testsettings['testtype']=="NoScores");
	$showhints = ($testsettings['showhints']==1);
	$showtips = $testsettings['showtips'];
	$regenonreattempt = (($testsettings['shuffle']&8)==8);
	
	$reloadqi = false;
	if (isset($_GET['reattempt'])) {
		if ($_GET['reattempt']=="all") {
			for ($i = 0; $i<count($questions); $i++) {
				if ($attempts[$i]<$qi[$questions[$i]]['attempts'] || $qi[$questions[$i]]['attempts']==0) {
					//$scores[$i] = -1;
					if ($noindivscores) { //clear scores if 
						$bestscores[$i] = -1;
					}
					if (!in_array($i,$reattempting)) {
						$reattempting[] = $i;
					}
					if (($regenonreattempt && $qi[$questions[$i]]['regen']==0) || $qi[$questions[$i]]['regen']==1) {
						$seeds[$i] = rand(1,9999);
						if (isset($qi[$questions[$i]]['answeights'])) {
							$reloadqi = true;
						}
					}
				}
			}
		} else if ($_GET['reattempt']=="canimprove") {
			$remainingposs = getallremainingpossible($qi,$questions,$testsettings,$attempts);
			for ($i = 0; $i<count($questions); $i++) {
				if ($attempts[$i]<$qi[$questions[$i]]['attempts'] || $qi[$questions[$i]]['attempts']==0) {
					if ($noindivscores || getpts($scores[$i])<$remainingposs[$i]) {
						//$scores[$i] = -1;
						if (!in_array($i,$reattempting)) {
							$reattempting[] = $i;
						}
						if (($regenonreattempt && $qi[$questions[$i]]['regen']==0) || $qi[$questions[$i]]['regen']==1) {
							$seeds[$i] = rand(1,9999);
							if (isset($qi[$questions[$i]]['answeights'])) {
								$reloadqi = true;
							}
						}
					}
				}
			}
		} else {
			$toclear = $_GET['reattempt'];
			if ($attempts[$toclear]<$qi[$questions[$toclear]]['attempts'] || $qi[$questions[$toclear]]['attempts']==0) {
				//$scores[$toclear] = -1;
				if (!in_array($toclear,$reattempting)) {
					$reattempting[] = $toclear;
				}
				if (($regenonreattempt && $qi[$questions[$toclear]]['regen']==0) || $qi[$questions[$toclear]]['regen']==1) {
					$seeds[$toclear] = rand(1,9999);
					if (isset($qi[$questions[$toclear]]['answeights'])) {
						$reloadqi = true;
					}
				}
			}
		}
		recordtestdata();
	}
	if (isset($_GET['regen']) && $allowregen && $qi[$questions[$_GET['regen']]]['allowregen']==1) {
		srand();
		$toregen = $_GET['regen'];
		$seeds[$toregen] = rand(1,9999);
		$scores[$toregen] = -1;
		$attempts[$toregen] = 0;
		$newla = array();
		deletefilesifnotused($lastanswers[$toregen],$bestlastanswers[$toregen]);
		$laarr = explode('##',$lastanswers[$toregen]);
		foreach ($laarr as $lael) {
			if ($lael=="ReGen") {
				$newla[] = "ReGen";
			}
		}
		$newla[] = "ReGen";
		$lastanswers[$toregen] = implode('##',$newla);
		$loc = array_search($toregen,$reattempting);
		if ($loc!==false) {
			array_splice($reattempting,$loc,1);
		}
		if (isset($qi[$questions[$toregen]]['answeights'])) {
			$reloadqi = true;
		}
		recordtestdata();
	}
	if (isset($_GET['regenall']) && $allowregen) {
		srand();
		if ($_GET['regenall']=="missed") {
			for ($i = 0; $i<count($questions); $i++) {
				if (getpts($scores[$i])<$qi[$questions[$i]]['points'] && $qi[$questions[$i]]['allowregen']==1) { 
					$scores[$i] = -1;
					$attempts[$i] = 0;
					$seeds[$i] = rand(1,9999);
					$newla = array();
					deletefilesifnotused($lastanswers[$i],$bestlastanswers[$i]);
					$laarr = explode('##',$lastanswers[$i]);
					foreach ($laarr as $lael) {
						if ($lael=="ReGen") {
							$newla[] = "ReGen";
						}
					}
					$newla[] = "ReGen";
					$lastanswers[$i] = implode('##',$newla);
					$loc = array_search($i,$reattempting);
					if ($loc!==false) {
						array_splice($reattempting,$loc,1);
					}
					if (isset($qi[$questions[$i]]['answeights'])) {
						$reloadqi = true;
					}
				}
			}
		} else if ($_GET['regenall']=="all") {
			for ($i = 0; $i<count($questions); $i++) {
				if ($qi[$questions[$i]]['allowregen']==0) { 
					continue;
				}
				$scores[$i] = -1;
				$attempts[$i] = 0;
				$seeds[$i] = rand(1,9999);
				$newla = array();
				deletefilesifnotused($lastanswers[$i],$bestlastanswers[$i]);
				$laarr = explode('##',$lastanswers[$i]);
				foreach ($laarr as $lael) {
					if ($lael=="ReGen") {
						$newla[] = "ReGen";
					}
				}
				$newla[] = "ReGen";
				$lastanswers[$i] = implode('##',$newla);
				$reattempting = array();
				if (isset($qi[$questions[$i]]['answeights'])) {
					$reloadqi = true;
				}
			}
		} else if ($_GET['regenall']=="fromscratch" && $testsettings['testtype']=="Practice" && !$isreview) {
			require_once("../includes/filehandler.php");
			deleteasidfilesbyquery(array('userid'=>$userid,'assessmentid'=>$testsettings['id']),1);
			$query = "DELETE FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='{$testsettings['id']}' LIMIT 1";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showtest.php?cid={$testsettings['courseid']}&id={$testsettings['id']}");
			exit;	
		}
		
		recordtestdata();
			
	}
	if (isset($_GET['jumptoans']) && $testsettings['showans']==='J') {
		$tojump = $_GET['jumptoans'];
		$attempts[$tojump]=$qi[$questions[$tojump]]['attempts'];
		if ($scores[$tojump]<0){
			$scores[$tojump] = 0;
		}
		recordtestdata();
		$reloadqi = true;
	}
	
	if ($reloadqi) {
		$qi = getquestioninfo($questions,$testsettings);
	}
	
	
	$isdiag = isset($sessiondata['isdiag']);
	if ($isdiag) {
		$diagid = $sessiondata['isdiag'];
	}
	$isltilimited = (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0);

	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	$useeditor = 1;
	if ($testsettings['eqnhelper']>0) {
		$placeinhead = '<script type="text/javascript">var eetype='.$testsettings['eqnhelper'].'</script>';
		$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqnhelper.js?v=012810\"></script>";
		$placeinhead .= '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';
		$useeqnhelper = true;
	}
	//IP: eqntips 
	if ($testsettings['showtips']==2) {
		$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqntips.js?v=012810\"></script>";
	}
	$cid = $testsettings['courseid'];
	
	require("header.php");
	if ($testsettings['noprint'] == 1) {
		echo '<style type="text/css" media="print"> div.question, div.todoquestion, div.inactive { display: none;} </style>';
	}
	
	if (!$isdiag && !$isltilimited) {
		if (isset($sessiondata['actas'])) {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid={$testsettings['courseid']}\">{$sessiondata['coursename']}</a> ";
			echo "&gt; <a href=\"../course/gb-viewasid.php?cid={$testsettings['courseid']}&amp;asid=$testid&amp;uid={$sessiondata['actas']}\">Gradebook Detail</a> ";
			echo "&gt; View as student</div>";
		} else {
			echo "<div class=breadcrumb>";
			echo "<span style=\"float:right;\">$userfullname</span>";
			echo "$breadcrumbbase <a href=\"../course/course.php?cid={$testsettings['courseid']}\">{$sessiondata['coursename']}</a> ";
	 
			echo "&gt; Assessment</div>";
		}
	}
	
	if ((!$sessiondata['isteacher'] || isset($sessiondata['actas'])) && ($testsettings['isgroup']==1 || $testsettings['isgroup']==2) && ($sessiondata['groupid']==0 || isset($_GET['addgrpmem']))) {
		if (isset($_POST['grpsubmit'])) {
			if ($sessiondata['groupid']==0) {
				//double check not already added to group by someone else
				$query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$testid'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$agroupid = mysql_result($result,0,0);
				if ($agroupid==0) { //really has no group, create group
					$query = "UPDATE imas_assessment_sessions SET agroupid='$testid' WHERE id='$testid'";
					mysql_query($query) or die("Query failed : $query:" . mysql_error());
					$agroupid = $testid;
				} else {
					echo "<p>Someone already added you to a group.  Using that group.</p>";
				}
				$sessiondata['groupid'] = $agroupid;
				writesessiondata();
			} else {
				$agroupid = $sessiondata['groupid'];
			}
			$query = "SELECT assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers ";
			$query .= "FROM imas_assessment_sessions WHERE id='$testid'";
			$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$rowgrptest = mysql_fetch_row($result);
			$rowgrptest = addslashes_deep($rowgrptest);
			$insrow = "'".implode("','",$rowgrptest)."'";
			for ($i=1;$i<$testsettings['groupmax'];$i++) {
				if (isset($_POST['user'.$i]) && $_POST['user'.$i]!=0) {
					$query = "SELECT password,LastName,FirstName FROM imas_users WHERE id='{$_POST['user'.$i]}'";
					$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
					$thisusername = mysql_result($result,0,2) . ' ' . mysql_result($result,0,1);	
					if ($testsettings['isgroup']==1) {
						$md5pw = md5($_POST['pw'.$i]);
						if (mysql_result($result,0,0)!=$md5pw) {
							echo "<p>$thisusername: password incorrect</p>";
							$errcnt++;
							continue;
						} 
					} 
						
					$thisuser = $_POST['user'.$i];
					$query = "SELECT id,agroupid FROM imas_assessment_sessions WHERE userid='{$_POST['user'.$i]}' AND assessmentid={$testsettings['id']}";
					$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
					if (mysql_num_rows($result)>0) {
						$row = mysql_fetch_row($result);
						if ($row[1]>0) { 
							echo "<p>$thisusername already has a group.  No change made</p>";
						} else {
							$query = "UPDATE imas_assessment_sessions SET assessmentid='{$rowgrptest[0]}',agroupid='{$rowgrptest[1]}',questions='{$rowgrptest[2]}'";
							$query .= ",seeds='{$rowgrptest[3]}',scores='{$rowgrptest[4]}',attempts='{$rowgrptest[5]}',lastanswers='{$rowgrptest[6]}',";
							$query .= "starttime='{$rowgrptest[7]}',endtime='{$rowgrptest[8]}',bestseeds='{$rowgrptest[9]}',bestattempts='{$rowgrptest[10]}',";
							$query .= "bestscores='{$rowgrptest[11]}',bestlastanswers='{$rowgrptest[12]}'  WHERE id='{$row[0]}'";
							//$query = "UPDATE imas_assessment_sessions SET agroupid='$agroupid' WHERE id='{$row[0]}'";
							mysql_query($query) or die("Query failed : $query:" . mysql_error());
							echo "<p>$thisusername added to group, overwriting existing attempt.</p>";
						}
					} else {
						$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers) ";
						$query .= "VALUES ('{$_POST['user'.$i]}',$insrow)";
						mysql_query($query) or die("Query failed : $query:" . mysql_error());
						echo "<p>$thisusername added to group.</p>";
					}
				}
			}
		} else {
			echo '<div id="headershowtest" class="pagetitle"><h2>Select group members</h2></div>';
			
			if ($sessiondata['groupid']>0) { //adding members to existing grp
				echo "Current Group Members: <ul>";
				$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM imas_users,imas_assessment_sessions WHERE ";
				$query .= "imas_users.id=imas_assessment_sessions.userid AND imas_assessment_sessions.agroupid='{$sessiondata['groupid']}' ORDER BY imas_users.LastName,imas_users.FirstName";
				$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$curgrp[] = $row[0];
					echo "<li>{$row[2]}, {$row[1]}</li>";
				}
				echo "</ul>";	
			} else {
				echo "Current Group Member: $userfullname</br>";
				$curgrp = array($userid);
			}
			$curids = "'".implode("','",$curgrp)."'";
			$selops = '<option value="0">Select a name..</option>';
			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM imas_users,imas_students ";
			$query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid='{$testsettings['courseid']}' ";
			$query .= "AND imas_users.id NOT IN ($curids) ORDER BY imas_users.LastName,imas_users.FirstName";
			$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$selops .= "<option value=\"{$row[0]}\">{$row[2]}, {$row[1]}</option>";
			}
			echo '<p>Each group member (other than the currently logged in student) to be added should select their name ';
			if ($testsettings['isgroup']==1) {
				echo 'and enter their password ';
			}
			echo 'here.</p>';
			echo '<form method="post" enctype="multipart/form-data" action="showtest.php?addgrpmem=true">';
			echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
			echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
			for ($i=1;$i<$testsettings['groupmax']-count($curgrp)+1;$i++) {
				echo '<br />Username: <select name="user'.$i.'">'.$selops.'</select> ';
				if ($testsettings['isgroup']==1) {
					echo 'Password: <input type=password name="pw'.$i.'" />'."\n";
				}
			}
			echo '<p><input type=submit name="grpsubmit" value="Record Group and Continue"/></p>';
			echo '</form>';
			require("../footer.php");
			exit;
		}
	}
	if ((!$sessiondata['isteacher'] || isset($sessiondata['actas'])) && $testsettings['isgroup']==3  && $sessiondata['groupid']==0) {
		//double check not already added to group by someone else
		$query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$testid'";
		$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
		$agroupid = mysql_result($result,0,0);
		if ($agroupid==0) { //really has no group, create group
			$query = "UPDATE imas_assessment_sessions SET agroupid='$testid' WHERE id='$testid'";
			mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$agroupid = $testid;
		} else {
			echo "<p>Someone already added you to a group.  Using that group.</p>";
		}
		$sessiondata['groupid'] = $agroupid;
		writesessiondata();
	}
	
	//if was added to existing group, need to reload $questions, etc
	echo '<div id="headershowtest" class="pagetitle">';
	echo "<h2>{$testsettings['name']}</h2></div>\n";
	if (isset($sessiondata['actas'])) {
		echo '<p style="color: red;">Teacher Acting as ';
		$query = "SELECT LastName, FirstName FROM imas_users WHERE id='{$sessiondata['actas']}'";
		$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
		$row = mysql_fetch_row($result);
		echo $row[1].' '.$row[0];
		echo '<p>';
	}
	
	if ($testsettings['testtype']=="Practice" && !$isreview) {
		echo "<div class=right><span style=\"color:#f00\">Practice Test.</span>  <a href=\"showtest.php?regenall=fromscratch\">Create new version.</a></div>";
	}
	if ($testsettings['timelimit']>0 && !$isreview && !$superdone) {
		$now = time();
		$remaining = $testsettings['timelimit']-($now - $starttime);
		if ($testsettings['timelimit']>3600) {
			$tlhrs = floor($testsettings['timelimit']/3600);
			$tlrem = $testsettings['timelimit'] % 3600;
			$tlmin = floor($tlrem/60);
			$tlsec = $tlrem % 60;
			$tlwrds = "$tlhrs hour";
			if ($tlhrs > 1) { $tlwrds .= "s";}
			if ($tlmin > 0) { $tlwrds .= ", $tlmin minute";}
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else if ($testsettings['timelimit']>60) {
			$tlmin = floor($testsettings['timelimit']/60);
			$tlsec = $testsettings['timelimit'] % 60;
			$tlwrds = "$tlmin minute";
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else {
			$tlwrds = $testsettings['timelimit'] . " second(s)";
		}
		if ($remaining < 0) {
			echo "<div class=right>Timelimit: $tlwrds.  Time Expired</div>\n";
		} else {
		if ($remaining > 3600) {
			$hours = floor($remaining/3600);
			$remaining = $remaining - 3600*$hours;
		} else { $hours = 0;}
		if ($remaining > 60) {
			$minutes = floor($remaining/60);
			$remaining = $remaining - 60*$minutes;
		} else {$minutes=0;}
		$seconds = $remaining;
		echo "<div class=right id=timelimitholder>Timelimit: $tlwrds. <span id=timeremaining ";
		if ($remaining<300) {
			echo 'style="color:#f00;" ';
		}
		echo ">$hours:$minutes:$seconds</span> remaining</div>\n";
		echo "<script type=\"text/javascript\">\n";
		echo " hours = $hours; minutes = $minutes; seconds = $seconds; done=false;\n";	
		echo " function updatetime() {\n";
		echo "	  seconds--;\n";
		echo "    if (seconds==0 && minutes==0 && hours==0) {done=true; ";
		if ($timelimitkickout) {
			echo "		document.getElementById('timelimitholder').className = \"\";";
			echo "		document.getElementById('timelimitholder').style.color = \"#f00\";";
			echo "		document.getElementById('timelimitholder').innerHTML = \"Time limit expired - submitting now\";";
			echo " 		document.getElementById('timelimitholder').style.fontSize=\"300%\";";
			echo "		if (document.getElementById(\"qform\") == null) { ";
			echo "			setTimeout(\"window.location.pathname='$imasroot/assessment/showtest.php?action=skip&superdone=true'\",2000); return;";
			echo "		} else {";
			echo "		var theform = document.getElementById(\"qform\");";
			echo " 		var action = theform.getAttribute(\"action\");";
			echo "		theform.setAttribute(\"action\",action+'&superdone=true');";
			echo "		if (doonsubmit(theform,true,true)) { setTimeout('document.getElementById(\"qform\").submit()',2000);}} \n";
			echo "		return 0;";
			echo "      }";
			
		} else {
			echo "		alert(\"Time Limit has elapsed\");}\n";
		}
		echo "    if (seconds==0 && minutes==5 && hours==0) {document.getElementById('timeremaining').style.color=\"#f00\";}\n";
		echo "    if (seconds==5 && minutes==0 && hours==0) {document.getElementById('timeremaining').style.fontSize=\"150%\";}\n";
		echo "    if (seconds < 0) { seconds=59; minutes--; }\n";
		echo "    if (minutes < 0) { minutes=59; hours--;}\n";
		echo "	  str = '';\n";
		echo "	  if (hours > 0) { str += hours + ':';}\n";
		echo "    if (hours > 0 && minutes <10) { str += '0';}\n";
		echo "	  if (minutes >0) {str += minutes + ':';}\n";
		echo "	    else if (hours>0) {str += '0:';}\n";
		echo "      else {str += ':';}\n";
		echo "    if (seconds<10) { str += '0';}\n";
		echo "	  str += seconds + '';\n";
		echo "	  document.getElementById('timeremaining').innerHTML = str;\n";
		echo "    if (!done) {setTimeout(\"updatetime()\",1000);}\n";
		echo " }\n";
		echo " updatetime();\n";
		echo "</script>\n";
		}
	} else if ($isreview) {
		echo "<div class=right style=\"color:#f00\">In Review Mode - no scores will be saved<br/><a href=\"showtest.php?regenall=all\">Create new versions of all questions.</a></div>\n";	
	} else if ($superdone) {
		echo "<div class=right>Time limit expired</div>";
	} else {
		echo "<div class=right>No time limit</div>\n";
	}
	
	if (isset($_GET['action'])) {
		if ($_GET['action']=="skip" || $_GET['action']=="seq") {
			echo "<div class=right><span onclick=\"document.getElementById('intro').className='intro';\"><a href=\"#\">Show Instructions</a></span></div>\n";
		}
		if ($_GET['action']=="scoreall") {
			//score test
			$GLOBALS['scoremessages'] = '';
			for ($i=0; $i < count($questions); $i++) {
				//if (isset($_POST["qn$i"]) || isset($_POST['qn'.(1000*($i+1))]) || isset($_POST["qn$i-0"]) || isset($_POST['qn'.(1000*($i+1)).'-0'])) {
					if ($_POST['verattempts'][$i]!=$attempts[$i]) {
						echo "Question ".($i+1)." has been submittted since you viewed it.  Your answer just submitted was not scored or recorded.<br/>";
					} else {
						scorequestion($i);
					}
				//}
			}
			//record scores
			
			$now = time();
			if (isset($_POST['saveforlater'])) {
				recordtestdata(true);
				if ($GLOBALS['scoremessages'] != '') {
					echo '<p>'.$GLOBALS['scoremessages'].'</p>';
				}
				echo "<p>Answers saved, but not submitted for grading.  You may continue with the test, or ";
				echo "come back to it later. ";
				if ($testsettings['timelimit']>0) {echo "The timelimit will continue to count down";}
				echo "</p><p><a href=\"showtest.php\">Return to test</a> or ";
				leavetestmsg();
				
			} else {
				recordtestdata();
				if ($GLOBALS['scoremessages'] != '') {
					echo '<p>'.$GLOBALS['scoremessages'].'</p>';
				}
				showscores($questions,$attempts,$testsettings);
			
				endtest($testsettings);
				leavetestmsg();
			}
		} else if ($_GET['action']=="shownext") {
			if (isset($_GET['score'])) {
				$last = $_GET['score'];
				
				if ($_POST['verattempts']!=$attempts[$last]) {
					echo "<p>The last question has been submittted since you viewed it, and that grade is shown below.  Your answer just submitted was not scored or recorded.</p>";
				} else {
					$GLOBALS['scoremessages'] = '';
					$rawscore = scorequestion($last);
					if ($GLOBALS['scoremessages'] != '') {
						echo '<p>'.$GLOBALS['scoremessages'].'</p>';
					}
					//record score
					
					recordtestdata();
				}
				if ($showeachscore) {
					$possible = $qi[$questions[$last]]['points'];
					echo "<p>Previous Question:<br/>";
					if (getpts($rawscore)!=getpts($scores[$last])) {
						echo "<p>Score before penalty on last attempt: ";
						echo printscore($rawscore,$last);
						echo "</p>";
					}
					echo "Score on last attempt: ";
					echo printscore($scores[$last],$last);
					echo "<br/>Score in gradebook: ";
					echo printscore($bestscores[$last],$last);
					 
					echo "</p>\n";
					if (hasreattempts($last)) {
						echo "<p><a href=\"showtest.php?action=shownext&to=$last&amp;reattempt=$last\">Reattempt last question</a>.  If you do not reattempt now, you will have another chance once you complete the test.</p>\n";
					}
				}
				if ($allowregen && $qi[$questions[$last]]['allowregen']==1) {
					echo "<p><a href=\"showtest.php?action=shownext&to=$last&amp;regen=$last\">Try another similar question</a></p>\n";
				}
				//show next
				unset($toshow);
				for ($i=$last+1;$i<count($questions);$i++) {
					if (unans($scores[$i]) || amreattempting($i)) {
						$toshow=$i;
						$done = false;
						break;
					}
				}
				if (!isset($toshow)) { //no more to show
					$done = true;
				} 
			} else if (isset($_GET['to'])) {
				$toshow = addslashes($_GET['to']);
				$done = false;
			}
			
			if (!$done) { //can show next
				echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?action=shownext&amp;score=$toshow\" onsubmit=\"return doonsubmit(this)\">\n";
				echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
				echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
				basicshowq($toshow);
				showqinfobar($toshow,true,true);
				echo '<input type="submit" class="btn" value="Continue" />';
			} else { //are all done
				showscores($questions,$attempts,$testsettings);
				endtest($testsettings);
				leavetestmsg();
			}
		} else if ($_GET['action']=="skip") {

			if (isset($_GET['score'])) { //score a problem
				$qn = $_GET['score'];
				
				if ($_POST['verattempts']!=$attempts[$qn]) {
					echo "<p>This question has been submittted since you viewed it, and that grade is shown below.  Your answer just submitted was not scored or recorded.</p>";
				} else {
					$GLOBALS['scoremessages'] = '';
					$rawscore = scorequestion($qn);
					
					//record score
					
					recordtestdata();
				}
			   if (!$superdone) {
				echo filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");
				$lefttodo = shownavbar($questions,$scores,$qn,$testsettings['showcat']);
				
				echo "<div class=inset>\n";
				echo "<a name=\"beginquestions\"></a>\n";
				if ($GLOBALS['scoremessages'] != '') {
					echo '<p>'.$GLOBALS['scoremessages'].'</p>';
				}
				$reattemptsremain = false;
				if ($showeachscore) {
					$possible = $qi[$questions[$qn]]['points'];
					if (getpts($rawscore)!=getpts($scores[$qn])) {
						echo "<p>Score before penalty on last attempt: ";
						echo printscore($rawscore,$qn);
						echo "</p>";
					}
					echo "<p>";
					echo "Score on last attempt: ";
					echo printscore($scores[$qn],$qn);
					echo "</p>\n";
					echo "<p>Score in gradebook: ";
					echo printscore($bestscores[$qn],$qn);
					echo "</p>";
										
					
				}
				if (hasreattempts($qn)) {
					if ($showeachscore) {
						echo "<p><a href=\"showtest.php?action=skip&amp;to=$qn&amp;reattempt=$qn\">Reattempt last question</a></p>\n";
					}
					$reattemptsremain = true;
				}
				if ($allowregen && $qi[$questions[$qn]]['allowregen']==1) {
					echo "<p><a href=\"showtest.php?action=skip&amp;to=$qn&amp;regen=$qn\">Try another similar question</a></p>\n";
				}
				
				echo "<p>Question scored. ";
				if ($lefttodo > 0) {
					echo '<b>Select another question</b></p>';
				} else {
					echo '</p>';
				}
				if ($reattemptsremain == false && $showeachscore) {
					echo "<p>This question, with your last answer";
					if (($showansafterlast && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showans']=='F' || $qi[$questions[$qn]]['showans']=='J') {
						echo " and correct answer";
						$showcorrectnow = true;
					} else if ($showansduring && $qi[$questions[$qn]]['showans']=='0' && $qi[$questions[$qn]]['showans']=='0' && $testsettings['showans']==$attempts[$qn]) {
						echo " and correct answer";
						$showcorrectnow = true;
					} else {
						$showcorrectnow = false;
					}
					if ($showcorrectnow) {
						echo ', is displayed below</p>';
						
						displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],2,false,$attempts[$qn],false,false);
					} else {
						echo ", can be viewed by clicking on the question number again.</p>";
					}
				}
				if ($lefttodo > 0) {
					echo "<p>or click <a href=\"showtest.php?action=skip&amp;done=true\">here</a> to finalize assessment and summarize score</p>\n";
				} else {
					echo "<a href=\"showtest.php?action=skip&amp;done=true\">Click here to finalize assessment and summarize score</a>\n";
				}
				echo "</div>\n";
			    }
			} else if (isset($_GET['to'])) { //jump to a problem
				$next = $_GET['to'];
				echo filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");
				
				$lefttodo = shownavbar($questions,$scores,$next,$testsettings['showcat']);
				if (unans($scores[$next]) || amreattempting($next)) {
					echo "<div class=inset>\n";
					echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?action=skip&amp;score=$next\" onsubmit=\"return doonsubmit(this)\">\n";
					echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
					echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
					echo "<a name=\"beginquestions\"></a>\n";
					basicshowq($next);
					showqinfobar($next,true,true);
					echo '<input type="submit" class="btn" value="Submit" />';
					if (($testsettings['showans']=='J' && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='J') {
						echo ' <input type="button" class="btn" value="Jump to Answer" onclick="if (confirm(\'If you jump to the answer, you must generate a new version to earn credit\')) {window.location = \'showtest.php?action=skip&amp;jumptoans='.$next.'&amp;to='.$next.'\'}"/>';
					}
					echo "</form>\n";
					echo "</div>\n";
				} else {
					echo "<div class=inset>\n";
					echo "<a name=\"beginquestions\"></a>\n";
					echo "You've already done this problem.\n";
					$reattemptsremain = false;
					if ($showeachscore) {
						$possible = $qi[$questions[$next]]['points'];
						echo "<p>Score on last attempt: ";
						echo printscore($scores[$next],$next);
						echo "</p>\n";
						echo "<p>Score in gradebook: ";
						echo printscore($bestscores[$next],$next);
						echo "</p>";
					}
					if (hasreattempts($next)) {
						if ($showeachscore) {
							echo "<p><a href=\"showtest.php?action=skip&amp;to=$next&amp;reattempt=$next\">Reattempt this question</a></p>\n";
						}
						$reattemptsremain = true;
					}
					if ($allowregen && $qi[$questions[$next]]['allowregen']==1) {
						echo "<p><a href=\"showtest.php?action=skip&amp;to=$next&amp;regen=$next\">Try another similar question</a></p>\n";
					}
					if ($lefttodo == 0) {
						echo "<a href=\"showtest.php?action=skip&amp;done=true\">Click here to finalize assessment and summarize score</a>\n";
					}
					if (!$reattemptsremain && $testsettings['showans']!='N') {// && $showeachscore) {
						echo "<p>Question with last attempt is displayed for your review only</p>";
						
						$qshowans = ((($showansafterlast && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='F' || $qi[$questions[$next]]['showans']=='J') || ($showansduring && $qi[$questions[$next]]['showans']=='0' && $attempts[$next]>=$testsettings['showans']));
						if ($qshowans) {
							displayq($next,$qi[$questions[$next]]['questionsetid'],$seeds[$next],2,false,$attempts[$next],false,false);
						} else {
							displayq($next,$qi[$questions[$next]]['questionsetid'],$seeds[$next],false,false,$attempts[$next],false,false);
						}
					}
					echo "</div>\n";
				}
			} 
			if (isset($_GET['done'])) { //are all done

				showscores($questions,$attempts,$testsettings);
				endtest($testsettings);
				leavetestmsg();
			}
		} else if ($_GET['action']=="seq") {
			if (isset($_GET['score'])) { //score a problem
				$qn = $_GET['score'];
				if ($_POST['verattempts']!=$attempts[$qn]) {
					echo "<p>The last question has been submitted since you viewed it, and that score is shown below. Your answer just submitted was not scored or recorded.</p>";
				} else {
					$GLOBALS['scoremessages'] = '';
					$rawscore = scorequestion($qn);
					//record score
					recordtestdata();
				}
				
				echo "<div class=review style=\"margin-top:5px;\">\n";
				if ($GLOBALS['scoremessages'] != '') {
					echo '<p>'.$GLOBALS['scoremessages'].'</p>';
				}
				$reattemptsremain = false;
				if ($showeachscore) {
					$possible = $qi[$questions[$qn]]['points'];
					if (getpts($rawscore)!=getpts($scores[$qn])) {
						echo "<p>Score before penalty on last attempt: ";
						echo printscore($rawscore,$qn);
						echo "</p>";
					}
					//echo "<p>";
					//echo "Score on last attempt: ";
					echo "<p>Score on last attempt: ";
					echo printscore($scores[$qn],$qn);
					echo "</p>\n";
					echo "<p>Score in gradebook: ";
					echo printscore($bestscores[$qn],$qn);
					echo "</p>";
					 
					if (hasreattempts($qn)) {
						echo "<p><a href=\"showtest.php?action=seq&amp;to=$qn&amp;reattempt=$qn\">Reattempt last question</a></p>\n";
						$reattemptsremain = true; 
					}
				}
				if ($allowregen && $qi[$questions[$qn]]['allowregen']==1) {
					echo "<p><a href=\"showtest.php?action=seq&amp;to=$qn&amp;regen=$qn\">Try another similar question</a></p>\n";
				}
				unset($toshow);
				if (canimprove($qn) && $showeachscore) {
					$toshow = $qn;
				} else {
					for ($i=$qn+1;$i<count($questions);$i++) {
						if (unans($scores[$i]) || amreattempting($i)) {
							$toshow=$i;
							$done = false;
							break;
						}
					}
					if (!isset($toshow)) {
						for ($i=0;$i<$qn;$i++) {
							if (unans($scores[$i]) || amreattempting($i)) {
								$toshow=$i;
								$done = false;
								break;
							}
						}
					}
				}
				if (!isset($toshow)) { //no more to show
					$done = true;
				} 
				if (!$done) {
					echo "<p>Question scored. Continue with assessment, or click <a href=\"showtest.php?action=seq&amp;done=true\">here</a> to finalize and summarize score.</p>\n";
					echo "</div>\n";
					echo "<hr/>";
				} else {
					echo "</div>\n";
					//echo "<a href=\"showtest.php?action=skip&done=true\">Click here to finalize and score test</a>\n";
				}
				
				
			}
			if (isset($_GET['to'])) { //jump to a problem
				$toshow = $_GET['to'];
			}
			if ($done || isset($_GET['done'])) { //are all done

				showscores($questions,$attempts,$testsettings);
				endtest($testsettings);
				leavetestmsg();
			} else { //show more test 
				echo filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");
				
				echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?action=seq&amp;score=$toshow\" onsubmit=\"return doonsubmit(this,false,true)\">\n";
				echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
				echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
				echo "<input type=hidden name=\"verattempts\" value=\"{$attempts[$toshow]}\" />";
				
				for ($i = 0; $i < count($questions); $i++) {
					
					$qavail = seqshowqinfobar($i,$toshow);
					
					if ($i==$toshow) {
						echo '<div class="curquestion">';
						basicshowq($i,false);
						echo '</div>';
					} else if ($qavail) {
						echo "<div class=todoquestion>";
						basicshowq($i,true);
						echo "</div>";
					} else {
						basicshowq($i,true);
					}
					
					if ($i==$toshow) {
						echo "<div><input type=\"submit\" class=\"btn\" value=\"Submit Question ".($i+1)."\" /></div><p></p>\n";
					}
					echo '<hr class="seq"/>';
				}
				
			}
		}
	} else { //starting test display  
		$canimprove = false;
		$hasreattempts = false;
		$ptsearned = 0;
		$perfectscore = false;
		
		for ($j=0; $j<count($questions);$j++) {
			$canimproveq[$j] = canimprove($j);
			$hasreattemptsq[$j] = hasreattempts($j);
			if ($canimproveq[$j]) {
				$canimprove = true;
			}
			if ($hasreattemptsq[$j]) {
				$hasreattempts = true;
			}
			$ptsearned += getpts($scores[$j]);
		}
		$testsettings['intro'] .= "<p>Total Points Possible: " . totalpointspossible($qi) . "</p>";
		if ($testsettings['isgroup']>0) {
			$testsettings['intro'] .= "<p><span style=\"color:red;\">This is a group assessment.  Any changes effect all group members.</span><br/>";
			if (!$isteacher || isset($sessiondata['actas'])) {
				$testsettings['intro'] .= "Group Members: <ul>";
				$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM imas_users,imas_assessment_sessions WHERE ";
				$query .= "imas_users.id=imas_assessment_sessions.userid AND imas_assessment_sessions.agroupid='{$sessiondata['groupid']}' ORDER BY imas_users.LastName,imas_users.FirstName";
				$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$curgrp[] = $row[0];
					$testsettings['intro'] .= "<li>{$row[2]}, {$row[1]}</li>";
				}
				$testsettings['intro'] .= "</ul>";
			
				if ($testsettings['isgroup']==1 || $testsettings['isgroup']==2) {
					if (count($curgrp)<$testsettings['groupmax']) {
						$testsettings['intro'] .= "<a href=\"showtest.php?addgrpmem=true\">Add Group Members</a></p>";
					} else {
						$testsettings['intro'] .= '</p>';
					}
				} else {
					$testsettings['intro'] .= '</p>';
				}
			}
		}
		if ($ptsearned==totalpointspossible($qi)) {
			$perfectscore = true; 
		} 
		if ($testsettings['displaymethod'] == "AllAtOnce") {
			echo filter("<div class=intro>{$testsettings['intro']}</div>\n");
			echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?action=scoreall\" onsubmit=\"return doonsubmit(this,true)\">\n";
			echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
			echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
			$numdisplayed = 0;
			for ($i = 0; $i < count($questions); $i++) {
				if (unans($scores[$i]) || amreattempting($i)) {
					basicshowq($i);
					showqinfobar($i,true,false);
					$numdisplayed++;
				}
			}
			if ($numdisplayed > 0) {
				echo '<br/><input type="submit" class="btn" value="Submit" />';
				echo '<input type="submit" class="btn" name="saveforlater" value="Save answers" />';
			} else {
				startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$testsettings['testtype']=="NoScores");
				
				leavetestmsg();
				
			}
		} else if ($testsettings['displaymethod'] == "OneByOne") {
			for ($i = 0; $i<count($questions);$i++) {
				if (unans($scores[$i]) || amreattempting($i)) {
					break;
				}
			}
			if ($i == count($questions)) {
				startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$testsettings['testtype']=="NoScores");
			
				leavetestmsg();
				
			} else {
				echo filter("<div class=intro>{$testsettings['intro']}</div>\n");
				echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?action=shownext&amp;score=$i\" onsubmit=\"return doonsubmit(this)\">\n";
				echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
				echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
				basicshowq($i);
				showqinfobar($i,true,true);
				echo '<input type="submit" class="btn" value="Next" />';
			}
		} else if ($testsettings['displaymethod'] == "SkipAround") {
			echo filter("<div class=intro>{$testsettings['intro']}</div>\n");
			
			for ($i = 0; $i<count($questions);$i++) {
				if (unans($scores[$i]) || amreattempting($i)) {
					break;
				}
			}
			shownavbar($questions,$scores,$i,$testsettings['showcat']);
			if ($i == count($questions)) {
				echo "<div class=inset><br/>\n";
				echo "<a name=\"beginquestions\"></a>\n";
				
				startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$testsettings['testtype']=="NoScores");
				
				leavetestmsg();
				
			} else {
				echo "<div class=inset>\n";
				echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?action=skip&amp;score=$i\" onsubmit=\"return doonsubmit(this)\">\n";
				echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
				echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
				echo "<a name=\"beginquestions\"></a>\n";
				basicshowq($i);
				showqinfobar($i,true,true);
				echo '<input type="submit" class="btn" value="Submit" />';
				if (($testsettings['showans']=='J' && $qi[$questions[$i]]['showans']=='0') || $qi[$questions[$i]]['showans']=='J') {
						echo ' <input type="button" class="btn" value="Jump to Answer" onclick="if (confirm(\'If you jump to the answer, you must generate a new version to earn credit\')) {window.location = \'showtest.php?action=skip&amp;jumptoans='.$i.'&amp;to='.$i.'\'}"/>';
					}
				echo "</form>\n";
				echo "</div>\n";
				
			}
		} else if ($testsettings['displaymethod'] == "Seq") {
			for ($i = 0; $i<count($questions);$i++) {
				if ($canimproveq[$i]) {
					break;
				}
			}
			if ($i == count($questions)) {
				startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$testsettings['testtype']=="NoScores");
				
				leavetestmsg();
				
			} else {
				$curq = $i;
				echo filter("<div class=intro>{$testsettings['intro']}</div>\n");
				echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"showtest.php?action=seq&amp;score=$i\" onsubmit=\"return doonsubmit(this,false,true)\">\n";
				echo "<input type=\"hidden\" name=\"asidverify\" value=\"$testid\" />";
				echo "<input type=\"hidden\" name=\"isreview\" value=\"". ($isreview?1:0) ."\" />";
				echo "<input type=\"hidden\" name=\"verattempts\" value=\"{$attempts[$i]}\" />";
				for ($i = 0; $i < count($questions); $i++) {
					$qavail = seqshowqinfobar($i,$curq);
					
					if ($i==$curq) {
						echo '<div class="curquestion">';
						basicshowq($i,false);
						echo '</div>';
					} else if ($qavail) {
						echo "<div class=todoquestion>";
						basicshowq($i,true);
						echo "</div>";
					} else {
						basicshowq($i,true);
					}
					if ($i==$curq) {
						echo "<div><input type=\"submit\" class=\"btn\" value=\"Submit Question ".($i+1)."\" /></div><p></p>\n";
					}
					
					echo '<hr class="seq"/>';
				}
			}
		}
	}
	//IP:  eqntips
	
	require("../footer.php");
	
	function shownavbar($questions,$scores,$current,$showcat) {
		global $imasroot,$isdiag,$testsettings,$attempts,$qi,$allowregen,$bestscores,$isreview,$showeachscore,$noindivscores;
		$todo = 0;
		$earned = 0;
		$poss = 0;
		echo "<a href=\"#beginquestions\"><img class=skipnav src=\"$imasroot/img/blank.gif\" alt=\"Skip Navigation\" /></a>\n";
		echo "<div class=navbar>";
		echo "<h4>Questions</h4>\n";
		echo "<ul class=qlist>\n";
		for ($i = 0; $i < count($questions); $i++) {
			echo "<li>";
			if ($current == $i) { echo "<span class=current>";}
			if (unans($scores[$i]) || amreattempting($i)) {
				$todo++;
			}
			/*
			$icon = '';
			if ($attempts[$i]==0) {
				$icon = "full";
			} else if (hasreattempts($i)) {
				$icon = "half";
			} else {
				$icon = "empty";
			}
			echo "<img src=\"$imasroot/img/aicon/left$icon.gif\"/>";
			$icon = '';
			if (unans($bestscores[$i]) || getpts($bestscores[$i])==0) {
				$icon .= "empty";
			} else if (getpts($bestscores[$i]) == $qi[$questions[$i]]['points']) {
				$icon .= "full";
			} else {
				$icon .= "half";
			}
			if (!canimprovebest($i) && !$allowregen && $icon!='full') {
				$icon .= "ci";
			}
			echo "<img src=\"$imasroot/img/aicon/right$icon.gif\"/>";
			*/	
			
			if ((unans($scores[$i]) && $attempts[$i]==0) || ($noindivscores && amreattempting($i))) {
				echo "<img src=\"$imasroot/img/q_fullbox.gif\"/> ";
			} else if (canimprove($i) && !$noindivscores) {
				echo "<img src=\"$imasroot/img/q_halfbox.gif\"/> ";
			} else {
				echo "<img src=\"$imasroot/img/q_emptybox.gif\"/> ";
			}
			
				
			if ($showcat>1 && $qi[$questions[$i]]['category']!='0') {
				if ($qi[$questions[$i]]['withdrawn']==1) {
					echo "<a href=\"showtest.php?action=skip&amp;to=$i\"><span class=\"withdrawn\">". ($i+1) . ") {$qi[$questions[$i]]['category']}</span></a>";
				} else {
					echo "<a href=\"showtest.php?action=skip&amp;to=$i\">". ($i+1) . ") {$qi[$questions[$i]]['category']}</a>";
				}
			} else {
				if ($qi[$questions[$i]]['withdrawn']==1) {
					echo "<a href=\"showtest.php?action=skip&amp;to=$i\"><span class=\"withdrawn\">Q ". ($i+1) . "</span></a>";
				} else {
					echo "<a href=\"showtest.php?action=skip&amp;to=$i\">Q ". ($i+1) . "</a>";
				}
			}
			if ($showeachscore) {
				if (($isreview && canimprove($i)) || (!$isreview && canimprovebest($i))) {
					echo ' (';
				} else {
					echo ' [';
				}
				if ($isreview) {
					$thisscore = getpts($scores[$i]);
				} else {
					$thisscore = getpts($bestscores[$i]);
				}
				if ($thisscore<0) {
					echo '0';
				} else {
					echo $thisscore;
					$earned += $thisscore;
				}
				echo '/'.$qi[$questions[$i]]['points'];
				$poss += $qi[$questions[$i]]['points'];
				if (($isreview && canimprove($i)) || (!$isreview && canimprovebest($i))) {
					echo ')';
				} else {
					echo ']';
				}
			}
			
			if ($current == $i) { echo "</span>";}
			
			echo "</li>\n";
		}
		echo "</ul>";
		if ($showeachscore) {
			if ($isreview) {
				echo "<p>Review: ";
			} else {
				echo "<p>Grade: ";
			}
			echo "$earned/$poss</p>";
		}
		if (!$isdiag && $testsettings['noprint']==0) {
			echo "<p><a href=\"#\" onclick=\"window.open('$imasroot/assessment/printtest.php','printver','width=400,height=300,toolbar=1,menubar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));return false;\">Print Version</a></p> ";
		}

		echo "</div>\n";
		return $todo;
	}
	
	function showscores($questions,$attempts,$testsettings) {
		global $isdiag,$allowregen,$isreview,$noindivscores,$scores,$bestscores,$qi,$superdone,$timelimitkickout;
		if ($isdiag) {
			global $userid;
			$query = "SELECT * from imas_users WHERE id='$userid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$userinfo = mysql_fetch_array($result, MYSQL_ASSOC);
			echo "<h3>{$userinfo['LastName']}, {$userinfo['FirstName']}: ";
			echo substr($userinfo['SID'],0,strpos($userinfo['SID'],'~'));
			echo "</h3>\n";
		}
		
		echo "<h3>Scores:</h3>\n";
		
		if (!$noindivscores) {
			echo "<table class=scores>";
			for ($i=0;$i < count($scores);$i++) {
				echo "<tr><td>";
				if ($bestscores[$i] == -1) {
					$bestscores[$i] = 0;
				}
				if ($scores[$i] == -1) {
					$scores[$i] = 0;
					echo 'Question '. ($i+1) . ': </td><td>';
					echo "Last attempt: ";
					
					echo "Not answered";
					echo "</td>";
					echo "<td>  Score in Gradebook: ";
					echo printscore($bestscores[$i],$i);
					echo "</td>";
					
					echo "</tr>\n";
				} else {
					echo 'Question '. ($i+1) . ': </td><td>';
					echo "Last attempt: ";
					
					echo printscore($scores[$i],$i);
					echo "</td>";
					echo "<td>  Score in Gradebook: ";
					echo printscore($bestscores[$i],$i);
					echo "</td>";
					
					echo "</tr>\n";
				}
			}
			echo "</table>";
		}
		global $testid;
		
		recordtestdata();
			
		if ($testsettings['testtype']!="NoScores") {
			$total = 0;
			$lastattempttotal = 0;
			for ($i =0; $i < count($bestscores);$i++) {
				if (getpts($bestscores[$i])>0) { $total += getpts($bestscores[$i]);}
				if (getpts($scores[$i])>0) { $lastattempttotal += getpts($scores[$i]);}
			}
			$totpossible = totalpointspossible($qi);
			
			echo "<p>Total Points on Last Attempts:  $lastattempttotal out of $totpossible possible</p>\n";
						
			if ($total<$testsettings['minscore']) {
				echo "<p><b>Total Points Earned:  $total out of $totpossible possible: ";	
			} else {
				echo "<p><b>Total Points in Gradebook: $total out of $totpossible possible: ";
			}
			
			$average = round(100*((float)$total)/((float)$totpossible),1);
			echo "$average % </b></p>\n";	
			
			$endmsg = unserialize($testsettings['endmsg']);
			$outmsg = '';
			if (isset($endmsg['msgs'])) {
				foreach ($endmsg['msgs'] as $sc=>$msg) { //array must be reverse sorted
					if (($endmsg['type']==0 && $total>=$sc) || ($endmsg['type']==1 && $average>=$sc)) {
						$outmsg = $msg;
						break;
					}
				}
			}
			if ($outmsg=='') {
				$outmsg = $endmsg['def'];
			}
			if ($outmsg!='') {
				echo "<p style=\"color:red;font-weight: bold;\">$outmsg</p>";
			}
			
			if ($total<$testsettings['minscore']) {
				echo "<p><span style=\"color:red;\"><b>A score of {$testsettings['minscore']} is required to receive credit for this assessment<br/>Grade in Gradebook: No Credit (NC)</span></p> ";	
			}
		} else {
			echo "<p><b>Your scores have been recorded for this assessment.</b></p>";
		}
		
		//if timelimit is exceeded
		$now = time();
		if (!$timelimitkickout && ($testsettings['timelimit']>0) && (($now-$GLOBALS['starttime']) > $testsettings['timelimit'])) {
			$over = $now-$GLOBALS['starttime'] - $testsettings['timelimit'];
			echo "<p>Time limit exceeded by ";
			if ($over > 60) {
				$overmin = floor($over/60);
				echo "$overmin minutes, ";
				$over = $over - $overmin*60;
			}
			echo "$over seconds.<br/>\n";
			echo "Grade is subject to acceptance by the instructor</p>\n";
		}
		
		
		if (!$superdone) { // $total < $totpossible && 
			if ($noindivscores) {
				echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions allowed (note: where reattempts are allowed, all scores, correct and incorrect, will be cleared)</p>";
			} else {
				if (canimproveany()) {
					echo "<p><a href=\"showtest.php?reattempt=canimprove\">Reattempt test</a> on questions that can be improved where allowed</p>";
				} 
				if (hasreattemptsany()) {
					echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on all questions where allowed</p>";
				}
			}
			
			if ($allowregen) {
				echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores where allowed.</p>";
				echo "<p><a href=\"showtest.php?regenall=all\">Try similar problems</a> for all questions where allowed.</p>";
			}
		}
		if ($testsettings['testtype']!="NoScores") {
			$hascatset = false;
			foreach($qi as $qii) {
				if ($qii['category']!='0') {
					$hascatset = true;
					break;
				}
			}
			if ($hascatset) {
				include("../assessment/catscores.php");
				catscores($questions,$bestscores,$testsettings['defpoints']);
			}
		}
			
		
	}

	function endtest($testsettings) {
		
		//unset($sessiondata['sessiontestid']);
	}
	function leavetestmsg() {
		global $isdiag, $diagid, $isltilimited, $testsettings;
		if ($isdiag) {
			echo "<a href=\"../diag/index.php?id=$diagid\">Exit Assessment</a></p>\n";
		} else if ($isltilimited) {
			
		} else {
			echo "<a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a></p>\n";
		}
	}
?>
