<?php
//IMathAS:  View/Edit and Question breakdown views
//(c) 2007 David Lippman
	require("../validate.php");
	require_once("../includes/filehandler.php");
		
	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	$cid = $_GET['cid'];
	$asid = $_GET['asid'];
	
	if ($isteacher || $istutor) {
		if (isset($sessiondata[$cid.'gbmode'])) {
			$gbmode =  $sessiondata[$cid.'gbmode'];
		} else {
			$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$gbmode = mysql_result($result,0,0);
		}
		if (isset($_GET['stu']) && $_GET['stu']!='') {
			$stu = $_GET['stu'];
		} else {
			$stu = 0;
		}
		if (isset($_GET['from'])) {
			$from = $_GET['from'];
		} else {
			$from = 'gb';
		}
		//Gbmode : Links NC Dates
		$totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
		$links = floor($gbmode/100)%10; //0: view/edit, 1 q breakdown
		$hidenc = floor($gbmode/10)%10; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
		$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all
	} else {
		$links = 0;
		$stu = 0;
		$from = 'gb';
	}
	
	

	if ($_GET['asid']=="new" && $isteacher) {
		$aid = $_GET['aid'];
		//student could have started, so better check to make sure it still doesn't exist
		$query = "SELECT id FROM imas_assessment_sessions WHERE userid='{$_GET['uid']}' AND assessmentid='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			$_GET['asid'] = mysql_result($result,0,0);
		} else {
			$query = "SELECT * FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$adata = mysql_fetch_array($result, MYSQL_ASSOC);
		
			require("../assessment/asidutil.php");
			list($qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist) = generateAssessmentData($adata['itemorder'],$adata['shuffle'],$aid);
			$starttime = time();
			$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,bestscores,bestattempts,bestseeds,bestlastanswers,reviewscores,reviewattempts,reviewseeds,reviewlastanswers) ";
			$query .= "VALUES ('{$_GET['uid']}','$aid','$qlist','$seedlist','$scorelist','$attemptslist','$lalist',$starttime,'$scorelist','$attemptslist','$seedlist','$lalist','$scorelist','$attemptslist','$reviewseedlist','$lalist');";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$asid = mysql_insert_id();
																
			$_GET['asid'] = $asid;
		}
		
	}
	//PROCESS ANY TODOS
	if (isset($_GET['clearattempt']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['clearattempt']=="confirmed") {
			if ($from=='isolate' || $from=='stugrp') {
				$query = "SELECT assessmentid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$aid = mysql_result($result,0,0);
			}
			$qp = getasidquery($_GET['asid']);
			deleteasidfilesbyquery(array($qp[0]=>$qp[1]),1);
			
			$query = "DELETE FROM imas_assessment_sessions";// WHERE id='{$_GET['asid']}'";
			$query .= " WHERE {$qp[0]}='{$qp[1]}'";
			//$query .= getasidquery($_GET['asid']);
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			if ($from=='isolate') {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/isolateassessgrade.php?stu=$stu&cid={$_GET['cid']}&aid=$aid&gbmode=$gbmode");
			} else if ($from=='stugrp') {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid={$_GET['cid']}&aid=$aid");
			} else {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid={$_GET['cid']}&gbmode=$gbmode");
			}
			exit;
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
				echo getconfirmheader(true);
			} else {
				$pers = 'student';
				echo getconfirmheader();
			}
			echo "<p>Are you sure you want to clear this $pers's assessment attempt?  This will make it appear the $pers never tried the assessment, and the $pers will receive a new version of the assessment.</p>";
			echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&from=$from&clearattempt=confirmed'\" value=\"Really Clear\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['breakfromgroup']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['breakfromgroup']=="confirmed") {
			$query = "SELECT count(id) FROM imas_assessment_sessions WHERE agroupid='{$_GET['asid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0)>1) { //was group creator and others in group; need to move to new id
				$query = "SELECT id,agroupid,userid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$row = mysql_fetch_row($result);
				$oldgroupid = $row[1];
				$thisuserid = $row[2];
				$query = "SELECT userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers ";
				$query .= "FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$row = mysql_fetch_row($result);
				$insrow = "'".implode("','",addslashes_deep($row))."'";
				$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers) ";
				$query .= "VALUES ($insrow)";
				mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$newasid = mysql_insert_id();
				$query = "DELETE FROM imas_assessment_sessions WHERE id='{$_GET['asid']}' LIMIT 1";
				mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$query = "SELECT sessionid,sessiondata FROM imas_sessions WHERE userid='$thisuserid'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$tmpsessdata = unserialize(base64_decode($row[1]));
					if ($tmpsessdata['sessiontestid']==$_GET['asid']) {
						$tmpsessdata['sessiontestid'] = $newasid;
						$tmpsessdata['groupid'] = 0;
						$tmpsessdata = base64_encode(serialize($tmpsessdata));
						$query = "UPDATE imas_sessions SET sessiondata='$tmpsessdata' WHERE sessionid='{$row[0]}'";
						mysql_query($query) or die("Query failed : $query:" . mysql_error());
					}
				}
				$_GET['asid'] = $newasid;
			} else {
				$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE id='{$_GET['asid']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		} else {
			echo getconfirmheader();
			echo "<p>Are you sure you want to separate this student from their current group?</p>";
			echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&uid={$_GET['uid']}&breakfromgroup=confirmed'\" value=\"Really Separate\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='gb-viewasid.php?stu=$stu&from=$from&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['clearscores']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['clearscores']=="confirmed") {
			
			//$whereqry = getasidquery($_GET['asid']);
			$qp = getasidquery($_GET['asid']);
			deleteasidfilesbyquery(array($qp[0]=>$qp[1]),1);
			$whereqry = " WHERE {$qp[0]}='{$qp[1]}'";
			$query = "SELECT seeds,lastanswers,bestlastanswers FROM imas_assessment_sessions $whereqry";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$seeds = explode(',',mysql_result($result,0,0));
				
			$scores = array_fill(0,count($seeds),-1);
			$attempts = array_fill(0,count($seeds),0);
			$lastanswers = array_fill(0,count($seeds),'');
			$scorelist = implode(",",$scores);
			$attemptslist = implode(",",$attempts);
			$lalist = implode("~",$lastanswers);
			$bestscorelist = implode(',',$scores);
			$bestattemptslist = implode(',',$attempts);
			$bestseedslist = implode(',',$seeds);
			$bestlalist = implode('~',$lastanswers);
			
			$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',lastanswers='$lalist',reattempting='',";
			$query .= "bestscores='$bestscorelist',bestattempts='$bestattemptslist',bestseeds='$bestseedslist',bestlastanswers='$bestlalist' ";
			$query .= $whereqry;//"WHERE id='{$_GET['asid']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			//unset($_GET['asid']);
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
				echo getconfirmheader(true);
			} else {
				$pers = 'student';
				echo getconfirmheader();
			}
			echo "<p>Are you sure you want to clear this $pers's scores for this assessment?</p>";
			echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&from=$from&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}&clearscores=confirmed'\" value=\"Really Clear\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='gb-viewasid.php?stu=$stu&from=$from&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['clearq']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['confirmed']=="true") {
			$qp = getasidquery($_GET['asid']);
			$whereqry = " WHERE {$qp[0]}='{$qp[1]}'";
			//$whereqry = getasidquery($_GET['asid']);
			
			$query = "SELECT attempts,lastanswers,reattempting,scores,bestscores,bestattempts,bestlastanswers FROM imas_assessment_sessions $whereqry"; //WHERE id='{$_GET['asid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			
			$scores = explode(",",$line['scores']);
			$attempts = explode(",",$line['attempts']);
			$lastanswers = explode("~",$line['lastanswers']);
			$reattempting = explode(',',$line['reattempting']);
			$bestscores = explode(",",$line['bestscores']);
			$bestattempts = explode(",",$line['bestattempts']);
			$bestlastanswers = explode("~",$line['bestlastanswers']);
			
			$clearid = $_GET['clearq'];
			if ($clearid!=='' && is_numeric($clearid) && isset($scores[$clearid])) {
				deleteasidfilesfromstring($lastanswers[$clearid].$bestlastanswers[$clearid],$qp[1]);
				$scores[$clearid] = -1;
				$attempts[$clearid] = 0;
				$lastanswers[$clearid] = '';
				$bestscores[$clearid] = -1;
				$bestattempts[$clearid] = 0;
				$bestlastanswers[$clearid] = '';
				$loc = array_search($clearid,$reattempting);
				if ($loc!==false) {
					array_splice($reattempting,$loc,1);
				}
				
				$scorelist = implode(",",$scores);
				$attemptslist = implode(",",$attempts);
				$lalist = addslashes(implode("~",$lastanswers));
				$bestscorelist = implode(',',$scores);
				$bestattemptslist = implode(',',$attempts);
				$bestlalist = addslashes(implode('~',$lastanswers));
				$reattemptinglist = implode(',',$reattempting);
				
				$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',lastanswers='$lalist',";
				$query .= "bestscores='$bestscorelist',bestattempts='$bestattemptslist',bestlastanswers='$bestlalist',reattempting='$reattemptinglist' ";
				$query .= $whereqry; //"WHERE id='{$_GET['asid']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			} else {
				echo "<p>Error.  Try again.</p>";
			}
			//unset($_GET['asid']);
			unset($_GET['clearq']);
			
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
				echo getconfirmheader(true);
			} else {
				$pers = 'student';
				echo getconfirmheader();
			}
			echo "<p>Are you sure you want to clear this $pers's scores for this question?</p>";
			echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&clearq={$_GET['clearq']}&uid={$_GET['uid']}&confirmed=true'\" value=\"Really Clear\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='gb-viewasid.php?stu=$stu&from=$from&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	
	//OUTPUTS
	if ($links==0) { //View/Edit full assessment
		require("../assessment/displayq2.php");
		if (isset($_GET['update']) && ($isteacher || $istutor)) {
			if (isoktorec()) {
				$scores = array();
				$i = 0;
				while (isset($_POST[$i]) || isset($_POST["$i-0"])) {
					$j=0;
					$scpt = array();
					if (isset($_POST["$i-0"])) {
	
						while (isset($_POST["$i-$j"])) {
							if ($_POST["$i-$j"]!='N/A') {
								$scpt[$j] = $_POST["$i-$j"];
							} else {
								$scpt[$j] = -1;
							}
							$j++;
						}
						$scores[$i] = implode('~',$scpt);
					} else {
						if ($_POST[$i]!='N/A') {
							$scores[$i] = $_POST[$i];
						} else {
							$scores[$i] = -1;
						}
					}
					$i++;
				}
				$scorelist = implode(",",$scores);
				$feedback = $_POST['feedback'];
				$query = "UPDATE imas_assessment_sessions SET bestscores='$scorelist',feedback='$feedback'";
				if (isset($_POST['updategroup'])) {
					$qp = getasidquery($_GET['asid']);
					$query .=  " WHERE {$qp[0]}='{$qp[1]}'";
					//$query .= getasidquery($_GET['asid']);
				} else {
					$query .= "WHERE id='{$_GET['asid']}'";
				}
			} else {
				echo "No authority to change scores.";
				exit;
			}
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			if ($from=='isolate') {
				$query = "SELECT assessmentid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$aid = mysql_result($result,0,0);
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/isolateassessgrade.php?stu=$stu&cid={$_GET['cid']}&aid=$aid&gbmode=$gbmode");
			} else if ($from=='stugrp') {
				$query = "SELECT assessmentid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$aid = mysql_result($result,0,0);
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid={$_GET['cid']}&aid=$aid");
			} else {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid={$_GET['cid']}&gbmode=$gbmode");
			}
			exit;
		}
		$useeditor='review';
		$sessiondata['coursetheme'] = $coursetheme;
		require("../assessment/header.php");
		echo "<style type=\"text/css\">p.tips {	display: none;}\n</style>\n";
		if (isset($_GET['starttime']) && $isteacher) {
			$query = "UPDATE imas_assessment_sessions SET starttime='{$_GET['starttime']}' ";//WHERE id='{$_GET['asid']}'";
			//$query .= getasidquery($_GET['asid']);
			$qp = getasidquery($_GET['asid']);
			$query .=  " WHERE {$qp[0]}='{$qp[1]}'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
		}
		
		$query = "SELECT imas_assessments.name,imas_assessments.timelimit,imas_assessments.defpoints,imas_assessments.tutoredit,";
		$query .= "imas_assessments.deffeedback,imas_assessments.enddate,imas_assessment_sessions.* ";
		$query .= "FROM imas_assessments,imas_assessment_sessions ";
		$query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id='{$_GET['asid']}'";
		if (!$isteacher && !$istutor) {
			$query .= " AND imas_assessment_sessions.userid='$userid'";
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)==0) {
			echo "uh oh.  Bad assessment id";
			exit;
		}
		$line=mysql_fetch_array($result, MYSQL_ASSOC);
		
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		if ($stu>0) {
			echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
			echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Student Detail</a> ";
		} else if ($_GET['from']=="isolate") {
			echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
			echo "&gt; <a href=\"isolateassessgrade.php?cid=$cid&aid={$line['assessmentid']}\">View Scores</a> ";	
		} else if ($_GET['from']=='stugrp') {
			echo "&gt; <a href=\"managestugrps.php?cid=$cid&aid={$line['assessmentid']}\">Student Groups</a> ";	
		} else {
			echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
		}
		echo "&gt; Detail</div>";
		echo '<div id="headergb-viewasid" class="pagetitle"><h2>Grade Book Detail</h2></div>';
		$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_students.timelimitmult FROM imas_users JOIN imas_students ON imas_users.id=imas_students.userid WHERE imas_users.id='{$_GET['uid']}' AND imas_students.courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$row = mysql_fetch_row($result);
		echo "<h3>{$row[1]}, {$row[0]}</h3>\n";
		
		//do time limit mult
		$timelimitmult = $row[2];
		$line['timelimit'] *= $timelimitmult;
		
		
		$teacherreview = $_GET['uid'];
		
		if ($isteacher || ($istutor && $line['tutoredit']==1)) {
			$canedit = 1;
		} else {
			$canedit = 0;
		}
		
		list($testtype,$showans) = explode('-',$line['deffeedback']);
		if ($showans=='N' && !$isteacher && !$istutor) {
			echo "You shouldn't be here";
			require("../footer.php");
			exit;
		}
		echo "<h4>{$line['name']}</h4>\n";
		if (($isteacher || $istutor) && !isset($_GET['lastver']) && !isset($_GET['reviewver'])) {
			if ($line['agroupid']>0) {
				$q2 = "SELECT i_u.LastName,i_u.FirstName FROM imas_assessment_sessions AS i_a_s,imas_users AS i_u WHERE ";
				$q2 .= "i_u.id=i_a_s.userid AND i_a_s.agroupid='{$line['agroupid']}'";
				$result = mysql_query($q2) or die("Query failed : " . mysql_error());
				echo "<p>Group members: <ul>";
				while ($row = mysql_fetch_row($result)) {
					echo "<li>{$row[0]}, {$row[1]}</li>";
				}
				echo "</ul></p>";
			}	
		}
		if ($line['agroupid']>0) {
			$s3asid = $line['agroupid'];
		} else {
			$s3asid = $asid;
		}
		$aid = $line['assessmentid'];
		
		echo "<p>Started: " . tzdate("F j, Y, g:i a",$line['starttime']) ."<BR>\n";
		if ($line['endtime']==0) { 
			echo "Not Submitted</p>\n";
		} else {
			echo "Last change: " . tzdate("F j, Y, g:i a",$line['endtime']) . "</p>\n";
		}
		$saenddate = $line['enddate'];
		unset($exped);
		$query = "SELECT enddate FROM imas_exceptions WHERE userid='{$_GET['uid']}' AND assessmentid='{$line['assessmentid']}'";
		$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($r2)>0) {
			$exped = mysql_result($r2,0,0);
			if ($exped>$saenddate) {
				$saenddate = $exped;
			}
		}
		
		if ($isteacher) {
			echo "<p>Due Date: ". tzdate("F j, Y, g:i a",$line['enddate']);
			echo " | <a href=\"exception.php?cid=$cid&aid={$line['assessmentid']}&uid={$_GET['uid']}&asid={$_GET['asid']}\">Make/Edit Exception</a>";
			if (isset($exped) && $exped!=$line['enddate']) {
				echo "<br/>Has exception, with due date: ".tzdate("F j, Y, g:i a",$exped);
			}
			echo "</p>";
		}
		if ($isteacher) {
			if ($line['agroupid']>0) {
				echo "<p>This assignment is linked to a group.  Changes will affect the group unless specified. ";
				echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$_GET['uid']}&breakfromgroup=true\">Separate from Group</a></p>";
			}
		}
		
		if ($isteacher) {
			echo "<p><a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$_GET['uid']}&clearattempt=true\" onmouseover=\"tipshow(this,'Clear everything, resetting things like the student never started.  Student will get new versions of questions.')\" onmouseout=\"tipout()\">Clear Attempt</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$_GET['uid']}&clearscores=true\" onmouseover=\"tipshow(this,'Clear scores and attempts, but keep same versions of questions')\" onmouseout=\"tipout()\">Clear Scores</a> | ";
			echo "<a href=\"$imasroot/assessment/showtest.php?cid=$cid&id={$line['assessmentid']}&actas={$_GET['uid']}\" onmouseover=\"tipshow(this,'Take on role of this student, bypassing date restrictions, to submit answers')\" onmouseout=\"tipout()\">View as student</a> | ";
			echo "<a href=\"$imasroot/assessment/printtest.php?cid=$cid&asid={$_GET['asid']}\" target=\"_blank\" onmouseover=\"tipshow(this,'Pull up a print version of this student\'s assessment')\" onmouseout=\"tipout()\">Print Version</a></p>\n";
		}
		
		if (($line['timelimit']>0) && ($line['endtime'] - $line['starttime'] > $line['timelimit'])) {
			$over = $line['endtime']-$line['starttime'] - $line['timelimit'];
			echo "<p>Time limit exceeded by ";
			if ($over > 60) {
				$overmin = floor($over/60);
				echo "$overmin minutes, ";
				$over = $over - $overmin*60;
			}
			echo "$over seconds.<BR>\n";
			$reset = $line['endtime']-$line['timelimit'];
			if ($isteacher) {
				echo "<a href=\"gb-viewasid.php?stu=$stu&starttime=$reset&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}\">Clear overtime and accept grade</a></p>\n";
			}
		}
		
		
		
		$questions = explode(",",$line['questions']);
		if (isset($_GET['lastver'])) {
			$seeds = explode(",",$line['seeds']);
			$scores = explode(",",$line['scores']);
			$attempts = explode(",",$line['attempts']);
			$lastanswers = explode("~",$line['lastanswers']);
			echo "<p>";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}\">Show Scored Attempts</a> | ";
			echo "<b>Showing Last Attempts</b> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}&reviewver=1\">Show Review Attempts</a>";
			echo "</p>";
		} else if (isset($_GET['reviewver'])) {
			$seeds = explode(",",$line['reviewseeds']);
			$scores = explode(",",$line['reviewscores']);
			$attempts = explode(",",$line['reviewattempts']);
			$lastanswers = explode("~",$line['reviewlastanswers']);
			echo "<p>";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}\">Show Scored Attempts</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}&lastver=1\">Show Last Graded Attempts</a> | ";
			echo "<b>Showing Review Attempts</b>";
			echo "</p>";
		}else {
			$seeds = explode(",",$line['bestseeds']);
			$scores = explode(",",$line['bestscores']);
			$attempts = explode(",",$line['bestattempts']);
			$lastanswers = explode("~",$line['bestlastanswers']);
			echo "<p><b>Showing Scored Attempts</b> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&cid=$cid&from=$from&uid={$_GET['uid']}&lastver=1\">Show Last Attempts</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&cid=$cid&from=$from&uid={$_GET['uid']}&reviewver=1\">Show Review Attempts</a>";
			echo "</p>";
		}
		
		$query = "SELECT iq.id,iq.points,iq.withdrawn,iqs.qtype,iqs.control ";
		$query .= "FROM imas_questions AS iq, imas_questionset AS iqs ";
		$query .= "WHERE iq.questionsetid=iqs.id AND iq.assessmentid='{$line['assessmentid']}'";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$totalpossible = 0;
		while ($r = mysql_fetch_row($result)) {
			if ($r[1]==9999) {
				$pts[$r[0]] = $line['defpoints'];  //use defpoints
			} else {
				$pts[$r[0]] = $r[1]; //use points from question
			}
			//$totalpossible += $pts[$r[0]];  do later
			$withdrawn[$r[0]] = $r[2];
			if ($r[3]=='multipart') {
				//if (preg_match('/answeights\s*=\s*("|\')([\d\.\,\s]+)/',$line['control'],$match)) {
				if (($p = strpos($r[4],'answeights'))!==false) {
					$p = strpos($r[4],"\n",$p);
					$answeights[$r[0]] = getansweights($r[0],substr($r[4],0,$p));
				} else {
					preg_match('/anstypes(.*)/',$r[4],$match);
					$n = substr_count($match[1],',')+1;
					if ($n>1) {
						$answeights[$r[0]] = array_fill(0,$n-1,round(1/$n,3));
						$answeights[$r[0]][] = 1-array_sum($answeights[$r[0]]);
					} else {
						$answeights[$r[0]] = array(1);
					}
				}
				for ($i=0; $i<count($answeights[$r[0]])-1; $i++) {
					$answeights[$r[0]][$i] = round($answeights[$r[0]][$i]*$pts[$r[0]],2);
				}
				//adjust for rounding
				$diff = $pts[$r[0]] - array_sum($answeights[$r[0]]);
				$answeights[$r[0]][count($answeights[$r[0]])-1] += $diff;
				$answeights[$r[0]] = implode(', ',$answeights[$r[0]]);
			}
		}
		
		
		
		echo '<script type="text/javascript">';
		echo 'function hidecorrect() {';
		echo '   var butn = document.getElementById("hctoggle");';
		echo '   if (butn.value=="Hide Perfect Score Questions") {';
		echo '      butn.value = "Show Perfect Score Questions";';
		echo '      var setdispto = "block";';
		echo '   } else { ';
		echo '      butn.value = "Hide Perfect Score Questions";';
		echo '      var setdispto = "none";';
		echo '   }';
		echo '   var divs = document.getElementsByTagName("div");';
		echo '   for (var i=0;i<divs.length;i++) {';
		echo '     if (divs[i].className=="iscorrect") { ';
		echo '         if (divs[i].style.display=="none") {';
		echo '               divs[i].style.display = "block";';
		echo '         } else { divs[i].style.display = "none"; }';
		echo '     }';
		echo '    }';
		echo '}';
		echo 'function hideNA() {';
		echo '   var butn = document.getElementById("hnatoggle");';
		echo '   if (butn.value=="Hide Not Answered Questions") {';
		echo '      butn.value = "Show Not Answered Questions";';
		echo '      var setdispto = "block";';
		echo '   } else { ';
		echo '      butn.value = "Hide Not Answered Questions";';
		echo '      var setdispto = "none";';
		echo '   }';
		echo '   var divs = document.getElementsByTagName("div");';
		echo '   for (var i=0;i<divs.length;i++) {';
		echo '     if (divs[i].className=="notanswered") { ';
		echo '         if (divs[i].style.display=="none") {';
		echo '               divs[i].style.display = "block";';
		echo '         } else { divs[i].style.display = "none"; }';
		echo '     }';
		echo '    }';
		echo '}';
		echo '</script>';
		echo '<input type=button id="hctoggle" value="Hide Perfect Score Questions" onclick="hidecorrect()" />';
		echo ' <input type=button id="hnatoggle" value="Hide Not Answered Questions" onclick="hideNA()" />';
		echo "<form id=\"mainform\" method=post action=\"gb-viewasid.php?stu=$stu&cid=$cid&from=$from&asid={$_GET['asid']}&update=true\">\n";
		$total = 0;
		for ($i=0; $i<count($questions);$i++) {
			echo "<div ";
			if (getpts($scores[$i])==$pts[$questions[$i]]) {
				echo 'class="iscorrect"';	
			} else if ($scores[$i]==-1) {
				echo 'class="notanswered"';	
			} else {
				echo 'class="iswrong"';
			}
			$totalpossible += $pts[$questions[$i]];
			echo '>';
			list($qsetid,$cat) = getqsetid($questions[$i]);
			if ($isteacher || $istutor || ($testtype=="Practice" && $showans!="V") || ($testtype!="Practice" && (($showans=="I"  && !in_array(-1,$scores))|| ($showans!="V" && time()>$saenddate)))) {$showa=true;} else {$showa=false;}
			displayq($i,$qsetid,$seeds[$i],$showa,false,$attempts[$i]);
			echo '</div>';
			
			if ($scores[$i]==-1) { $scores[$i]="NA";} else {$total+=getpts($scores[$i]);}
			echo "<div class=review>Question ".($i+1).": ";
			if ($withdrawn[$questions[$i]]==1) {
				echo "<span class=\"red\">Question Withdrawn</span> ";
			}
			list($pt,$parts) = printscore($scores[$i]);
			if ($canedit && $parts=='') { 
				echo "<input type=text size=4 name=\"$i\" value=\"$pt\">";
			} else {
				echo $pt;
			}
			if ($parts!='') {
				if ($canedit) {
					echo " (parts: ";
					$prts = explode(', ',$parts);
					for ($j=0;$j<count($prts);$j++) {
						echo "<input type=text size=2 name=\"$i-$j\" value=\"{$prts[$j]}\"> ";
					}
					echo ")";
				} else {
					echo " (parts: $parts)";
				}
			}
			echo " out of {$pts[$questions[$i]]} ";
			if ($parts!='') {
				echo "(parts: {$answeights[$questions[$i]]}) ";
			}
			echo "in {$attempts[$i]} attempt(s)\n";
			if ($isteacher || $istutor) {
				$laarr = explode('##',$lastanswers[$i]);
				if (count($laarr)>1) {
					echo "<br/>Previous Attempts:";
					$cnt =1;
					for ($k=0;$k<count($laarr)-1;$k++) {
						if ($laarr[$k]=="ReGen") {
							echo ' ReGen ';
						} else {
							echo "  <b>$cnt:</b> " ;
							if (preg_match('/@FILE:(.+?)@/',$laarr[$k],$match)) {
								$url = getasidfileurl($s3asid,$match[1]);
								echo "<a href=\"$url\" target=\"_new\">{$match[1]}</a>";
							} else {
								echo str_replace(array('&','%nbsp;'),array('; ','&nbsp;'),strip_tags($laarr[$k]));
							}
							$cnt++;
						}
					}
				}
			}
			if ($isteacher) {
				echo " <a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?cid=$cid&add=new&quoteq=$i-$qsetid-{$seeds[$i]}&to={$_GET['uid']}\">Use in Msg</a>";
				echo " &nbsp; <a href=\"gb-viewasid.php?stu=$stu&cid=$cid&from=$from&asid={$_GET['asid']}&uid={$_GET['uid']}&clearq=$i\">Clear Score</a> ";
				echo "(Question ID: <a href=\"$imasroot/course/moddataset.php?id=$qsetid&cid=$cid&qid={$questions[$i]}&aid=$aid\">$qsetid</a>)";
				
			}
			echo "</div>\n";
			
		}
		echo "<p></p><div class=review>Total: $total/$totalpossible</div>\n";
		if ($canedit && !isset($_GET['lastver']) && !isset($_GET['reviewver'])) {
			echo "<p>Feedback to student:<br/><textarea cols=60 rows=4 name=\"feedback\">{$line['feedback']}</textarea></p>";
			if ($line['agroupid']>0) {
				echo "<p>Update grade for all group members? <input type=checkbox name=\"updategroup\" checked=\"checked\" /></p>";
			}
			echo "<p><input type=submit value=\"Record Changed Grades\"></p>\n";
			/*
			if ($line['agroupid']>0) {
				$q2 = "SELECT i_u.LastName,i_u.FirstName FROM imas_assessment_sessions AS i_a_s,imas_users AS i_u WHERE ";
				$q2 .= "i_u.id=i_a_s.userid AND i_a_s.agroupid='{$line['agroupid']}'";
				$result = mysql_query($q2) or die("Query failed : " . mysql_error());
				echo "Group members: <ul>";
				while ($row = mysql_fetch_row($result)) {
					echo "<li>{$row[0]}, {$row[1]}</li>";
				}
				echo "</ul>";
			}
			*/
				
		} else if (trim($line['feedback'])!='') {
			echo "<p>Instructor Feedback:<div class=\"intro\">{$line['feedback']}</div></p>";
		}
		echo "</form>";
		
		echo "<p><a href=\"gradebook.php?stu=$stu&cid=$cid\">Return to GradeBook</a></p>\n";
		
		$query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='{$line['assessmentid']}' AND category<>'0'";
		$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			include("../assessment/catscores.php");
			catscores($questions,$scores,$line['defpoints']);
		}
		
	} else if ($links==1) { //show grade detail question/category breakdown
		require("../header.php");
		
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
		if ($stu>0) {echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Student Detail</a> ";}
		echo "&gt; Detail</div>";
		echo "<h2>Grade Book Detail</h2>\n";
		$query = "SELECT FirstName,LastName FROM imas_users WHERE id='{$_GET['uid']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$row = mysql_fetch_row($result);
		echo "<h3>{$row[1]}, {$row[0]}</h3>\n";
		
		$query = "SELECT imas_assessments.name,imas_assessments.defpoints,imas_assessment_sessions.* ";
		$query .= "FROM imas_assessments,imas_assessment_sessions ";
		$query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id='{$_GET['asid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line=mysql_fetch_array($result, MYSQL_ASSOC);
		
		echo "<h4>{$line['name']}</h4>\n";
		echo "<p>Started: " . tzdate("F j, Y, g:i a",$line['starttime']) ."<BR>\n";
		if ($line['endtime']==0) { 
			echo "Not Submitted</p>\n";
		} else {
			echo "Last change: " . tzdate("F j, Y, g:i a",$line['endtime']) . "</p>\n";
		}
		
		
		$query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='{$line['assessmentid']}' AND category<>'0'";
		$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			include("../assessment/catscores.php");
			catscores(explode(',',$line['questions']),explode(',',$line['bestscores']),$line['defpoints']);
		}
		
		$scores = array();
		$qs = explode(',',$line['questions']);
		foreach(explode(',',$line['bestscores']) as $k=>$score) {
			$scores[$qs[$k]] = getpts($score);
		}
		
		echo "<h4>Question Breakdown</h4>\n";
		echo "<table cellpadding=5 class=gb><thead><tr><th>Question</th><th>Points / Possible</th></tr></thead><tbody>\n";
		$query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questions.withdrawn FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		$query .= " AND imas_questions.id IN ({$line['questions']})";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$i=1;
		$totpt = 0;
		$totposs = 0;
		while ($row = mysql_fetch_row($result)) {
			if ($i%2!=0) {echo "<tr class=even>"; } else {echo "<tr class=odd>";}
			echo '<td>';
			if ($row[3]==1) {
				echo '<span class="red">Withdrawn</span> ';
			}
			echo $row[0];
			echo "</td><td>{$scores[$row[1]]} / ";
			if ($row[2]==9999) {
				$poss= $line['defpoints'];
			} else {
				$poss = $row[2];
			}
			echo $poss;
			
			echo "</td></tr>\n";
			$i++;
			$totpt += $scores[$row[1]];
			$totposs += $poss;
		}
		echo "</table>\n";
		
		$pc = round(100*$totpt/$totposs,1);
		echo "<p>Total:  $totpt / $totposs  ($pc %)</p>\n";
		
		echo "<p><a href=\"gradebook.php?stu=$stu&cid=$cid\">Return to GradeBook</a></p>\n";		
				
		
	}

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) { 
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) { 
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}
function getasidquery($asid) {
	$query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$asid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$agroupid = mysql_result($result,0,0);
	if ($agroupid>0) {
		return array('agroupid',$agroupid);
		//return (" WHERE agroupid='$agroupid'");
	} else {
		return array('id',$asid);
		//return (" WHERE id='$asid' LIMIT 1");
	}
}
function isasidgroup($asid) {
	$query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$asid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	return (mysql_result($result,0,0)==$asid);
}
function printscore($sc) {
	if (strpos($sc,'~')===false) {

		return array($sc,'');
	} else {
		$pts = getpts($sc);
		$sc = str_replace('-1','N/A',$sc);
		$sc = str_replace('~',', ',$sc);
		return array($pts,$sc);
	}		
}
//evals a portion of the control section to extract the $answeights
//which might be randomizer determined, hence the seed
function getansweights($qi,$code) {
	global $seeds,$questions;	
	$i = array_search($qi,$questions);
	return sandboxgetweights($code,$seeds[$i]);
}

function sandboxgetweights($code,$seed) {
	srand($seed);
	eval(interpret('control','multipart',$code));
	if (is_array($answeights)) {
		return $answeights;
	} else {
		return explode(',',$answeights);
	}
}

function getconfirmheader($group=false) {
	global $isteacher, $istutor, $userid;
	if ($group) {
		$out = '<h3>Whole Group</h3>';
	} else {
		$query = "SELECT FirstName,LastName FROM imas_users WHERE id='{$_GET['uid']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$row = mysql_fetch_row($result);
		$out = "<h3>{$row[1]}, {$row[0]}</h3>\n";
	}
	$query = "SELECT imas_assessments.name FROM imas_assessments,imas_assessment_sessions ";
	$query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id='{$_GET['asid']}'";
	if (!$isteacher && !$istutor) {
		$query .= " AND imas_assessment_sessions.userid='$userid'";
	}
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$out .= "<h4>".mysql_result($result,0,0)."</h4>";
	return $out;
}

function isoktorec() {
	global $isteacher, $istutor;
	$oktorec = false;
	if ($isteacher) {
		$oktorec = true;
	} else if ($istutor) {
		$query = "SELECT ia.tutoredit FROM imas_assessments AS ia JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
		$query .= "WHERE ias.id='{$_GET['asid']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_result($result,0,0)==1) {
			$oktorec = true;
		}
	}	
	return $oktorec;
}
?>
