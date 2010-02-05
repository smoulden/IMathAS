<?php 
//IMathAS:  Main course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("courseshowitems.php");
require("../includes/htmlutil.php");
require("../includes/calendardisp.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($guestid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = $_GET['cid'];
	
	if (isset($teacherid) && isset($sessiondata['sessiontestid']) && !isset($sessiondata['actas'])) {
		//clean up coming out of an assessment
		require_once("../includes/filehandler.php");
		deleteasidfilesbyquery(array('id'=>$sessiondata['sessiontestid']),1);
		$query = "DELETE FROM imas_assessment_sessions WHERE id='{$sessiondata['sessiontestid']}' LIMIT 1";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());		
	}
   
	if (isset($teacherid) && isset($_GET['from']) && isset($_GET['to'])) {
		$from = $_GET['from'];
		$to = $_GET['to'];
		$block = $_GET['block'];
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		$blocktree = explode('-',$block);
		$sub =& $items;
		for ($i=1;$i<count($blocktree)-1;$i++) {
			$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
		if (count($blocktree)>1) {
			$curblock =& $sub[$blocktree[$i]-1]['items'];
			$blockloc = $blocktree[$i]-1;
		} else {
			$curblock =& $sub;
		}
	   	   
		$blockloc = $blocktree[count($blocktree)-1]-1; 
	   	//$sub[$blockloc]['items'] is block with items
	   
		if (strpos($to,'-')!==false) {  //in or out of block
			if ($to[0]=='O') {  //out of block
				$itemtomove = $curblock[$from-1];  //+3 to adjust for other block params
				//$to = substr($to,2);
				array_splice($curblock,$from-1,1);
				if (is_array($itemtomove)) {
					array_splice($sub,$blockloc+1,0,array($itemtomove));
				} else {
					array_splice($sub,$blockloc+1,0,$itemtomove);
				}
			} else {  //in to block
				$itemtomove = $curblock[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
				array_splice($curblock,$from-1,1);
				$to = substr($to,2);
				if ($from<$to) {$adj=1;} else {$adj=0;}
				array_push($curblock[$to-1-$adj]['items'],$itemtomove);
			}
		} else { //move inside block
			$itemtomove = $curblock[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
			array_splice($curblock,$from-1,1);
			if (is_array($itemtomove)) {
				array_splice($curblock,$to-1,0,array($itemtomove));
			} else {
				array_splice($curblock,$to-1,0,$itemtomove);
			}
		}
		$itemlist = addslashes(serialize($items));
		$query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='{$_GET['cid']}'";
		mysql_query($query) or die("Query failed : " . mysql_error()); 
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
	}
		
	$query = "SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,chatset,topbar,cploc FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if ($line == null) {
		$overwriteBody = 1;
		$body = "Course does not exist.  <a hre=\"../index.php\">Return to main page</a></body></html>\n";
	}	
	
	$allowunenroll = $line['allowunenroll'];
	$hideicons = $line['hideicons'];
	$graphicalicons = ($line['picicons']==1);
	$pagetitle = $line['name'];
	$items = unserialize($line['itemorder']);
	$msgset = $line['msgset']%5;
	$chatset = $line['chatset'];
	$useleftbar = (($line['cploc']&1)==1);
	$useleftstubar = (($line['cploc']&2)==2);
	$topbar = explode('|',$line['topbar']);
	$topbar[0] = explode(',',$topbar[0]);
	$topbar[1] = explode(',',$topbar[1]);
	if (!isset($topbar[2])) {$topbar[2] = 0;}
	if ($topbar[0][0] == null) {unset($topbar[0][0]);}
	if ($topbar[1][0] == null) {unset($topbar[1][0]);}
  
	if (isset($teacherid) && isset($_GET['togglenewflag'])) { //handle toggle of NewFlag
		$sub =& $items;
		$blocktree = explode('-',$_GET['togglenewflag']);
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree)-1;$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		$sub =& $sub[$blocktree[$i]-1];
		if (!isset($sub['newflag']) || $sub['newflag']==0) {
			$sub['newflag']=1;
		} else {
			$sub['newflag']=0;
		}
		$itemlist = addslashes(serialize($items));
		$query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error()); 	   
	}
	
	//enable teacher guest access
	if (isset($guestid)) {
		$teacherid = $guestid;
	}

	if ((!isset($_GET['folder']) || $_GET['folder']=='') && !isset($sessiondata['folder'.$cid])) {
		$_GET['folder'] = '0';  
		$sessiondata['folder'.$cid] = '0';
		writesessiondata();
	} else if ((isset($_GET['folder']) && $_GET['folder']!='') && (!isset($sessiondata['folder'.$cid]) || $sessiondata['folder'.$cid]!=$_GET['folder'])) {
		$sessiondata['folder'.$cid] = $_GET['folder'];
		writesessiondata();
	} else if ((!isset($_GET['folder']) || $_GET['folder']=='') && isset($sessiondata['folder'.$cid])) {
		$_GET['folder'] = $sessiondata['folder'.$cid];
	}
	if (!isset($_GET['quickview']) && !isset($sessiondata['quickview'.$cid])) {
		$quickview = false;
	} else if (isset($_GET['quickview'])) {
		$quickview = $_GET['quickview'];
		$sessiondata['quickview'.$cid] = $quickview;
		writesessiondata();
	} else if (isset($sessiondata['quickview'.$cid])) {
		$quickview = $sessiondata['quickview'.$cid];
	}
	if ($quickview=="on") {
		$_GET['folder'] = '0';
	}
	
	if (!isset($sessiondata['lastaccess'.$cid]) && !isset($teacherid)) {
		$now = time();
		$query = "UPDATE imas_students SET lastaccess='$now' WHERE userid='$userid' AND courseid='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$sessiondata['lastaccess'.$cid] = $now;
		writesessiondata();
	}
	//get exceptions
	$now = time() + $previewshift;
	$exceptions = array();
	if (!isset($teacherid) && !isset($tutorid)) {
		$query = "SELECT items.id,ex.startdate,ex.enddate FROM ";
		$query .= "imas_exceptions AS ex,imas_items as items,imas_assessments as i_a WHERE ex.userid='$userid' AND ";
		$query .= "ex.assessmentid=i_a.id AND (items.typeid=i_a.id AND items.itemtype='Assessment') ";
		// $query .= "AND (($now<i_a.startdate AND ex.startdate<$now) OR ($now>i_a.enddate AND $now<ex.enddate))";
		//$query .= "AND (ex.startdate<$now AND $now<ex.enddate)";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$exceptions[$line['id']] = array($line['startdate'],$line['enddate']);
		}
	}
	//update block start/end dates to show blocks containing items with exceptions
	if (count($exceptions)>0) {
		upsendexceptions($items);
	}
		
	if ($_GET['folder']!='0') {
		$now = time() + $previewshift;
		$blocktree = explode('-',$_GET['folder']);
		$backtrack = array();
		for ($i=1;$i<count($blocktree);$i++) {
			$backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
			if (!isset($teacherid) && !isset($tutorid) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
				$_GET['folder'] = 0;
				$items = unserialize($line['itemorder']);
				unset($backtrack);
				unset($blocktree);
				break;
			}
			if (isset($items[$blocktree[$i]-1]['grouplimit']) && count($items[$blocktree[$i]-1]['grouplimit'])>0 && !isset($teacherid) && !isset($tutorid)) {
				if (!in_array('s-'.$studentinfo['section'],$items[$blocktree[$i]-1]['grouplimit'])) {
					echo 'Not authorized';
					exit;
				}
			}  
			$items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
	}
	//DEFAULT DISPLAY PROCESSING
	$jsAddress1 = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}";
	$jsAddress2 = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	
	$openblocks = Array(0);
	$prevloadedblocks = array(0);
	if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]); $firstload=false;} else {$firstload=true;}
	if (isset($_COOKIE['prevloadedblocks-'.$cid]) && $_COOKIE['prevloadedblocks-'.$cid]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$cid]);}
	$plblist = implode(',',$prevloadedblocks);
	$oblist = implode(',',$openblocks);
	
	$curBreadcrumb = $breadcrumbbase;
	if (isset($backtrack) && count($backtrack)>0) {
		$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=0\">$coursename</a> ";
		for ($i=0;$i<count($backtrack);$i++) {
			$curBreadcrumb .= "&gt; ";
			if ($i!=count($backtrack)-1) {
				$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder={$backtrack[$i][1]}\">";
			}
			$curBreadcrumb .= stripslashes($backtrack[$i][0]);
			if ($i!=count($backtrack)-1) {
				$curBreadcrumb .= "</a>";
			}
		}
		$curname = $backtrack[count($backtrack)-1][0];
		if (count($backtrack)==1) {
			$backlink =  "<span class=right><a href=\"course.php?cid=$cid&folder=0\">Back</a></span><br class=\"form\" />";
		} else {
			$backlink = "<span class=right><a href=\"course.php?cid=$cid&folder=".$backtrack[count($backtrack)-2][1]."\">Back</a></span><br class=\"form\" />";
		}
	} else {
		$curBreadcrumb .= $coursename;
		$curname = $coursename;
	}
	
	
	if ($msgset<4) {
	   $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread=0 OR isread=4)";
	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
	   if (mysql_result($result,0,0)>0) {
		   $newmsgs = " <a href=\"$imasroot/msgs/newmsglist.php?cid=$cid\" style=\"color:red\">New Messages</a>";
	   } else {
		   $newmsgs = '';
	   }
	}
	/*
	$query = "SELECT count(*) FROM ";
	$query .= "(SELECT imas_forum_posts.threadid,max(imas_forum_posts.postdate),mfv.lastview FROM imas_forum_posts ";
	$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_posts.threadid AND mfv.userid='$userid' WHERE imas_forums.courseid='$cid' ";
	$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))) AS newitems ";
	*/	
	$query = "SELECT count(*) FROM ";
	$query .= "(SELECT imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid='$cid' ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))) AS newitems ";
	
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	if ($row[0]>0) {
		$newpostscnt = " <a href=\"$imasroot/forums/newthreads.php?cid=$cid\" style=\"color:red\">New Posts</a>";
	} else {
		$newpostscnt = '';	
	}
   
	//get active chatters
	if (isset($mathchaturl) &&  $chatset==1) {
		if (substr($mathchaturl,0,4)=='http') {
			//remote mathchat
			$url = $mathchaturl.'?isactive='.$cid.'&sep='.time();
			$fp = fopen($url,'r');
			$activechatters = fread($fp,100);
			fclose($fp);
		} else {  
			//local mathchat
			$on = time() - 15;
			$query = "SELECT name FROM mc_sessions WHERE mc_sessions.room='$cid' AND lastping>$on";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$activechatters =  mysql_num_rows($result);
		}
	}
	
	//get latepasses
	if (!isset($teacherid) && !isset($tutorid) && $previewshift==-1) {
	   $query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   $latepasses = mysql_result($result,0,0);
	} else {
		$latepasses = 0;
	}
}
  
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/course.js?v=112409\"></script>";

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {

	if (isset($teacherid)) {
 ?>  
	<script type="text/javascript">
		function moveitem(from,blk) { 
			var to = document.getElementById(blk+'-'+from).value;
			
			if (to != from) {
				var toopen = '<?php echo $jsAddress1 ?>&block=' + blk + '&from=' + from + '&to=' + to;
				window.location = toopen;
			}
		}
		
		function additem(blk,tb) {
			var type = document.getElementById('addtype'+blk+'-'+tb).value;
			if (tb=='BB' || tb=='LB') { tb = 'b';}
			if (type!='') {
				var toopen = '<?php echo $jsAddress2 ?>/add' + type + '.php?block='+blk+'&tb='+tb+'&cid=<?php echo $_GET['cid'] ?>';
				window.location = toopen;
			}
		}
	</script>

<?php
	}	
?>
	<script type="text/javascript">
		var getbiaddr = 'getblockitems.php?cid=<?php echo $cid ?>&folder=';
		var oblist = '<?php echo $oblist ?>';
		var plblist = '<?php echo $plblist ?>';
		var cid = '<?php echo $cid ?>';
	</script> 
	
<?php
	//check for course layout
	if (isset($CFG['GEN']['courseinclude'])) {
		require($CFG['GEN']['courseinclude']);
		if ($firstload) {
			echo "<script>document.cookie = 'openblocks-$cid=' + oblist;\n";
			echo "document.cookie = 'loadedblocks-$cid=0';</script>\n";
		}
		require("../footer.php");
		exit;
	}
?>
	<div class=breadcrumb>
		<span class="padright">
		<?php if (isset($guestid)) {
			echo '<span class="red">Instructor Preview</span> ';
		}?>
		<?php echo $userfullname ?>
		</span>
		<?php echo $curBreadcrumb ?>
		<div class=clear></div>
	</div>
	
<?php  
	if ($useleftbar && isset($teacherid)) {
?>	
	<div id="leftcontent">
	<?php
		if (isset($CFG['CPS']['additemtype']) && $CFG['CPS']['additemtype'][0]=='links') {
		} else {
			echo '<p>'.generateadditem($_GET['folder'],'LB').'</p>';
		}
	?>
		<p>
		<b>Communication</b><br/>
			<a href="<?php echo $imasroot ?>/msgs/msglist.php?cid=<?php echo $cid ?>&folder=<?php echo $_GET['folder'] ?>">
			Messages</a> <?php echo $newmsgs ?> <br/>
			<a href="<?php echo $imasroot ?>/forums/forums.php?cid=<?php echo $cid ?>&folder=<?php echo $_GET['folder'] ?>">
			Forums</a> <?php echo $newpostscnt ?><br/>
<?php 
		if (isset($mathchaturl) &&  $chatset==1) {
			echo "<br/><a href=\"$mathchaturl?uname=".urlencode($userfullname)."&amp;room=$cid&amp;roomname=".urlencode($coursename)."\" target=\"chat\">Chat</a> ($activechatters)";
		}
		?>
		</p>
		<p><b>Tools:</b><br/>
			<a href="listusers.php?cid=<?php echo $cid ?>">Roster</a><br/>
			<a href="gradebook.php?cid=<?php echo $cid ?>">Gradebook</a> <?php if (($coursenewflag&1)==1) {echo '<span class="red">New</span>';}?><br/>
			<a href="managestugrps.php?cid=<?php echo $cid ?>">Groups</a><br/>
			<a href="showcalendar.php?cid=<?php echo $cid ?>">Calendar</a>
		
		</p>
	<?php
	if (!isset($CFG['CPS']['viewbuttons']) || $CFG['CPS']['viewbuttons']!=true) {
	?>
		<p><b>Views:</b><br/>
		<a href="course.php?cid=<?php echo $cid ?>&stuview=0">Student View</a><br/>
			<a href="course.php?cid=<?php echo $cid ?>&quickview=on">Quick View</a>
		</p>
	<?php
	}
	?>
		<p><b>Manage:</b><br/>
			<a href="manageqset.php?cid=<?php echo $cid ?>">Question Set</a><br/>
			<a href="managelibs.php?cid=<?php echo $cid ?>">Libraries</a>
		</p>
<?php			
		if ($allowcourseimport) {
?>
		<p><b>Export/Import:</b><br/>
			<a href="../admin/export.php?cid=<?php echo $cid ?>">Export Question Set</a><br/>
			<a href="../admin/import.php?cid=<?php echo $cid ?>">Import Question Set</a><br/>
			<a href="../admin/exportlib.php?cid=<?php echo $cid ?>">Export Libraries</a><br/>
			<a href="../admin/importlib.php?cid=<?php echo $cid ?>">Import Libraries</a>
		</p>
<?php
		}
?>
		<p><b>Course Items:</b><br/>
			<a href="copyitems.php?cid=<?php echo $cid ?>">Copy</a><br/>
			<a href="../admin/exportitems.php?cid=<?php echo $cid ?>">Export</a><br/>
			<a href="../admin/importitems.php?cid=<?php echo $cid ?>">Import</a>
		</p>
		
		<p><b>Change:</b><br/>
			<a href="chgassessments.php?cid=<?php echo $cid ?>">Assessments</a><br/>
			<a href="masschgdates.php?cid=<?php echo $cid ?>">Dates</a><br/>
			<a href="../admin/forms.php?action=modify&id=<?php echo $cid ?>&cid=<?php echo $cid ?>">Course Settings</a>
		</p>
		<p>
			<a href="<?php echo $imasroot ?>/help.php?section=coursemanagement">Help</a><br/>
			<a href="../actions.php?action=logout">Log Out</a>
		</p>
	</div>
	<div id="centercontent">
<?php	
	} else if ($useleftstubar && !isset($teacherid)) {
?>
		<div id="leftcontent">
			<p>
			<a href="<?php echo $imasroot ?>/msgs/msglist.php?cid=<?php echo $cid ?>&folder=<?php echo $_GET['folder'] ?>">
			Messages</a> <?php echo $newmsgs ?> <br/>
			<a href="<?php echo $imasroot ?>/forums/forums.php?cid=<?php echo $cid ?>&folder=<?php echo $_GET['folder'] ?>">
			Forums</a> <?php echo $newpostscnt ?><br/>
			<a href="showcalendar.php?cid=<?php echo $cid ?>">Calendar</a>
	<?php if (isset($mathchaturl) && $chatset==1) {
			echo "<br/><a href=\"$mathchaturl?uname=".urlencode($userfullname)."&amp;room=$cid&amp;roomname=".urlencode($coursename)."\"  target=\"chat\">Chat</a>  ($activechatters)";
		}
	?>
			</p>
			<p>
			<a href="gradebook.php?cid=<?php echo $cid ?>">Gradebook</a> <?php if (($coursenewflag&1)==1) {echo '<span class="red">New</span>';}?>
			</p>
			<p>
			<a href="../actions.php?action=logout">Log Out</a><br/>   
			<a href="<?php echo $imasroot ?>/help.php?section=usingimas">Help Using <?php echo $installname;?></a>
			</p>
			<?php		  
			if ($myrights > 5 && $allowunenroll==1) {
				echo "<p><a href=\"../forms.php?action=unenroll&cid=$cid\">Unenroll From Course</a></p>\n";
			}
			?>
		</div>
		<div id="centercontent">
<?php
	}
   
   if ($previewshift>-1) {
?>
	<script type="text/javascript">
		function changeshift() {
			var shift = document.getElementById("pshift").value;
			var toopen = '<?php echo $jsAddress1 ?>&stuview='+shift;
			window.location = toopen; 
		}
	</script>

<?php	
   }
   makeTopMenu();	
   echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>$curname</h2></div>\n";
   
   if (count($items)>0) {
	   
	   	   
	   if ($quickview=='on' && isset($teacherid)) {
		   echo '<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;}</style>';
		   echo "<script>var AHAHsaveurl = '$imasroot/course/savequickreorder.php?cid=$cid';</script>";
		   echo "<script src=\"$imasroot/javascript/mootools.js\"></script>";
		   echo "<script src=\"$imasroot/javascript/nested1.js?v=0122102\"></script>";
		   echo '<ul id=qviewtree class=qview>';
		   quickview($items,0);
		   echo '</ul>';
		   echo '<p>&nbsp;</p>';
	   } else {
		   showitems($items,$_GET['folder']);
	   }
	    
	 
	  
	
   } else {
	   if (isset($teacherid)) {echo generateadditem($_GET['folder'],'t');}
   }
   if (isset($backlink)) {
	   echo $backlink;
   }
   
   if (($useleftbar && isset($teacherid)) || ($useleftstubar && !isset($teacherid))) {
	   echo "</div>";
   } else {
	  
?>	   
	<div class=cp>
		<span class=column>
<?php		 if ($msgset<4) {  ?>
			<a href="<?php echo $imasroot ?>/msgs/msglist.php?cid=<?php echo $cid ?>&folder=<?php echo $_GET['folder'] ?>">
			Messages</a><?php echo $newmsgs ?> <br/>
<?php		 } ?>
			<a href="<?php echo $imasroot ?>/forums/forums.php?cid=<?php echo $cid ?>&folder=<?php echo $_GET['folder'] ?>">
			Forums</a> <?php echo $newpostscnt ?>
	<?php if (isset($mathchaturl) && $chatset==1) {
			echo "<br/><a href=\"$mathchaturl?uname=".urlencode($userfullname)."&amp;room=$cid&amp;roomname=".urlencode($coursename)."\"  target=\"chat\">Chat</a>  ($activechatters)";
		}
	?>
		</span>
		<div class=clear></div>
	</div>
<?php
	   
	   
	   if (isset($teacherid)) {
?>
	<div class=cp>
		<span class=column>
			<?php echo generateadditem($_GET['folder'], 'BB') ?>
			<a href="listusers.php?cid=<?php echo $cid ?>">List Students</a><br/>
			<a href="gradebook.php?cid=<?php echo $cid ?>">Show Gradebook</a> <?php if (($coursenewflag&1)==1) {echo '<span class="red">New</span>';}?><br/>
			<a href="course.php?cid=<?php echo $cid ?>&stuview=0">Student View</a><br/>
			<a href="course.php?cid=<?php echo $cid ?>&quickview=on">Quick View</a></span>
			<span class=column>
				<a href="manageqset.php?cid=<?php echo $cid ?>">Manage Question Set<br></a>
<?php		
			if ($allowcourseimport) {
?>
				<a href="../admin/export.php?cid=<?php echo $cid ?>">Export Question Set<br></a>
				<a href="../admin/import.php?cid=<?php echo $cid ?>">Import Question Set</a>
			</span>
			<span class=column>
				<a href="managelibs.php?cid=<?php echo $cid ?>">Manage Libraries</a><br>
				<a href="../admin/exportlib.php?cid=<?php echo $cid ?>">Export Libraries</a><br/>
				<a href="../admin/importlib.php?cid=<?php echo $cid ?>">Import Libraries</a>
			</span>
			<span class=column>
				<a href="copyitems.php?cid=<?php echo $cid ?>">Copy Course Items</a><br/>
				<a href="managestugrps.php?cid=<?php echo $cid ?>">Student Groups</a><br/>
				<a href="showcalendar.php?cid=<?php echo $cid ?>">Calendar</a>
			</span>
<?php
			} else {
?>
			<a href="managelibs.php?cid=<?php echo $cid ?>">Manage Libraries</a><br>
			<a href="copyitems.php?cid=<?php echo $cid ?>">Copy Course Items</a><br/>
			<a href="showcalendar.php?cid=<?php echo $cid ?>">Calendar</a>
		</span>
		<span class=column>
			<a href="managestugrps.php?cid=<?php echo $cid ?>">Student Groups</a><br/>
			<a href="../admin/forms.php?action=modify&id=<?php echo $cid ?>&cid=<?php echo $cid ?>">Course Settings</a>
		</span>
<?php
			}
			
			echo "<div class=clear></div></div>\n";
		}
		echo "<div class=cp>\n";
	   
	   if (!isset($teacherid)) {
?>
	<a href="showcalendar.php?cid=<?php echo $cid ?>">Calendar</a><br />
	<a href="gradebook.php?cid=<?php echo $cid ?>">Gradebook</a> <?php if (($coursenewflag&1)==1) {echo '<span class="red">New</span>';}?><br/>
	<a href="../actions.php?action=logout">Log Out</a><br/>   
	<a href="<?php echo $imasroot ?>/help.php?section=usingimas">Help Using <?php echo $installname;?></a><br/> 
<?php		  
			if ($myrights > 5 && $allowunenroll==1) {
				echo "<p><a href=\"../forms.php?action=unenroll&cid=$cid\">Unenroll From Course</a></p>\n";
			}
	   } else {
?>
	<span class=column>
		<a href="../actions.php?action=logout">Log Out</a><BR>
<?php
			if ($allowcourseimport) {
				echo "<a href=\"copyitems.php?cid=$cid\">Copy Course Items</a><br/>\n";
			}
?>			
		<a href="../admin/exportitems.php?cid=<?php echo $cid ?>">Export Course Items</a><br/>
		<a href="../admin/importitems.php?cid=<?php echo $cid ?>">Import Course Items</a><br/>
	</span>
	<span class=column>
		<a href="<?php echo $imasroot ?>/help.php?section=coursemanagement">Help</a><br/>
		<a href="timeshift.php?cid=<?php echo $cid ?>">Shift all Course Dates</a><br/>
		<a href="chgassessments.php?cid=<?php echo $cid ?>">Mass Change Assessments</a>
	</span>
	<span class=column>
		<a href="masschgdates.php?cid=<?php echo $cid ?>">Mass Change Dates</a>
	</span>
<?php		
		}
		echo "<div class=clear></div></div>\n";
	}
	if ($firstload) {
		echo "<script>document.cookie = 'openblocks-$cid=' + oblist;\n";
		echo "document.cookie = 'loadedblocks-$cid=0';</script>\n";
	}
}   

require("../footer.php");

function makeTopMenu() {
	global $teacherid;
	global $topbar;
	global $msgset;
	global $previewshift;
	global $imasroot;
	global $cid;
	global $newmsgs;
	global $quickview;
	global $newpostscnt;
	global $coursenewflag;
	global $CFG;
	
	if (isset($CFG['CPS']['viewbuttons']) && $CFG['CPS']['viewbuttons']==true) {
		$useviewbuttons = true;
		echo '<div id="viewbuttoncont">View: ';
		echo "<a href=\"course.php?cid=$cid&quickview=off&teachview=1\" ";
		if ($previewshift==-1 && $quickview != 'on') {
			echo 'class="buttonactive"';
		} else {
			echo 'class="buttoninactive"';
		}
		echo '>Normal</a>';
		echo "<a href=\"course.php?cid=$cid&quickview=off&stuview=0\" ";
		if ($previewshift>-1 && $quickview != 'on') {
			echo 'class="buttonactive"';
		} else {
			echo 'class="buttoninactive"';
		}
		echo '>Student</a>';
		echo "<a href=\"course.php?cid=$cid&quickview=on&teachview=1\" ";
		if ($previewshift==-1 && $quickview == 'on') {
			echo 'class="buttonactive"';
		} else {
			echo 'class="buttoninactive"';
		}
		echo '>Quick</a>';
		echo '</div>';
		//echo '<br class="clear"/>';
			
		
	} else {
		$useviewbuttons = false;
	}
	
	if (isset($teacherid) && $quickview=='on') {
		echo "<div class=breadcrumb>";
		if (!$useviewbuttons) {
			echo "Quick View. <a href=\"course.php?cid=$cid&quickview=off\">Back to regular view</a>. ";
		} else {
			echo '<br class="clear"/>';
		}
		 echo 'Use colored boxes to drag-and-drop order.  <input type="button" id="recchg" disabled="disabled" value="Record Changes" onclick="submitChanges()"/>';
		 echo '<span id="submitnotice" style="color:red;"></span>';
		 echo '</div>';
		
	}
	if (($coursenewflag&1)==1) {
		$gbnewflag = ' <span class="red">New</span>';
	} else {
		$gbnewflag = '';
	}
	if (isset($teacherid) && count($topbar[1])>0 && $topbar[2]==0) {
		echo '<div class=breadcrumb>';
		if (in_array(0,$topbar[1]) && $msgset<4) { //messages
			echo "<a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a>$newmsgs &nbsp; ";
		}
		if (in_array(6,$topbar[1])) { //Calendar
			echo "<a href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a>$newpostscnt &nbsp; ";
		}
		if (in_array(1,$topbar[1])) { //Stu view
			echo "<a href=\"course.php?cid=$cid&stuview=0\">Student View</a> &nbsp; ";
		}
		if (in_array(3,$topbar[1])) { //List stu
			echo "<a href=\"listusers.php?cid=$cid\">Roster</a> &nbsp; \n";
		}
		if (in_array(2,$topbar[1])) { //Gradebook
			echo "<a href=\"gradebook.php?cid=$cid\">Gradebook</a>$gbnewflag &nbsp; ";
		}
		if (in_array(7,$topbar[1])) { //List stu
			echo "<a href=\"managestugrps.php?cid=$cid\">Groups</a> &nbsp; \n";
		}
		if (in_array(4,$topbar[1])) { //Calendar
			echo "<a href=\"showcalendar.php?cid=$cid\">Calendar</a> &nbsp; \n";
		}
		if (in_array(5,$topbar[1])) { //Calendar
			echo "<a href=\"course.php?cid=$cid&quickview=on\">Quick View</a> &nbsp; \n";
		}
		
		if (in_array(9,$topbar[1])) { //Log out
			echo "<a href=\"../actions.php?action=logout\">Log Out</a>";
		}
		echo '<div class=clear></div></div>';
	} else if (!isset($teacherid) && ((count($topbar[0])>0 && $topbar[2]==0) || ($previewshift>-1 && !$useviewbuttons))) {
		echo '<div class=breadcrumb>';
		if ($topbar[2]==0) {
			if (in_array(0,$topbar[0]) && $msgset<4) { //messages
				echo "<a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a>$newmsgs &nbsp; ";
			}
			if (in_array(3,$topbar[0])) { //forums
				echo "<a href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a>$newpostscnt &nbsp; ";
			}
			if (in_array(1,$topbar[0])) { //Gradebook
				echo "<a href=\"gradebook.php?cid=$cid\">Show Gradebook</a>$gbnewflag &nbsp; ";
			}
			if (in_array(2,$topbar[0])) { //Calendar
				echo "<a href=\"showcalendar.php?cid=$cid\">Calendar</a> &nbsp; \n";
			}
			if (in_array(9,$topbar[0])) { //Log out
				echo "<a href=\"../actions.php?action=logout\">Log Out</a>";
			}
			if ($previewshift>-1 && count($topbar[0])>0) { echo '<br />';}
		}
		if ($previewshift>-1 && !$useviewbuttons) {
			echo 'Showing student view. Show view: <select id="pshift" onchange="changeshift()">';
			echo '<option value="0" ';
			if ($previewshift==0) {echo "selected=1";}
			echo '>Now</option>';
			echo '<option value="3600" ';
			if ($previewshift==3600) {echo "selected=1";}
			echo '>1 hour from now</option>';
			echo '<option value="14400" ';
			if ($previewshift==14400) {echo "selected=1";}
			echo '>4 hours from now</option>';
			echo '<option value="86400" ';
			if ($previewshift==86400) {echo "selected=1";}
			echo '>1 day from now</option>';
			echo '<option value="604800" ';
			if ($previewshift==604800) {echo "selected=1";}
			echo '>1 week from now</option>';
			echo '</select>';
			echo " <a href=\"course.php?cid=$cid&teachview=1\">Back to instructor view</a>";
		}
		echo '<div class=clear></div></div>';
	}
}




?>

