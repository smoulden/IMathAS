<?php
//IMathAS:  Redeem latepasses
//(c) 2007 David Lippman

	require("../validate.php");
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	
	$query = "SELECT latepasshrs FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$hours = mysql_result($result,0,0);
		
	if (isset($_GET['confirm'])) {
		$addtime = $hours*60*60;
		$query = "SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($allowlate,$enddate,$startdate) =mysql_fetch_row($result);
		if ($allowlate==1) {
			$query = "UPDATE imas_students SET latepass=latepass-1 WHERE userid='$userid' AND courseid='$cid' AND latepass>0";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_affected_rows()>0) {
				$query = "SELECT enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) { //already have exception
					$query = "UPDATE imas_exceptions SET enddate=enddate+$addtime,islatepass=islatepass+1 WHERE userid='$userid' AND assessmentid='$aid'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else {
					$enddate = $enddate + $addtime;
					$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass) VALUES ('$userid','$aid','$startdate','$enddate',1)";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}
		}
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
	} else {
		require("../header.php");
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";
		$curBreadcrumb .= " &gt; Redeem LatePass\n";
		echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";
		
		$query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$numlatepass = mysql_result($result,0,0);
		
		if ($numlatepass==0) { //shouldn't get here if 0
			echo "<p>You have no late passes remaining</p>";
		} else {
			echo '<div id="headerredeemlatepass" class="pagetitle"><h2>Redeem LatePass</h2></div>';
			echo "<form method=post action=\"redeemlatepass.php?cid=$cid&aid=$aid&confirm=true\">";
			echo "<p>You have $numlatepass LatePass(es) remaining.  You can redeem one LatePass for a $hours hour ";
			echo "extension on this assessment.  Are you sure you want to redeem a LatePass?</p>";
			echo "<input type=submit value=\"Yes, Redeem LatePass\"/>";
			echo "<input type=button value=\"Nevermind\" onclick=\"window.location='course.php?cid=$cid'\"/>";
			echo "</form>";
		}
		require("../footer.php");
	}
	
?>
