<?php
//IMathAS:  Upload grade page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

	
 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Upload Grades";
	
	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION	
	
	$cid = $_GET['cid'];
	
	if (isset($_FILES['userfile']['name']) && $_FILES['userfile']['name']!='') {
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
			$curscores = array();
			$query = "SELECT userid,score FROM imas_grades WHERE gbitemid='{$_GET['gbitem']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$curscores[$row[0]] = $row[1];
			}
			
			$failures = array();
			$successes = 0;
			
			if ($_POST['useridtype']==0) {
				$usercol = $_POST['usernamecol']-1;
			} else if ($_POST['useridtype']==1) {
				$usercol = $_POST['fullnamecol']-1;
			}
			$scorecol = $_POST['gradecol']-1;
			$feedbackcol = $_POST['feedbackcol']-1;
			
			$handle = fopen($_FILES['userfile']['tmp_name'],'r');
			if ($_POST['hashdr']==1) {
				$data = fgetcsv($handle,4096,',');
			} else if ($_POST['hashdr']==2) {
				$data = fgetcsv($handle,4096,',');
				$data = fgetcsv($handle,4096,',');
			}
			while (($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
				$query = "SELECT imas_users.id FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid AND imas_students.courseid='$cid' AND ";
				if ($_POST['useridtype']==0) {
					$query .= "imas_users.SID='{$data[$usercol]}'";
				} else if ($_POST['useridtype']==1) {
					list($last,$first) = explode(',',$data[$usercol]);
					$first = str_replace(' ','',$first);
					$last = str_replace(' ','',$last);
					$query .= "imas_users.FirstName='$first' AND imas_users.LastName='$last'";
					//echo $query;
				} else {
					$query .= "0";
				}
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if ($feedbackcol==-1) {
					$feedback = '';
				} else {
					$feedback = addslashes($data[$feedbackcol]);
				}
				$score = addslashes($data[$scorecol]);
				if (mysql_num_rows($result)>0) {
					$cuserid=mysql_result($result,0,0);
					if (isset($curscores[$cuserid])) {
						$query = "UPDATE imas_grades SET score='$score',feedback='$feedback' WHERE userid='$cuserid' AND gbitemid='{$_GET['gbitem']}'";
						$successes++;
					} else {
						$query = "INSERT INTO imas_grades (gbitemid,userid,score,feedback) VALUES ";
						$query .= "('{$_GET['gbitem']}','$cuserid','$score','$feedback')";
						$successes++;
					}
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else {
					$failures[] = $data[$usercol];
				}
			}
			
			$overwriteBody = 1;
			$body = "<p>Grades uploaded.  $successes records.</p> ";
			if (count($failures)>0) {
				$body .= "<p>Grade upload failure on: <br/>";
				$body .= implode('<br/>',$failures);
				$body .= '</p>';
			}
			if ($successes>0) {
				$body .= "<a href=\"addgrades.php?stu=0&gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}&grades=all\">Return to grade list</a></p>";
			}
			
		} else {
			$overwriteBody = 1;
			$body = "File Upload error";
		}
	} else { //DEFAULT DATA MANIPULATION
		$curBreadcrumb ="$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		$curBreadcrumb .=" &gt; <a href=\"gradebook.php?stu=0&gbmode={$_GET['gbmode']}&cid=$cid\">Gradebook</a> ";
		$curBreadcrumb .=" &gt; <a href=\"addgrades.php?stu=0&gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}&grades=all\">Offline Grades</a> &gt; Upload Grades";
	}
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {			
?>
	
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	
	<div id="headeruploadgrades" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
	
	
	<form enctype="multipart/form-data" method=post action="uploadgrades.php?cid=<?php echo $cid ?>&gbmode=<?php echo $_GET['gbmode'] ?>&gbitem=<?php echo $_GET['gbitem'] ?>">
		<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
		<span class=form>Grade file (CSV): </span>
		<span class=formright><input name="userfile" type="file" /></span><br class=form>
		<span class=form>File has header row?</span>
		<span class=formright>
			<input type=radio name="hashdr" value="0" checked=1 />No header<br/>
			<input type=radio name="hashdr" value="1" />Has 1 row header <br/>
			<input type=radio name="hashdr" value="2" />Has 2 row header <br/>
		</span><br class="form" />
		<span class=form>Grade is in column:</span>
		<span class=formright><input type=text size=4 name="gradecol" value="2"/></span><br class="form" />
		<span class=form>Feedback is in column (0 if none):</span>
		<span class=formright><input type=text size=4 name="feedbackcol" value="0"/></span><br class="form" />
		<span class=form>User is identified by:</span>
		<span class=formright>
			<input type=radio name="useridtype" value="0" checked=1 />Username (login name) in column 
			<input type=text size=4 name="usernamecol" value="2" /><br/>
			<input type=radio name="useridtype" value="1" />Lastname, Firstname in column 
			<input type=text size=4 name="fullnamecol" value="1" />
		</span><br class="form" />
	
		<div class=submit><input type=submit value="Submit"></div>

	</form>
	
<?php	
}
	
require("../footer.php");
	
?>
