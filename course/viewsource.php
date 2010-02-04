<?php
//IMathAS:  Modify a question's code
//(c) 2006 David Lippman
	require("../validate.php");
	$pagetitle = "Question Source";
	require("../header.php");
	if (!(isset($teacherid)) && $myrights<100) {
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$isadmin = false;
	if (!isset($_GET['aid'])) {
		if ($_GET['cid']=="admin") {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../admin/admin.php\">Admin</a>";
			echo "&gt; <a href=\"manageqset.php?cid=admin\">Manage Question Set</a> &gt; View Source</div>\n";
			$isadmin = true;
		} else {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a>";
			echo "&gt; <a href=\"manageqset.php?cid={$_GET['cid']}\">Manage Question Set</a> &gt; View Source</div>\n";
		}
		
	} else {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"addquestions.php?aid={$_GET['aid']}&cid={$_GET['cid']}\">Add/Remove Questions</a> &gt; View Source</div>";
	}
	
	$qsetid = $_GET['id'];
	$query = "SELECT * FROM imas_questionset WHERE id='$qsetid'";
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	
	echo '<div id="headerviewsource" class="pagetitle"><h2>Question Source</h2></div>';
	echo "<h4>Descr'ption</h4>\n";
	echo "<pre>".$line['description']."</pre>\n";
	echo "<h4>Author</h4>\n";
	echo "<pre>".$line['author']."</pre>\n";
	echo "<h4>Question Type</h4>\n";
	echo "<pre>".$line['qtype']."</pre>\n";
	echo "<h4>Common Control</h4>\n";
	echo "<pre>".$line['control']."</pre>\n";
	echo "<h4>Question Control</h4>\n";
	echo "<pre>".$line['qcontrol']."</pre>\n";
	echo "<h4>Question Text</h4>\n";
	echo "<pre>".$line['qtext']."</pre>\n";
	echo "<h4>Answer</h4>\n";
	echo "<pre>".$line['answer']."</pre>\n";
	
	
	if (!isset($_GET['aid'])) {
		echo "<a href=\"manageqset.php?cid={$_GET['cid']}\">Return to Question Set Management</a>\n";
	} else {
		echo "<a href=\"addquestions.php?cid={$_GET['cid']}&aid={$_GET['aid']}\">Return to Assessment</a>\n";
	}
	require("../footer.php");
?>
