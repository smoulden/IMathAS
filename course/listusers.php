<?php
//var_dump($_GET);
//var_dump($_POST);
//IMathAS:  Main course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$cid = $_GET['cid'];
if (isset($_GET['secfilter'])) {
	$secfilter = $_GET['secfilter'];
	$sessiondata[$cid.'secfilter'] = $secfilter;
	writesessiondata();
} else if (isset($sessiondata[$cid.'secfilter'])) {
	$secfilter = $sessiondata[$cid.'secfilter'];
} else {
	$secfilter = -1;
}
$overwriteBody = 0;
$body = "";
$pagetitle = "";
$hasInclude = 0;
if (!isset($CFG['GEN']['allowinstraddstus'])) {
	$CFG['GEN']['allowinstraddstus'] = true;
}
if (!isset($CFG['GEN']['allowinstraddtutors'])) {
	$CFG['GEN']['allowinstraddtutors'] = true;
}
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";

if (!isset($teacherid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	
	if (isset($_POST['submit']) && $_POST['submit']=="Unenroll") {
		$_GET['action'] = "unenroll";
	}
	
	if (isset($_GET['assigncode'])) {
		
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Assign Codes\n";
		$pagetitle = "Assign Section/Code Numbers";
		
		if (isset($_POST['submit'])) {
			$keys = array_keys($_POST['sec']);
			foreach ($keys as $stuid) {
				if ($_POST['sec'][$stuid]=='') {
					$_POST['sec'][$stuid] = "NULL";
				} else {
					$_POST['sec'][$stuid] = "'".$_POST['sec'][$stuid]."'";
				}
				if ($_POST['code'][$stuid]=='') {
					$_POST['code'][$stuid] = "NULL";
				} else {
					$_POST['code'][$stuid] = intval($_POST['code'][$stuid]);
				}
			}
			foreach ($keys as $stuid) {
				$query = "UPDATE imas_students SET section={$_POST['sec'][$stuid]},code={$_POST['code'][$stuid]} WHERE id='$stuid' AND courseid='$cid' ";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
	
		} else {
			$query = "SELECT imas_students.id,imas_users.FirstName,imas_users.LastName,imas_students.section,imas_students.code ";
			$query .= "FROM imas_students,imas_users WHERE imas_students.courseid='$cid' AND imas_students.userid=imas_users.id ";
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
			$resultStudentList = mysql_query($query) or die("Query failed : " . mysql_error());
		}
	} elseif (isset($_GET['enroll'])) {

		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Enroll Students\n";
		$pagetitle = "Enroll an Existing User";
		
		if (isset($_POST['username'])) {
			$query = "SELECT id FROM imas_users WHERE SID='{$_POST['username']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==0) {
				$overwriteBody = 1;
				$body = "Error, username doesn't exist. <a href=\"listusers.php?cid=$cid&enroll=student\">Try again</a>\n";
			} else {
				$id = mysql_result($result,0,0);
				if ($id==$userid) {
					echo "Instructors can't enroll themselves as students.  Use Student View.";
					exit;
				}
				$vals = "$id,'$cid'";
				$query = "INSERT INTO imas_students (userid,courseid";
				if (trim($_POST['section'])!='') {
					$query .= ",section";
					$vals .= ",'".$_POST['section']."'";
				}
				if (trim($_POST['code'])!='') {
					$query .= ",code";
					$vals .= ",'".$_POST['code']."'";
				}
				$query .= ") VALUES ($vals)";
				mysql_query($query) or die("Query failed : " . mysql_error());
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
				exit;
			}
			
		} 
	} elseif (isset($_GET['newstu']) && $CFG['GEN']['allowinstraddstus']) {
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Enroll Students\n";
		$pagetitle = "Enroll a New Student";	
	
		if (isset($_POST['SID'])) {
			$query = "SELECT id FROM imas_users WHERE SID='{$_POST['SID']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$overwriteBody = 1;
				$body = "$loginprompt '{$_POST['SID']}' is used.  <a href=\"listusers.php?cid=$cid&newstu=new\">Try Again</a>\n";
			} else {
				$md5pw = md5($_POST['pw1']);
				$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify) ";
				$query .= "VALUES ('{$_POST['SID']}','$md5pw',10,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',0);";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$newuserid = mysql_insert_id();
				//$query = "INSERT INTO imas_students (userid,courseid) VALUES ($newuserid,'$cid')";
				$vals = "$newuserid,'$cid'";
				$query = "INSERT INTO imas_students (userid,courseid";
				if (trim($_POST['section'])!='') {
					$query .= ",section";
					$vals .= ",'".$_POST['section']."'";
				}
				if (trim($_POST['code'])!='') {
					$query .= ",code";
					$vals .= ",'".$_POST['code']."'";
				}
				$query .= ") VALUES ($vals)";
				mysql_query($query) or die("Query failed : " . mysql_error());
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
				exit;
			}
		} 
	} elseif (isset($_GET['chgstuinfo'])) {
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Enroll Students\n";	
		$pagetitle = "Change Student Info";
		
		if (isset($_POST['firstname'])) {
			$un = preg_replace('/[^\w\.@]*/','',$_POST['username']);
			$updateusername = true;
			$query = "SELECT id FROM imas_users WHERE SID='$un'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$updateusername = false;
			}
			$query = "UPDATE imas_users SET FirstName='{$_POST['firstname']}',LastName='{$_POST['lastname']}',email='{$_POST['email']}'";
			if ($updateusername) {
				$query .= ",SID='$un'";
			}
			if (isset($_POST['doresetpw'])) {
				$newpw = md5($_POST['password']);
				$query .= ",password='$newpw'";
			}
			
			$query .= " WHERE id='{$_GET['uid']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			
			$code = "'{$_POST['code']}'";
			$section = "'{$_POST['section']}'";
			if (trim($_POST['section'])==='') {
				$section = "NULL";
			}
			if (trim($_POST['code'])==='') {
				$code = "NULL";
			}
			if (isset($_POST['locked'])) {
				$locked = 1;
			} else {
				$locked = 0;
			}
			$timelimitmult = floatval($_POST['timelimitmult']);
			//echo $timelimitmult;
			if ($timelimitmult <= 0) {
				$timelimitmult = '1.0';
			} 
			//echo $timelimitmult;
				
			$query = "UPDATE imas_students SET code=$code,section=$section,locked=$locked,timelimitmult='$timelimitmult' WHERE userid='{$_GET['uid']}' AND courseid='$cid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		
			require('../includes/userpics.php');
			if (is_uploaded_file($_FILES['stupic']['tmp_name'])) {
				processImage($_FILES['stupic'],$_GET['uid'],100,100);
				processImage($_FILES['stupic'],'sm'.$_GET['uid'],40,40);
			} else if (isset($_POST['removepic'])) {
				$curdir = rtrim(dirname(__FILE__), '/\\');
				$galleryPath = "$curdir/../course/files/";
				if (file_exists($galleryPath.'userimg_'.$_GET['uid'].'.jpg')) {
					unlink($galleryPath.'userimg_'.$_GET['uid'].'.jpg');
					unlink($galleryPath.'userimg_sm'.$_GET['uid'].'.jpg');
				}
			}
			
			require("../header.php");
			echo "<p>User info updated. ";
			if ($updateusername) {
				echo "User login changed to $un.";
			} else {
				echo "User login left unchanged.";
			}
			if (isset($_POST['doresetpw'])) {
				echo "  Password changed.";
			}
			echo "</p><p><a href=\"listusers.php?cid=$cid\">OK</a></p>";
			require("../footer.php");
			
			//header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else {
			$query = "SELECT imas_users.*,imas_students.code,imas_students.section,imas_students.locked,imas_students.timelimitmult FROM imas_users,imas_students ";
			$query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id='{$_GET['uid']}' AND imas_students.courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$lineStudent = mysql_fetch_array($result, MYSQL_ASSOC);
			
		}
		
	} elseif ((isset($_POST['submit']) && ($_POST['submit']=="E-mail" || $_POST['submit']=="Message"))|| isset($_GET['masssend']))  {
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "masssend.php";
	} elseif ((isset($_POST['submit']) && $_POST['submit']=="Make Exception") || isset($_GET['massexception'])) {
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "massexception.php";
	} elseif (isset($_GET['action']) && $_GET['action']=="resetpw") {
		if (isset($_GET['confirmed'])) {
			$newpw = "5f4dcc3b5aa765d61d8327deb882cf99";  //md5("password")
			$query = "UPDATE imas_users SET password='$newpw' WHERE id='{$_GET['uid']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n"; 
			$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Confirm Change\n";
			$pagetitle = "Confirm Change";
		}
	} elseif (isset($_GET['action']) && $_GET['action']=="unenroll" ) {
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n"; 
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Confirm Change\n";
		$pagetitle = "Unenroll Students";		
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "unenroll.php";
		
	} else { //DEFAULT DATA MANIPULATION HERE
	 
		$curBreadcrumb .= " &gt; Roster\n";
		$pagetitle = "Student Roster";
		
		$query = "SELECT DISTINCT section FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.section IS NOT NULL ORDER BY section";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			$hassection = true;
			$sectionselect = "<br/><select id=\"secfiltersel\" onchange=\"chgsecfilter()\"><option value=\"-1\" ";
			if ($secfilter==-1) {$sectionselect .= 'selected=1';}
			$sectionselect .=  '>All</option>';
			while ($row = mysql_fetch_row($result)) {
				$sectionselect .=  "<option value=\"{$row[0]}\" ";
				if ($row[0]==$secfilter) {
					$sectionselect .=  'selected=1';
				}
				$sectionselect .=  ">{$row[0]}</option>";
			}
			$sectionselect .=  "</select>";
		} else {
			$hassection = false;
		}
		$query = "SELECT count(id) FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.code IS NOT NULL";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			$hascode = true;
		} else {
			$hascode = false;
		}	
		
		$query = "SELECT imas_students.id,imas_students.userid,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.SID,imas_students.lastaccess,imas_students.section,imas_students.code,imas_students.locked ";
		$query .= "FROM imas_students,imas_users WHERE imas_students.courseid='$cid' AND imas_students.userid=imas_users.id ";
		if ($secfilter>-1) {
			$query .= "AND imas_students.section='$secfilter' ";			
		}
		if ($hassection) {
			$query .= "ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$resultDefaultUserList = mysql_query($query) or die("Query failed : " . mysql_error());
		$hasSectionRowHeader = ($hassection)? "<th>Section$sectionselect</th>" : "";
		$hasCodeRowHeader = ($hascode) ? "<th>Code</th>" : "";
		$hasSectionSortTable = ($hassection) ? "'S'," : "";
		$hasCodeSortTable = ($hascode) ? "'N'," : "";
	
	}
} //END DATA MANIPULATION
	
//$pagetitle = "Student List";	

/******* begin html output ********/
if ($fileToInclude==null || $fileToInclude=="") {

$placeinhead .= "<script type=\"text/javascript\">";
$placeinhead .= 'function chgsecfilter() { ';
$placeinhead .= '       var sec = document.getElementById("secfiltersel").value; ';
$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid";
$placeinhead .= "       var toopen = '$address&secfilter=' + sec;\n";
$placeinhead .= "  	window.location = toopen; \n";
$placeinhead .= "}\n";
$placeinhead .= "</script>";

require("../header.php");
$curdir = rtrim(dirname(__FILE__), '/\\');
}
/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	if (strlen($body)<2) {
		include("./$fileToInclude");
	} else {
		echo $body;
	}
} else {	
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerlistusers" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php

	if (isset($_GET['assigncode'])) {
?>
	<form method=post action="listusers.php?cid=<?php echo $cid ?>&assigncode=1">
		<table class=gb>
			<thead>
			<tr>
				<th>Name</th><th>Section</th><th>Code</th>
			</tr>
			</thead>
			<tbody>
<?php	
		while ($line=mysql_fetch_array($resultStudentList, MYSQL_ASSOC)) {
?>
			<tr>
				<td><?php echo $line['LastName'] . ", " . $line['FirstName'] ?></td>
				<td><input type=text name="sec[<?php echo $line['id'] ?>]" value="<?php echo $line['section'] ?>"/></td>
				<td><input type=text name="code[<?php echo $line['id'] ?>]" value="<?php echo $line['code'] ?>"/></td>
			</tr>
<?php
		}
?>
			</tbody>
		</table>
		<input type=submit name=submit value="Submit"/>
	</form>
<?php			
	} elseif (isset($_GET['enroll'])) {
?>
	<form method=post action="listusers.php?enroll=student&cid=<?php echo $cid ?>">
		<span class=form>Username to enroll:</span>
		<span class=formright><input type="text" name="username"></span><br class=form>
		<span class=form>Section (optional):</span>
		<span class=formright><input type="text" name="section"></span><br class=form>
		<span class=form>Code (optional):</span>
		<span class=formright><input type="text" name="code"></span><br class=form>
		<div class=submit><input type="submit" value="Enroll"></div>
	</form>
<?php
	} elseif (isset($_GET['newstu'])) {
?>
	
	<form method=post action="listusers.php?cid=<?php echo $cid ?>&newstu=new">
		<span class=form><label for="SID"><?php echo $loginprompt;?>:</label></span> <input class=form type=text size=12 id=SID name=SID><BR class=form>
	<span class=form><label for="pw1">Choose a password:</label></span><input class=form type=password size=20 id=pw1 name=pw1><BR class=form>
	<span class=form><label for="firstname">Enter First Name:</label></span> <input class=form type=text size=20 id=firstnam name=firstname><BR class=form>
	<span class=form><label for="lastname">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname><BR class=form>
	<span class=form><label for="email">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email><BR class=form>
	<span class=form>Section (optional):</span>
		<span class=formright><input type="text" name="section"></span><br class=form>
	<span class=form>Code (optional):</span>
		<span class=formright><input type="text" name="code"></span><br class=form>
	<div class=submit><input type=submit value="Create and Enroll"></div>
	</form>

<?php 
	} elseif (isset($_GET['chgstuinfo'])) {
?>
		<form enctype="multipart/form-data" method=post action="listusers.php?cid=<?php echo $cid ?>&chgstuinfo=true&uid=<?php echo $_GET['uid'] ?>"/>
			<span class=form><label for="username">Enter User Name (login name):</label></span>
			<input class=form type=text size=20 id=username name=username value="<?php echo $lineStudent['SID'] ?>"/><br class=form>
			<span class=form><label for="firstname">Enter First Name:</label></span>
			<input class=form type=text size=20 id=firstname name=firstname value="<?php echo $lineStudent['FirstName'] ?>"/><br class=form>
			<span class=form><label for="lastname">Enter Last Name:</label></span>
			<input class=form type=text size=20 id=lastname name=lastname value="<?php echo $lineStudent['LastName'] ?>"/><BR class=form>
			<span class=form><label for="email">Enter E-mail address:</label></span>
			<input class=form type=text size=60 id=email name=email value="<?php echo $lineStudent['email'] ?>"/><BR class=form>
			<span class=form><label for="stupic">Picture:</label></span>
			<span class="formright">
			<?php
		$curdir = rtrim(dirname(__FILE__), '/\\');
		$galleryPath = "$curdir/../course/files/";
		if (file_exists($galleryPath.'userimg_'.$_GET['uid'].'.jpg')) {
			echo "<img src=\"$imasroot/course/files/userimg_{$_GET['uid']}.jpg\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> Remove ";
		} else {
			echo "No Pic ";
		}
		?>
			<br/><input type="file" name="stupic"/></span><br class="form" />
			<span class=form>Section (optional):</span>
			<span class=formright><input type="text" name="section" value="<?php echo $lineStudent['section'] ?>"/></span><br class=form>
			<span class=form>Code (optional):</span>
			<span class=formright><input type="text" name="code" value="<?php echo $lineStudent['code'] ?>"/></span><br class=form>
			<span class=form>Time Limit Multiplier:</span>
			<span class=formright><input type="text" name="timelimitmult" value="<?php echo $lineStudent['timelimitmult'] ?>"/></span><br class=form>
			<span class=form>Lock out of course?:</span>
			<span class=formright><input type="checkbox" name="locked" value="1" <?php if ($lineStudent['locked']==1) {echo ' checked="checked" ';} ?>/></span><br class=form>
			<span class=form>Reset password?</span>
			<span class=formright>
				<input type=checkbox name="doresetpw" value="1" /> Reset to: 
				<input type=text size=20 name="password" />
			</span><br class=form />
			<div class=submit><input type=submit value="Update Info"></div>
		</form>

<?php		
	} elseif (isset($_GET['action']) && $_GET['action']=="resetpw") {
?>
		<form method=post action="listusers.php?cid=<?php echo $cid ?>&action=<?php echo $_GET['action'] ?>&uid=<?php echo $_GET['uid'] ?>&confirmed=true">

		Are you sure you want to reset this student's password	
		
		<p>
			<input type=submit value="Yes, I'm Sure">
			<input type=button value="Nevermind" onclick="window.location='listusers.php?cid=<?php echo $cid ?>'">
		</p>
	</form>

<?php		
	} else {
?>	
	
	<script type="text/javascript">
	function chkAll(frm, arr, mark) {
	  for (i = 0; i <= frm.elements.length; i++) {
	   try{
	     if(frm.elements[i].name == arr) {
	       frm.elements[i].checked = mark;
	     }
	   } catch(er) {}
	  }
	}
	var picsize = 0;
	function rotatepics() {
		picsize = (picsize+1)%3;
		picshow(picsize);
	}
	function picshow(size) {
		if (size==0) {
			els = document.getElementById("myTable").getElementsByTagName("img");
			for (var i=0; i<els.length; i++) {
				els[i].style.display = "none";
			}
		} else {
			els = document.getElementById("myTable").getElementsByTagName("img");
			for (var i=0; i<els.length; i++) {
				els[i].style.display = "inline";
				if (els[i].getAttribute("src").match("userimg_sm")) {
					if (size==2) {
						els[i].setAttribute("src",els[i].getAttribute("src").replace("_sm","_"));
					}
				} else if (size==1) {
					els[i].setAttribute("src",els[i].getAttribute("src").replace("_","_sm"));
				}
			}
		}
	}
	</script>
	<script type="text/javascript" src="<?php echo $imasroot ?>/javascript/tablesorter.js"></script>
	<form method=post action="listusers.php?cid=<?php echo $cid ?>">
		Check/Uncheck All: 
		<input type="checkbox" name="ca" value="1" onClick="chkAll(this.form, 'checked[]', this.checked)"> 
		With Selected:  
		<input type=submit name=submit value="E-mail">
		<input type=submit name=submit value="Message"> 
		<input type=submit name=submit value="Unenroll"> 
		<input type=submit name=submit value="Make Exception">
		<input type="button" value="Pictures" onclick="rotatepics()" />
		
	<table class=gb id=myTable>
		<thead>
		<tr>
			<th></th>
			<th></th>
			<?php echo $hasSectionRowHeader; ?>
			<?php echo $hasCodeRowHeader; ?>
			<th>Last</th>
			<th>First</th>
			<th>Email</th>
			<th><?php echo $loginprompt ?></th>
			<th>Last Access</th>
			<th>Grades</th>
			<th>Due Dates</th>
			<th>Chg Info</th>
			<th>Unenroll</th>
		</tr>
		</thead>
		<tbody>	
<?php		
		$alt = 0;
		$numstu = 0;
		while ($line=mysql_fetch_array($resultDefaultUserList, MYSQL_ASSOC)) {
			$numstu++;
			if ($line['locked']==1) {
				$lastaccess = "locked";
			} else {
				$lastaccess = ($line['lastaccess']>0) ? date("n/j/y g:ia",$line['lastaccess']) : "never";
			}
			$hasSectionData = ($hassection) ? "<td>{$line['section']}</td>" : "";
			$hasCodeData = ($hascode) ? "<td>{$line['code']}</td>" : "";
			if ($alt==0) {echo "			<tr class=even>"; $alt=1;} else {echo "			<tr class=odd>"; $alt=0;}
?>			
				<td><input type=checkbox name="checked[]" value="<?php echo $line['userid'] ?>"></td>
				<td>
<?php
	
	if (file_exists("$curdir/files/userimg_sm{$line['userid']}.jpg")) {
		echo "<img src=\"$imasroot/course/files/userimg_sm{$line['userid']}.jpg\" style=\"display:none;\"  />";
	}
?>
				</td>
				<?php echo $hasSectionData; ?>
				<?php echo $hasCodeData; ?>
				<td><?php echo $line['LastName'] ?></td>
				<td><?php echo $line['FirstName'] ?></td>
				<td><a href="mailto:<?php echo $line['email'] ?>"><?php echo $line['email'] ?></a></td>
				<td><?php echo $line['SID'] ?></td>
				<td><?php echo $lastaccess ?></td>
				<td><a href="gradebook.php?cid=<?php echo $cid ?>&stu=<?php echo $line['userid'] ?>&from=listusers">Grades</a></td>
				<td><a href="listusers.php?cid=<?php echo $cid ?>&uid=<?php echo $line['userid'] ?>&massexception=1">Exception</a></td>
				<td><a href="listusers.php?cid=<?php echo $cid ?>&chgstuinfo=true&uid=<?php echo $line['userid'] ?>">Chg</a></td>
				<td><a href="listusers.php?cid=<?php echo $cid ?>&action=unenroll&uid=<?php echo $line['userid'] ?>">Unenroll</a></td>
			</tr>
<?php
		}
?>		

			</tbody>
		</table>
<?php
		echo "Number of students: $numstu<br/>";
?>
		<script type="text/javascript">
			initSortTable('myTable',Array(false,false,<?php echo $hasSectionSortTable ?><?php echo $hasCodeSortTable ?>'S','S','S','S','D',false,false,false),true);
		</script>
	</form>
		

	<div class=cp>
	<?php
	echo "<a href=\"listusers.php?cid=$cid&enroll=student\">Enroll Student with known username</a><br/>";
	if ($CFG['GEN']['allowinstraddstus']) { 
		echo "<a href=\"$imasroot/admin/importstu.php?cid=$cid\">Import Students from File</a><br/>";
		echo "<a href=\"listusers.php?cid=$cid&newstu=new\">Create and Enroll new student</a><br/>";
		echo "<a href=\"enrollfromothercourse.php?cid=$cid\">Enroll students from another course</a><br/>";
	}
	echo "<a href=\"listusers.php?cid=$cid&assigncode=1\">Assign Sections and/or Codes</a><br/>";
	echo "<a href=\"latepasses.php?cid=$cid\">Manage LatePasses</a>";
	if ($CFG['GEN']['allowinstraddtutors']) {
		echo "<br/><a href=\"managetutors.php?cid=$cid\">Manage Tutors</a>";
	}
	?>
	</div>
	<p></p>
<?php
	}
}	
	
require("../footer.php");
?>
