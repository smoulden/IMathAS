<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Question Settings";

	
	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION	
	
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	$curBreadcrumb .= "&gt; <a href=\"addquestions.php?aid=$aid&cid=$cid\">Add/Remove Questions</a> &gt; ";
	$curBreadcrumb .= "Modify Question Settings";
	
	if ($_GET['process']== true) {
		if (trim($_POST['points'])=="") {$points=9999;} else {$points = intval($_POST['points']);}
		if (trim($_POST['attempts'])=="") {$attempts=9999;} else {$attempts = intval($_POST['attempts']);}
		if (trim($_POST['penalty'])=="") {$penalty=9999;} else {$penalty = intval($_POST['penalty']);}
		if ($penalty!=9999) {
			if ($_POST['skippenalty']==10) {
				$penalty = 'L'.$penalty;
			} else if ($_POST['skippenalty']>0) {
				$penalty = 'S'.$_POST['skippenalty'].$penalty;
			}
		}
		$regen = $_POST['regen'] + 3*$_POST['allowregen'];
		$showans = $_POST['showans'];
		if (isset($_GET['id'])) { //already have id - updating
			$query = "UPDATE imas_questions SET points='$points',attempts='$attempts',penalty='$penalty',regen='$regen',showans='$showans' ";
			$query .= "WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (isset($_POST['copies']) && $_POST['copies']>0) {
				$query = "SELECT questionsetid FROM imas_questions WHERE id='{$_GET['id']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$_GET['qsetid'] = mysql_result($result,0,0);
			}
		} 
		if (isset($_GET['qsetid'])) { //new - adding
			$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemorder = mysql_result($result,0,0);
			for ($i=0;$i<$_POST['copies'];$i++) {
				$query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,regen,showans,questionsetid) ";
				$query .= "VALUES ('$aid','$points','$attempts','$penalty','$regen','$showans','{$_GET['qsetid']}')";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$qid = mysql_insert_id();
				
				//add to itemorder
				if (isset($_GET['id'])) { //am adding copies of existing  
					$itemarr = explode(',',$itemorder);
					$key = array_search($_GET['id'],$itemarr);
					array_splice($itemarr,$key+1,0,$qid);
					$itemorder = implode(',',$itemarr);
				} else {
					if ($itemorder=='') {
						$itemorder = $qid;
					} else {
						$itemorder = $itemorder . ",$qid";	
					}
				}
			}
			$query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
		exit;
	} else { //DEFAULT DATA MANIPULATION

		if (isset($_GET['id'])) {
			$query = "SELECT points,attempts,penalty,regen,showans FROM imas_questions WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			if ($line['penalty']{0}==='L') {
				$line['penalty'] = substr($line['penalty'],1);
				$skippenalty==10;
			} else if ($line['penalty']{0}==='S') {
				$skippenalty = $line['penalty']{1};
				$line['penalty'] = substr($line['penalty'],2);
			} else {
				$skippenalty = 0;
			}
			
			if ($line['points']==9999) {$line['points']='';}
			if ($line['attempts']==9999) {$line['attempts']='';}
			if ($line['penalty']==9999) {$line['penalty']='';}
		} else {
			//set defaults
			$line['points']="";
			$line['attempts']="";
			$line['penalty']="";
			$skippenalty = 0;
			$line['regen']=0;
			$line['showans']='0';
		}
		
		$query = "SELECT ias.id FROM imas_assessment_sessions AS ias,imas_students WHERE ";
		$query .= "ias.assessmentid='$aid' AND ias.userid=imas_students.userid AND imas_students.courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result) > 0) {
			$page_beenTakenMsg = "<h3>Warning</h3>\n";
			$page_beenTakenMsg .= "<p>This assessment has already been taken.  Altering the points or penalty will not change the scores of students who already completed this question. ";
			$page_beenTakenMsg .= "If you want to make these changes, or add additional copies of this question, you should clear all existing assessment attempts</p> ";
			$page_beenTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearattempts=ask'\"></p>\n";
			$beentaken = true;
		} else {
			$beentaken = false;
		}

	}
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {		
?>
	<div class="breadcrumb"><?php echo $curBreadcrumb; ?></div> 
	<?php echo $page_beenTakenMsg; ?>		


<div id="headermodquestion" class="pagetitle"><h2>Modify Question Settings</h2></div> 
<form method=post action="modquestion.php?process=true&<?php echo "cid=$cid&aid=$aid"; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";} if (isset($_GET['qsetid'])) {echo "&qsetid={$_GET['qsetid']}";}?>">
Leave items blank to use the assessment's default values<br/>

<span class=form>Points for this problem:</span><span class=formright> <input type=text size=4 name=points value="<?php echo $line['points'];?>"></span><BR class=form>

<span class=form>Attempts allowed for this problem (0 for unlimited):</span><span class=formright> <input type=text size=4 name=attempts value="<?php echo $line['attempts'];?>"></span><BR class=form>

<span class=form>Default penalty:</span><span class=formright><input type=text size=4 name=penalty value="<?php echo $line['penalty'];?>">% 
   <select name="skippenalty" <?php if ($taken) {echo 'disabled=disabled';}?>>
     <option value="0" <?php if ($skippenalty==0) {echo "selected=1";} ?>>per missed attempt</option>
     <option value="1" <?php if ($skippenalty==1) {echo "selected=1";} ?>>per missed attempt, after 1</option>
     <option value="2" <?php if ($skippenalty==2) {echo "selected=1";} ?>>per missed attempt, after 2</option>
     <option value="3" <?php if ($skippenalty==3) {echo "selected=1";} ?>>per missed attempt, after 3</option>
     <option value="4" <?php if ($skippenalty==4) {echo "selected=1";} ?>>per missed attempt, after 4</option>
     <option value="5" <?php if ($skippenalty==5) {echo "selected=1";} ?>>per missed attempt, after 5</option>
     <option value="6" <?php if ($skippenalty==6) {echo "selected=1";} ?>>per missed attempt, after 6</option>
     <option value="10" <?php if ($skippenalty==10) {echo "selected=1";} ?>>on last possible attempt only</option>
     </select></span><BR class=form>

<span class=form>New version on reattempt?</span><span class=formright>
    <select name="regen">
     <option value="0" <?php if (($line['regen']%3)==0) { echo 'selected="1"';}?>>Use Default</option>
     <option value="1" <?php if (($line['regen']%3)==1) { echo 'selected="1"';}?>>Yes, new version on reattempt</option>
     <option value="2" <?php if (($line['regen']%3)==2) { echo 'selected="1"';}?>>No, same version on reattempt</option>
    </select></span><br class="form"/>
    
<span class="form">Allow &quot;Try similar problem&quot;?</span>
<span class=formright>
    <select name="allowregen">
     <option value="0" <?php if ($line['regen']<3) { echo 'selected="1"';}?>>Use Default</option>
     <option value="1" <?php if ($line['regen']>=3) { echo 'selected="1"';}?>>No</option>
</select></span><br class="form"/>

<span class=form>Show Answers</span><span class=formright>
    <select name="showans">
     <option value="0" <?php if ($line['showans']=='0') { echo 'selected="1"';}?>>Use Default</option>
     <option value="N" <?php if ($line['showans']=='N') { echo 'selected="1"';}?>>Never during assessment</option>
     <option value="F" <?php if ($line['showans']=='F') { echo 'selected="1"';}?>>Show answer after last attempt</option>
    </select></span><br class="form"/>

<?php
	if (isset($_GET['qsetid'])) { //adding new question
		echo "<span class=form>Number of copies of question to add:</span><span class=formright><input type=text size=4 name=copies value=\"1\"/></span><br class=form />";
	} else if (!$beentaken) {
		echo "<span class=form>Number, if any, of additional copies to add to assessment:</span><span class=formright><input type=text size=4 name=copies value=\"0\"/></span><br class=form />";
	}
?>

<div class=submit><input type=submit value=Submit></div>

<?php
}

require("../footer.php");
?>
