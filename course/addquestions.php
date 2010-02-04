<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
include("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Add/Remove Questions";

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=" . $_GET['cid'] . "\">$coursename</a> ";
if (isset($_GET['clearattempts']) || isset($_GET['clearqattempts']) || isset($_GET['withdraw'])) {
	$curBreadcrumb .= "&gt; <a href=\"addquestions.php?cid=" . $_GET['cid'] . "&aid=" . $_GET['aid'] . "\">Add/Remove Questions</a> &gt; Confirm\n";
	//$pagetitle = "Modify Inline Text";
} else {
	$curBreadcrumb .= "&gt; Add/Remove Questions\n";
	//$pagetitle = "Add Inline Text";
}	

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid'])) || !(isset($_GET['aid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];	
	if (isset($_GET['grp'])) { $sessiondata['groupopt'.$aid] = $_GET['grp']; writesessiondata();}
	if (isset($_GET['selfrom'])) {
		$sessiondata['selfrom'.$aid] = $_GET['selfrom'];
		writesessiondata();
	} else {
		if (!isset($sessiondata['selfrom'.$aid])) {
			$sessiondata['selfrom'.$aid] = 'lib';
			writesessiondata();
		}
	}
	
	if (isset($teacherid) && isset($_GET['addset'])) {
		if (!isset($_POST['nchecked']) && !isset($_POST['qsetids'])) {
			$overwriteBody = 1;
			$body = "No questions selected.  <a href=\"addquestions.php?cid=$cid&aid=$aid\">Go back</a>\n";
		} else if (isset($_POST['add'])) {
			include("modquestiongrid.php");	
			if (isset($_GET['process'])) {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
				exit;
			}
		} else {
			$checked = $_POST['nchecked'];
			foreach ($checked as $qsetid) {
				$query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,questionsetid) ";
				$query .= "VALUES ('$aid',9999,9999,9999,'$qsetid');";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$qids[] = mysql_insert_id();
			}
			//add to itemorder
			$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			if ($row[0]=='') {
				$itemorder = implode(",",$qids);
			} else {
				$itemorder  = $row[0] . "," . implode(",",$qids);	
			}
		
			$query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
			exit;
		}	
	}
	if (isset($_GET['modqs'])) {
		if (!isset($_POST['checked']) && !isset($_POST['qids'])) {
			$overwriteBody = 1;
			$body = "No questions selected.  <a href=\"addquestions.php?cid=$cid&aid=$aid\">Go back</a>\n";
		} else {
			include("modquestiongrid.php");
			if (isset($_GET['process'])) {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
				exit;
			}
		}
	}
	if (isset($_GET['clearattempts'])) {
		if ($_GET['clearattempts']=="confirmed") {
			require_once('../includes/filehandler.php');
			deleteasidfilesbyquery(array('assessmentid'=>$aid));
			
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "UPDATE imas_questions SET withdrawn=0 WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
			exit;
		} else {
			$overwriteBody = 1; 
			$query = "SELECT name FROM imas_assessments WHERE id={$_GET['aid']}";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$assessmentname = mysql_result($result,0,0);
			$body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
			$body .= "<h3>$assessmentname</h3>";
			$body .= "<p>Are you SURE you want to delete all attempts (grades) for this assessment?</p>";
			$body .= "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearattempts=confirmed'\">\n";
			$body .= "<input type=button value=\"Nevermind\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid';\"></p>\n";
		}
	}
	if (isset($_GET['clearqattempts'])) {
		if (isset($_GET['confirmed'])) {
			$clearid = $_GET['clearqattempts'];
			if ($clearid!=='' && is_numeric($clearid)) {
				$query = "SELECT id,questions,scores,attempts,lastanswers,bestscores,bestattempts,bestlastanswers ";
				$query .= "FROM imas_assessment_sessions WHERE assessmentid='$aid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$questions = explode(',',$line['questions']);
					$qloc = array_search($clearid,$questions);
					if ($qloc!==false) {
						$scores = explode(',',$line['scores']);
						$attempts = explode(',',$line['attempts']);
						$lastanswers = explode('~',$line['lastanswers']);
						$bestscores = explode(',',$line['bestscores']);
						$bestattempts = explode(',',$line['bestattempts']);
						$bestlastanswers = explode('~',$line['bestlastanswers']);
						
						$scores[$qloc] = -1;
						$attempts[$qloc] = 0;
						$lastanswers[$qloc] = '';
						$bestscores[$qloc] = -1;
						$bestattempts[$qloc] = 0;
						$bestlastanswers[$qloc] = '';
						
						$scorelist = implode(',',$scores);
						$attemptslist = implode(',',$attempts);
						$lalist = addslashes(implode('~',$lastanswers));
						$bestscorelist = implode(',',$scores);
						$bestattemptslist = implode(',',$attempts);
						$bestlalist = addslashes(implode('~',$lastanswers));
						
						$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',lastanswers='$lalist',";
						$query .= "bestscores='$bestscorelist',bestattempts='$bestattemptslist',bestlastanswers='$bestlalist' ";
						$query .= "WHERE id='{$line['id']}'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					} 
				}
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
				exit;
			} else {
				$overwriteBody = 1;
				$body = "<p>Error with question id.  Try again.</p>";
			}
		} else {
			$overwriteBody = 1;
			$body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
			$body .= "<p>Are you SURE you want to delete all attempts (grades) for this question?</p>";
			$body .= "<p>This will allow you to safely change points and penalty for a question, or give students another attempt ";
			$body .= "on a question that needed fixing.  This will NOT allow you to remove the question from the assessment.</p>";
			$body .= "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearqattempts={$_GET['clearqattempts']}&confirmed=1'\">\n";
			$body .= "<input type=button value=\"Nevermind\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid'\"></p>\n";
		}
	}
	if (isset($_GET['withdraw'])) {
		if (isset($_GET['confirmed'])) {
			if (strpos($_GET['withdraw'],'-')!==false) {
				$isingroup = true;
				$loc = explode('-',$_GET['withdraw']);
				$toremove = $loc[0];
			} else {
				$isingroup = false;
				$toremove = $_GET['withdraw'];
			}
			$query = "SELECT itemorder,defpoints FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemorder = explode(',',mysql_result($result,0,0));
			$defpoints = mysql_result($result,0,1);
			
			$qids = array();
			if ($isingroup && $_POST['withdrawtype']!='full') { //is group remove
				$qids = explode('~',$itemorder[$toremove]);
				if (strpos($qids[0],'|')!==false) { //pop off nCr
					array_shift($qids);
				}
			} else if ($isingroup) { //is single remove from group
				$sub = explode('~',$itemorder[$toremove]);
				if (strpos($sub[0],'|')!==false) { //pop off nCr
					array_shift($sub);
				}
				$qids = array($sub[$loc[1]]);
			} else { //is regular item remove
				$qids = array($itemorder[$toremove]);
			}
			$qidlist = implode(',',$qids);
			//withdraw question
			$query = "UPDATE imas_questions SET withdrawn=1";
			if ($_POST['withdrawtype']=='zero' || $_POST['withdrawtype']=='groupzero') {
				$query .= ',points=0';
			}
			$query .= " WHERE id IN ($qidlist)"; 
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			//get possible points if needed
			if ($_POST['withdrawtype']=='full' || $_POST['withdrawtype']=='groupfull') {
				$poss = array();
				$query = "SELECT id,points FROM imas_questions WHERE id IN ($qidlist)";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					if ($row[1]==9999) {
						$poss[$row[0]] = $defpoints;
					} else {
						$poss[$row[0]] = $row[1];
					}
				}
			}
			
			//update assessment sessions
			$query = "SELECT id,questions,bestscores FROM imas_assessment_sessions WHERE assessmentid='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$qarr = explode(',',$row[1]);
				$sarr = explode(',',$row[2]);
				for ($i=0; $i<count($qarr); $i++) {
					if (in_array($qarr[$i],$qids)) {
						if ($_POST['withdrawtype']=='zero' || $_POST['withdrawtype']=='groupzero') {
							$sarr[$i] = 0;
						} else if ($_POST['withdrawtype']=='full' || $_POST['withdrawtype']=='groupfull') {
							$sarr[$i] = $poss[$qarr[$i]];
						}
					}
				}
				$slist = implode(',',$sarr);
				$query = "UPDATE imas_assessment_sessions SET bestscores='$slist' WHERE id='{$row[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
			exit;
			
		} else {
			if (strpos($_GET['withdraw'],'-')!==false) {
				$isingroup = true;
			} else {
				$isingroup = false;
			}
			$overwriteBody = 1;
			$body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
			$body .= "<h3>Withdraw Question</h3>";
			$body .= "<form method=post action=\"addquestions.php?cid=$cid&aid=$aid&withdraw={$_GET['withdraw']}&confirmed=true\">";
			if ($isingroup) {
				$body .= '<p><b>This question is part of a group of questions</b>.  </p>';
				$body .= '<input type=radio name="withdrawtype" value="groupzero" > Set points possible and all student scores to zero <b>for all questions in group</b><br/>';
				$body .= '<input type=radio name="withdrawtype" value="groupfull" checked="1"> Set all student scores to points possible <b>for all questions in group</b><br/>';
				$body .= '<input type=radio name="withdrawtype" value="full" > Set all student scores to points possible <b>for this question only</b>';
			} else {
				$body .= '<input type=radio name="withdrawtype" value="zero" > Set points possible and all student scores to zero<br/>';
				$body .= '<input type=radio name="withdrawtype" value="full" checked="1"> Set all student scores to points possible';
			}
			$body .= '<p>This action can <b>not</b> be undone.</p>';
			$body .= '<p><input type=submit value="Withdraw Question">';
			$body .= "<input type=button value=\"Nevermind\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid'\"></p>\n";
			
			$body .= '</form>';
		}
		
	}
	
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid";
	
	$placeinhead = "<script type=\"text/javascript\">
		var previewqaddr = '$imasroot/course/testquestion.php?cid=$cid';
		var addqaddr = '$address';
		</script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/addquestions.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/addqsort.js\"></script>";
	
	
	//DEFAULT LOAD PROCESSING GOES HERE
	//load filter.  Need earlier than usual header.php load
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require_once("$curdir/../filter/filter.php");
	
	$query = "SELECT ias.id FROM imas_assessment_sessions AS ias,imas_students WHERE ";
	$query .= "ias.assessmentid='$aid' AND ias.userid=imas_students.userid AND imas_students.courseid='$cid' LIMIT 1";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result) > 0) {
		$beentaken = true;
	} else {
		$beentaken = false;
	}
	
	$query = "SELECT itemorder,name,defpoints FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$itemorder = mysql_result($result, 0,0);
	$page_assessmentName = mysql_result($result,0,1);
	$ln = 1;
	$defpoints = mysql_result($result,0,2);
	
	$grp0Selected = "";
	if (isset($sessiondata['groupopt'.$aid])) {
		$grp = $sessiondata['groupopt'.$aid];
		$grp1Selected = ($grp==1) ? " selected" : "";
	} else {
		$grp = 0;
		$grp0Selected = " selected";
	}
	
	$jsarr = '[';
	$items = explode(",",$itemorder);
	$existingq = array();
	$apointstot = 0;
	for ($i = 0; $i < count($items); $i++) {
		if (strpos($items[$i],'~')!==false) {
			$subs = explode('~',$items[$i]);
		} else {
			$subs[] = $items[$i];
		}
		if ($i>0) {
			$jsarr .= ',';
		}
		if (count($subs)>1) {
			if (strpos($subs[0],'|')===false) { //for backwards compat
				$jsarr .= '[1,0,['; 
			} else {
				$grpparts = explode('|',$subs[0]);
				$jsarr .= '['.$grpparts[0].','.$grpparts[1].',[';
				array_shift($subs);
			}
		} 
		for ($j=0;$j<count($subs);$j++) {
			$query = "SELECT imas_questions.questionsetid,imas_questionset.description,imas_questionset.userights,imas_questionset.ownerid,imas_questionset.qtype,imas_questions.points,imas_questions.withdrawn FROM imas_questions,imas_questionset ";
			$query .= "WHERE imas_questions.id='{$subs[$j]}' AND imas_questionset.id=imas_questions.questionsetid";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$existingq[] = $line['questionsetid'];
			if ($j>0) {
				$jsarr .= ',';
			} 
			//output item array
			$jsarr .= '['.$subs[$j].','.$line['questionsetid'].',"'.addslashes(filter(str_replace(array("\r\n", "\n", "\r")," ",$line['description']))).'","'.$line['qtype'].'",'.$line['points'].',';
			if ($line['userights']>2 || $line['ownerid']==$userid || $adminasteacher) { //can edit without template?
				$jsarr .= '1';
			} else {
				$jsarr .= '0';
			}
			$jsarr .= ','.$line['withdrawn'];
			$jsarr .= ']';
		}
		if (count($subs)>1) {
			$jsarr .= ']]';
		}
		$alt = 1-$alt;
		unset($subs);
	}
	$jsarr .= ']';
	
	//DATA MANIPULATION FOR POTENTIAL QUESTIONS
	if ($sessiondata['selfrom'.$aid]=='lib') { //selecting from libraries
	
		//remember search
		if (isset($_POST['search'])) {
			$safesearch = $_POST['search'];
			$safesearch = str_replace(' and ', ' ',$safesearch);
			$search = stripslashes($safesearch);
			$search = str_replace('"','&quot;',$search);
			$sessiondata['lastsearch'.$cid] = str_replace(" ","+",$safesearch);
			if (isset($_POST['searchall'])) {
				$searchall = 1;
			} else {
				$searchall = 0;
			}
			$sessiondata['searchall'.$cid] = $searchall;
			if (isset($_POST['searchmine'])) {
				$searchmine = 1;
			} else {
				$searchmine = 0;
			}
			if (isset($_POST['newonly'])) {
				$newonly = 1;
			} else {
				$newonly = 0;
			}
			$sessiondata['searchmine'.$cid] = $searchmine;
			writesessiondata();
		} else if (isset($sessiondata['lastsearch'.$cid])) {
			$safesearch = str_replace("+"," ",$sessiondata['lastsearch'.$cid]);
			$search = stripslashes($safesearch);
			$search = str_replace('"','&quot;',$search);
			$searchall = $sessiondata['searchall'.$cid];
			$searchmine = $sessiondata['searchmine'.$cid];
		} else {
			$search = '';
			$searchall = 0;
			$searchmine = 0;
			$safesearch = '';
		}
		if (trim($safesearch)=='') {
			$searchlikes = '';
		} else {
			$searchterms = explode(" ",$safesearch);
			$searchlikes = "((imas_questionset.description LIKE '%".implode("%' AND imas_questionset.description LIKE '%",$searchterms)."%') ";
			if (substr($safesearch,0,3)=='id=') {
				$searchlikes = "imas_questionset.id='".substr($safesearch,3)."' AND ";
			} else if (is_numeric($safesearch)) {
				$searchlikes .= "OR imas_questionset.id='$safesearch') AND ";
			} else {
				$searchlikes .= ") AND";
			}
		}
		
		if (isset($_POST['libs'])) {
			if ($_POST['libs']=='') {
				$_POST['libs'] = $userdeflib;
			}
			$searchlibs = $_POST['libs'];
			//$sessiondata['lastsearchlibs'] = implode(",",$searchlibs);
			$sessiondata['lastsearchlibs'.$cid] = $searchlibs;
			writesessiondata();
		} else if (isset($_GET['listlib'])) {
			$searchlibs = $_GET['listlib'];
			$sessiondata['lastsearchlibs'.$cid] = $searchlibs;
			$searchall = 0;
			$sessiondata['searchall'.$cid] = $searchall;
			$sessiondata['lastsearch'.$cid] = '';
			$searchlikes = '';
			$search = '';
			$safesearch = '';
			writesessiondata();
		}else if (isset($sessiondata['lastsearchlibs'.$cid])) {
			//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
			$searchlibs = $sessiondata['lastsearchlibs'.$cid];
		} else {
			$searchlibs = $userdeflib;
		}
		$llist = "'".implode("','",explode(',',$searchlibs))."'";
		
		if (!$beentaken) {
			//potential questions
			$libsortorder = array();
			if (substr($searchlibs,0,1)=="0") {
				$lnamesarr[0] = "Unassigned";
				$libsortorder[0] = 0;
			}
			
			$query = "SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$lnamesarr[$row[1]] = $row[0];
				$libsortorder[$row[1]] = $row[2];
			}
			$lnames = implode(", ",$lnamesarr);

			$page_libRowHeader = ($searchall==1) ? "<th>Library</th>" : "";
			
			if (isset($search)) {
				
				$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_library_items.libid,imas_questionset.ownerid ";
				$query .= "FROM imas_questionset,imas_library_items WHERE $searchlikes "; //imas_questionset.description LIKE '%$safesearch%' ";
				$query .= " (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0) "; 
				$query .= "AND imas_library_items.qsetid=imas_questionset.id ";
				
				if ($searchall==0) {
					$query .= "AND imas_library_items.libid IN ($llist)";
				}
				if ($searchmine==1) {
					$query .= " AND imas_questionset.ownerid='$userid'";
				} else {
					$query .= " AND (imas_library_items.libid > 0 OR imas_questionset.ownerid='$userid') "; 
				}
				$query .= " ORDER BY imas_library_items.libid,imas_questionset.id";
				if ($search=='recommend' && count($existingq)>0) {
					$existingqlist = implode(',',$existingq);  //pulled from database, so no quotes needed
					$query = "SELECT a.questionsetid, count( DISTINCT a.assessmentid ) as qcnt,
						imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.ownerid
						FROM imas_questions AS a 
						JOIN imas_questions AS b ON a.assessmentid = b.assessmentid 
						JOIN imas_questions AS c ON b.questionsetid = c.questionsetid
						AND c.assessmentid ='$aid'
						JOIN imas_questionset  ON a.questionsetid=imas_questionset.id
						AND (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0)
						WHERE a.questionsetid NOT IN ($existingqlist)
						GROUP BY a.questionsetid ORDER BY qcnt DESC LIMIT 100";
				}
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				if (mysql_num_rows($result)==0) {
					$noSearchResults = true;
				} else {
					$alt=0;
					$lastlib = -1;
					$i=0;
					$page_questionTable = array();
					$page_libstouse = array();
					$page_libqids = array();
					
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						if ($newonly && in_array($line['id'],$existingq)) {
							continue;
						}
						if ($lastlib!=$line['libid'] && isset($lnamesarr[$line['libid']])) {
							/*$page_questionTable[$i]['checkbox'] = "";
							$page_questionTable[$i]['desc'] = "<b>".$lnamesarr[$line['libid']]."</b>";
							$page_questionTable[$i]['preview'] = "";
							$page_questionTable[$i]['type'] = "";
							if ($searchall==1) 
								$page_questionTable[$i]['lib'] = "";
							$page_questionTable[$i]['times'] = "";
							$page_questionTable[$i]['mine'] = "";
							$page_questionTable[$i]['add'] = "";
							$page_questionTable[$i]['src'] = "";
							$page_questionTable[$i]['templ'] = "";
							$lastlib = $line['libid'];
							$i++;
							*/
							$page_libstouse[] = $line['libid'];
							$lastlib = $line['libid'];
							$page_libqids[$line['libid']] = array();
							
						} 
						
						if ($libsortorder[$line['libid']]==1) { //alpha
							$page_libqids[$line['libid']][$line['id']] = $line['description'];
						} else { //id
							$page_libqids[$line['libid']][] = $line['id'];
						}
						$i = $line['id'];
						$page_questionTable[$i]['checkbox'] = "<input type=checkbox name='nchecked[]' value='" . $line['id'] . "' id='qo$ln'>";
						$page_questionTable[$i]['desc'] = filter($line['description']);
						$page_questionTable[$i]['preview'] = "<input type=button value=\"Preview\" onClick=\"previewq('selq','qo$ln',{$line['id']},true,false)\"/>";
						$page_questionTable[$i]['type'] = $line['qtype'];
						if ($searchall==1) {
							$page_questionTable[$i]['lib'] = "<a href=\"addquestions.php?cid=$cid&aid=$aid&listlib={$line['libid']}\">List lib</a>";
						}
						/*$query = "SELECT COUNT(id) FROM imas_questions WHERE questionsetid='{$line['id']}'";
						$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
						$times = mysql_result($result2,0,0);
						$page_questionTable[$i]['times'] = $times;
						*/
						$page_questionTable[$i]['times'] = 0;
						
						if ($line['ownerid']==$userid) { 
							if ($line['userights']==0) {
								$page_questionTable[$i]['mine'] = "Private";
							} else {
								$page_questionTable[$i]['mine'] = "Yes";
							}
						} else {
							$page_questionTable[$i]['mine'] = "";
						}							
						
						
						$page_questionTable[$i]['add'] = "<a href=\"modquestion.php?qsetid={$line['id']}&aid=$aid&cid=$cid\">Add</a>";
						
						if ($line['userights']>2 || $line['ownerid']==$userid) {
							$page_questionTable[$i]['src'] = "<a href=\"moddataset.php?id={$line['id']}&aid=$aid&cid=$cid&frompot=1\">Edit</a>";
						} else { 
							$page_questionTable[$i]['src'] = "<a href=\"viewsource.php?id={$line['id']}&aid=$aid&cid=$cid\">View</a>";
						}							
						
						$page_questionTable[$i]['templ'] = "<a href=\"moddataset.php?id={$line['id']}&aid=$aid&cid=$cid&template=true\">Template</a>";						
						//$i++;
						$ln++;
							
					} //end while
					
					//pull question useage data
					if (count($page_questionTable)>0) {
						$allusedqids = implode(',', array_keys($page_questionTable));
						$query = "SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						while ($row = mysql_fetch_row($result)) {
							$page_questionTable[$row[0]]['times'] = $row[1];
						}
					}
					
					//sort alpha sorted libraries
					foreach ($page_libstouse as $libid) {
						if ($libsortorder[$libid]==1) {
							natcasesort($page_libqids[$libid]);
							$page_libqids[$libid] = array_keys($page_libqids[$libid]);
						}
					}
					
				}
			}
		
		}
		
	} else if ($sessiondata['selfrom'.$aid]=='assm') { //select from assessments

		if (isset($_GET['clearassmt'])) {
			unset($sessiondata['aidstolist'.$aid]);
		}
		if (isset($_POST['achecked'])) {
			if (count($_POST['achecked'])!=0) {
				$aidstolist = $_POST['achecked'];
				$sessiondata['aidstolist'.$aid] = $aidstolist;
				writesessiondata();
			}
		}
		
		if (isset($sessiondata['aidstolist'.$aid])) { //list questions

			$aidlist = "'".implode("','",addslashes_deep($sessiondata['aidstolist'.$aid]))."'";
			$query = "SELECT id,name,itemorder FROM imas_assessments WHERE id IN ($aidlist)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$aidnames[$row[0]] = $row[1];
				$items = str_replace('~',',',$row[2]);
				if ($items=='') {
					$aiditems[$row[0]] = array();
				} else {
					$aiditems[$row[0]] = explode(',',$items);
				}
			}
			$x=0;
			$page_assessmentQuestions = array();
			foreach ($sessiondata['aidstolist'.$aid] as $aidq) {
				$query = "SELECT imas_questions.id,imas_questionset.id,imas_questionset.description,imas_questionset.qtype,imas_questionset.ownerid,imas_questionset.userights FROM imas_questionset,imas_questions";
				$query .= " WHERE imas_questionset.id=imas_questions.questionsetid AND imas_questions.assessmentid='$aidq'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)==0) { //maybe defunct aid; if no questions in it, skip it
					continue;
				}
				while ($row = mysql_fetch_row($result)) {
					$qsetid[$row[0]] = $row[1];
					$descr[$row[0]] = $row[2];
					$qtypes[$row[0]] = $row[3];
					$owner[$row[0]] = $row[4];
					$userights[$row[0]] = $row[5];
					$query = "SELECT COUNT(id) FROM imas_questions WHERE questionsetid='{$row[1]}'";
					$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
					$times[$row[0]] = mysql_result($result2,0,0);
					
				}
				$page_assessmentQuestions['desc'][$x] = $aidnames[$aidq];
				$y=0;
				foreach($aiditems[$aidq] as $qid) {
					if (strpos($qid,'|')!==false) { continue;}
					$page_assessmentQuestions[$x]['checkbox'][$y] = "<input type=checkbox name='nchecked[]' id='qo$ln' value='" . $qsetid[$qid] . "'>";
					$page_assessmentQuestions[$x]['desc'][$y] = $descr[$qid];
					$page_assessmentQuestions[$x]['preview'][$y] = "<input type=button value=\"Preview\" onClick=\"previewq('selq','qo$ln',$qsetid[$qid],true)\"/>";
					$page_assessmentQuestions[$x]['type'][$y] = $qtypes[$qid];
					$page_assessmentQuestions[$x]['times'][$y] = $times[$qid];
					$page_assessmentQuestions[$x]['mine'][$y] = ($owner[$qid]==$userid) ? "Yes" : "" ;
					$page_assessmentQuestions[$x]['add'][$y] = "<a href=\"modquestion.php?qsetid=$qsetid[$qid]&aid=$aid&cid=$cid\">Add</a>";
					$page_assessmentQuestions[$x]['src'][$y] = ($userights[$qid]>2 || $owner[$qid]==$userid) ? "<a href=\"moddataset.php?id=$qsetid[$qid]&aid=$aid&cid=$cid&frompot=1\">Edit</a>" : "<a href=\"viewsource.php?id=$qsetid[$qid]&aid=$aid&cid=$cid\">View</a>" ;
					$page_assessmentQuestions[$x]['templ'][$y] = "<a href=\"moddataset.php?id=$qsetid[$qid]&aid=$aid&cid=$cid&template=true\">Template</a>";

					$ln++;
					$y++;
				}
				$x++;
			}
		} else {  //choose assessments

			$query = "SELECT id,name,summary FROM imas_assessments WHERE courseid='$cid' AND id<>'$aid' ORDER BY enddate,name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$i=0;
			$page_assessmentList = array();
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$page_assessmentList[$i]['id'] = $line['id'];
				$page_assessmentList[$i]['name'] = $line['name'];
				$line['summary'] = strip_tags($line['summary']);
				if (strlen($line['summary'])>100) {
					$line['summary'] = substr($line['summary'],0,97).'...';
				}
				$page_assessmentList[$i]['summary'] = $line['summary'];
				
				$i++;
			}
		}
	}
}


/******* begin html output ********/
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
	
//var_dump($jsarr);	
?>
	<script type="text/javascript">
		var curcid = <?php echo $cid ?>; 
		var curaid = <?php echo $aid ?>; 
		var defpoints = <?php echo $defpoints ?>;
		var AHAHsaveurl = 'http://<?php echo $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') ?>/addquestionssave.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>';
		var curlibs = '<?php echo $searchlibs;?>';
	</script>
	<script type="text/javascript" src="<?php echo $imasroot ?>/javascript/tablesorter.js"></script>

	<div class="breadcrumb"><?php echo $curBreadcrumb ?></div>

<?php	
	if ($beentaken) {
?>	
	<h3>Warning</h3>
	<p>This assessment has already been taken.  Adding or removing questions, or changing a 
		question's settings (point value, penalty, attempts) now would majorly mess things up.
		If you want to make these changes, you need to clear all existing assessment attempts
	</p>
	<p><input type=button value="Clear Assessment Attempts" onclick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&clearattempts=ask'">
	</p>
<?php
	}
?>	

	<div id="headeraddquestions" class="pagetitle"><h2>Add/Remove Questions 
		<img src="<?php echo $imasroot ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=addingquestionstoanassessment','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/>
	</h2></div>
	<h3>Questions in Assessment - <?php echo $page_assessmentName ?></h3>

<?php	
	if ($itemorder == '') {
		echo "<p>No Questions currently in assessment</p>\n";
		
		echo '<a href="#" onclick="this.style.display=\'none\';document.getElementById(\'helpwithadding\').style.display=\'block\';return false;">';
		echo "<img src=\"$imasroot/img/help.gif\" /> ";
		echo 'How do I find questions to add?</a>';
		echo '<div id="helpwithadding" style="display:none">';
		if ($sessiondata['selfrom'.$aid]=='lib') {
			echo "<p>You are currently set to select questions from the question libraries.  If you would like to select questions from ";
			echo "assessments you've already created, click the <b>Select From Assessments</b> button below</p>";
			echo "<p>To find questions to add from the question libraries:";
			echo "<ol><li>Click the <b>Select Libraries</b> button below to pop open the library selector</li>";
			echo " <li>In the library selector, open up the topics of interest, and click the checkbox to select libraries to use</li>";
			echo " <li>Scroll down in the library selector, and click the <b>Use Libraries</b> button</li> ";
			echo " <li>On this page, click the <b>Search</b> button to list the questions in the libraries selected.<br/>  You can limit the listing by entering a sepecific search term in the box provided first, or leave it blank to view all questions in the chosen libraries</li>";
			echo "</ol>";
		} else if ($sessiondata['selfrom'.$aid]=='assm') {
			echo "<p>You are currently set to select questions existing assessments.  If you would like to select questions from ";
			echo "the question libraries, click the <b>Select From Libraries</b> button below</p>";
			echo "<p>To find questions to add from existing assessments:";
			echo "<ol><li>Use the checkboxes to select the assessments you want to pull questions from</li>";
			echo " <li>Click <b>Use these Assessments</b> button to list the questions in the assessments selected</li>";
			echo "</ol>";
		}
		echo "<p>To select questions and add them:</p><ul>";
		echo " <li>Click the <b>Preview</b> button after the question description to view an example of the question</li>";
		echo " <li>Use the checkboxes to mark the questions you want to use</li>";
		echo " <li>Click the <b>Add</b> button above the question list to add the questions to your assessment</li> ";
		echo "  </ul>";
		echo '</div>';
			
	} else {
?>	
	<form id="curqform" method="post" action="addquestions.php?modqs=true&aid=<?php echo $aid ?>&cid=<?php echo $cid ?>">
<?php
		if (!$beentaken) {
?>		
		Use select boxes to 
		<select name=group id=group>
			<option value="0"<?php echo $grp0Selected ?>>Rearrange questions</option>
			<option value="1"<?php echo $grp1Selected ?>>Group questions</option>
		</select>
		With Selected: <input type=button value="Remove" onclick="removeSelected()" />
				<input type=button value="Group" onclick="groupSelected()" />
			  	<input type="submit" value="Change Settings" />
<?php			
		}
?>
		Check/Uncheck All: <input type="checkbox" name="ca1" value="ignore" onClick="chkAll(this.form, 'checked[]', this.checked)">
		<span id="submitnotice" style="color:red;"></span>
		<div id="curqtbl"></div>

	</form>
	<p>Assessment points total: <span id="pttotal"></span></p>
	<script>
		var itemarray = <?php echo $jsarr ?>; 
		var beentaken = <?php echo ($beentaken) ? 1:0; ?>; 
		document.getElementById("curqtbl").innerHTML = generateTable();
	</script>
<?php
	}
?>	
	<p>
		<input type=button value="Done" onClick="window.location='course.php?cid=<?php echo $cid ?>'"> 
		<input type=button value="Categorize Questions" onClick="window.location='categorize.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>'"> 
		<input type=button value="Create Print Version" onClick="window.location='printtest.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>'"> 
		<input type=button value="Define End Messages" onClick="window.location='assessendmsg.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>'">
		<input type=button value="Preview" onClick="window.open('<?php echo $imasroot;?>/assessment/showtest.php?cid=<?php echo $cid ?>&id=<?php echo $aid ?>','Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20))"> 
	</p>
		
<?php	
	//POTENTIAL QUESTIONS
	if ($sessiondata['selfrom'.$aid]=='lib') { //selecting from libraries
		if (!$beentaken) {
?>	
	
	<h3>Potential Questions</h3>
	<form method=post action="addquestions.php?aid=<?php echo $aid ?>&cid=<?php echo $cid ?>">

		In Libraries: 
		<span id="libnames"><?php echo $lnames ?></span>
		<input type=hidden name="libs" id="libs"  value="<?php echo $searchlibs ?>">
		<input type=button value="Select Libraries" onClick="libselect()">
		or <input type=button value="Select From Assessments" onClick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&selfrom=assm'">
		<br> 
		Search: 
		<input type=text size=15 name=search value="<?php echo $search ?>"> 
		<span onmouseover="tipshow(this,'Search all libraries, not just selected ones')" onmouseout="tipout()">
		<input type=checkbox name="searchall" value="1" <?php writeHtmlChecked($searchall,1,0) ?> />
		Search all libs</span> 
		<span onmouseover="tipshow(this,'List only questions I own')" onmouseout="tipout()">
		<input type=checkbox name="searchmine" value="1" <?php writeHtmlChecked($searchmine,1,0) ?> />
		Mine only</span> 
		<span onmouseover="tipshow(this,'Exclude questions already in assessment')" onmouseout="tipout()">
		<input type=checkbox name="newonly" value="1" <?php writeHtmlChecked($newonly,1,0) ?> />
		Exclude added</span> 
		<input type=submit value=Search>
		<input type=button value="Add New Question" onclick="window.location='moddataset.php?aid=<?php echo $aid ?>&cid=<?php echo $cid ?>'">
	</form>
<?php			
			if ($searchall==1 && trim($search)=='') {
				echo "Must provide a search term when searching all libraries";
			} elseif (isset($search)) {
				if ($noSearchResults) {
					echo "<p>No Questions matched search</p>\n";
				} else {
?>				
		<form id="selq" method=post action="addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&addset=true">
		
		
		Check/Uncheck All: 
		<input type="checkbox" name="ca2" value="1" onClick="chkAll(this.form, 'nchecked[]', this.checked)">
		<input name="add" type=submit value="Add" />
		<input name="addquick" type=submit value="Add (using defaults)">
		<input type=button value="Preview Selected" onclick="previewsel('selq')" />
		<table cellpadding=5 id=myTable class=gb>
			<thead>
				<tr><th></th><th>Description</th><th>Preview</th><th>Type</th>
					<?php echo $page_libRowHeader ?>
					<th>Times Used</th><th>Mine</th><th>Add</th><th>Source</th><th>Use as Template</th>
				</tr>
			</thead>
			<tbody>
<?php					
				$alt=0;
				for ($j=0; $j<count($page_libstouse); $j++) {
					if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
					echo '<td></td>';
					if ($searchall==1) {echo '<td colspan="9">';} else {echo '<td colspan="8">';}
					echo '<b>'.$lnamesarr[$page_libstouse[$j]].'</b>';
					echo '</td></tr>';
					
					for ($i=0;$i<count($page_libqids[$page_libstouse[$j]]); $i++) {
						$qid =$page_libqids[$page_libstouse[$j]][$i];
						if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>						

					<td><?php echo $page_questionTable[$qid]['checkbox'] ?></td>
					<td><?php echo $page_questionTable[$qid]['desc'] ?></td>
					<td><?php echo $page_questionTable[$qid]['preview'] ?></td>
					<td><?php echo $page_questionTable[$qid]['type'] ?></td>
<?php
						if ($searchall==1) {
?>					
					<td><?php echo $page_questionTable[$qid]['lib'] ?></td>
<?php
						}
?>
					<td class=c><?php echo $page_questionTable[$qid]['times'] ?></td>
					<td><?php echo $page_questionTable[$qid]['mine'] ?></td>
					<td class=c><?php echo $page_questionTable[$qid]['add'] ?></td>
					<td><?php echo $page_questionTable[$qid]['src'] ?></td>
					<td class=c><?php echo $page_questionTable[$qid]['templ'] ?></td>
					
				</tr>
<?php
					}
				}
?>					
			</tbody>
		</table>
		<script type="text/javascript">
			initSortTable('myTable',Array(false,'S',false,'S',<?php echo ($searchall==1) ? "false, " : ""; ?>'N','S',false,false,false),true);
		</script>
	</form>
	
<?php 					
				}
			}
		}	
		
	} else if ($sessiondata['selfrom'.$aid]=='assm') { //select from assessments
?>	

	<h3>Potential Questions</h3>

<?php
		if (isset($_POST['achecked']) && (count($_POST['achecked'])==0)) {
			echo "<p>No Assessments Selected.  Select at least one assessment.</p>";
		} elseif (isset($sessiondata['aidstolist'.$aid])) { //list questions
?>		
	<form id="selq" method=post action="addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&addset=true">
		
		<input type=button value="Select Assessments" onClick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&clearassmt=1'">
		or <input type=button value="Select From Libraries" onClick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&selfrom=lib'">
		<br/>
			
		Check/Uncheck All: 
		<input type="checkbox" name="ca2" value="1" onClick="chkAll(this.form, 'nchecked[]', this.checked)">
		<input name="add" type=submit value="Add" />
		<input name="addquick" type=submit value="Add Selected (using defaults)">
		<input type=button value="Preview Selected" onclick="previewsel('selq')" />
			
		<table cellpadding=5 id=myTable class=gb>
			<thead>
				<tr>
					<th> </th><th>Description</th><th>Preview</th><th>Type</th><th>Times Used</th><th>Mine</th><th>Add</th><th>Source</th><th>Use as Template</th>
				</tr>
			</thead>
			<tbody>
<?php			
			$alt=0;
			for ($i=0; $i<count($page_assessmentQuestions['desc']);$i++) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>				
				<td></td>
				<td><b><?php echo $page_assessmentQuestions['desc'][$i] ?></b></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
<?php
				for ($x=0;$x<count($page_assessmentQuestions[$i]['desc']);$x++) {
					if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>					
				<td><?php echo $page_assessmentQuestions[$i]['checkbox'][$x] ?></td>
				<td><?php echo $page_assessmentQuestions[$i]['desc'][$x] ?></td>
				<td><?php echo $page_assessmentQuestions[$i]['preview'][$x] ?></td>					
				<td><?php echo $page_assessmentQuestions[$i]['type'][$x] ?></td>
				<td class=c><?php echo $page_assessmentQuestions[$i]['times'][$x] ?></td>
				<td><?php echo $page_assessmentQuestions[$i]['mine'][$x] ?></td>
				<td class=c><?php echo $page_assessmentQuestions[$i]['add'][$x] ?></td>
				<td class=c><?php echo $page_assessmentQuestions[$i]['src'][$x] ?></td>
				<td class=c><?php echo $page_assessmentQuestions[$i]['templ'][$x] ?></td>
			</tr>
					
<?php
				}
			}
?>			
			</tbody>
		</table>

		<script type="text/javascript">
			initSortTable('myTable',Array(false,'S',false,'S','N','S',false,false,false),true);
		</script>
		</form>

<?php
		} else {  //choose assessments
?>		
		<h4>Choose assessments to take questions from</h4>
		<form method=post action="addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>">
		Check/Uncheck All: <input type="checkbox" name="ca2" value="1" onClick="chkAll(this.form, 'achecked[]', this.checked)" checked=1>
		<input type=submit value="Use these Assessments" /> or 
		<input type=button value="Select From Libraries" onClick="window.location='addquestions.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&selfrom=lib'">
			
		<table cellpadding=5 id=myTable class=gb>
			<thead>
			<tr><th></th><th>Assessment</th><th>Summary</th></tr>
			</thead>
			<tbody>
<?php

			$alt=0;
			for ($i=0;$i<count($page_assessmentList);$i++) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>

				<td><input type=checkbox name='achecked[]' value='<?php echo $page_assessmentList[$i]['id'] ?>' checked=1></td>
				<td><?php echo $page_assessmentList[$i]['name'] ?></td>
				<td><?php echo $page_assessmentList[$i]['summary'] ?></td>
			</tr>
<?php
			}
?>
			
			</tbody>
		</table>
		<script type=\"text/javascript\">
			initSortTable('myTable',Array(false,'S','S',false,false,false),true);
		</script>
	</form>

<?php			
		}
		
	}

}	

require("../footer.php");
?>
