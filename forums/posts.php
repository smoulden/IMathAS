<?php
	//Displays forums posts
	//(c) 2006 David Lippman
	
	require("../validate.php");
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
	}
	if (isset($teacherid)) {
		$isteacher = true;	
	} else {
		$isteacher = false;
	}
	
	$cid = $_GET['cid'];
	$forumid = $_GET['forum'];
	$threadid = $_GET['thread'];
	$page = $_GET['page'];
	//special "page"s
	//-1 new posts from forum page
	//-2 tagged posts from forum page
	//-3 new posts from newthreads page
	
	if (isset($_GET['markunread'])) {
		$query = "DELETE FROM imas_forum_views WHERE userid='$userid' AND threadid='$threadid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ($page==-3) {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newthreads.php?cid=$cid");
		} else {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?cid=$cid&forum=$forumid&page=$page");
		}
		exit;
	}
	if (isset($_GET['marktagged'])) {
		$query = "UPDATE imas_forum_views SET tagged=1 WHERE userid='$userid' AND threadid='$threadid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ($page==-3) {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newthreads.php?cid=$cid");
		} else {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?cid=$cid&forum=$forumid&page=$page");
		}
		exit;
	} else if (isset($_GET['markuntagged'])) {
		$query = "UPDATE imas_forum_views SET tagged=0 WHERE userid='$userid' AND threadid='$threadid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ($page==-3) {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newthreads.php?cid=$cid");
		} else {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?cid=$cid&forum=$forumid&page=$page");
		}
		exit;
	}
	$query = "SELECT settings,replyby,defdisplay,name,points FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumsettings = mysql_result($result,0,0);
	$allowreply = ($isteacher || (time()<mysql_result($result,0,1)));
	$defdisplay = mysql_result($result,0,2);
	$allowanon = (($forumsettings&1)==1);
	$allowmod = ($isteacher || (($forumsettings&2)==2));
	$allowdel = ($isteacher || (($forumsettings&4)==4));
	$haspoints = (mysql_result($result,0,4) > 0);
	$forumname = mysql_result($result,0,3);
	
		
	if (isset($_GET['view'])) {
		$view = $_GET['view'];
	} else {
		$view = $defdisplay;  //0: expanded, 1: collapsed, 2: condensed
	}
	
	$caller = "posts";
	include("posthandler.php");
	
	$pagetitle = "Posts";
	$placeinhead = '<link rel="stylesheet" href="'.$imasroot.'/forums/forums.css?ver=022410" type="text/css" />';
	//$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	
	$allowmsg = false;
	if (!$isteacher) {
		$query = "SELECT msgset FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ((mysql_result($result,0,0)%5)==0) {
			$allowmsg = true;
		} 
	}
		
	
	$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email from imas_forum_posts,imas_users ";
	$query .= "WHERE imas_forum_posts.userid=imas_users.id AND (imas_forum_posts.id='$threadid' OR imas_forum_posts.threadid='$threadid') ORDER BY imas_forum_posts.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($line['parent']==0) {
			if ($line['replyby']!=null) {
				$allowreply = ($isteacher || (time()<$line['replyby']));
			}
		}
		if ($line['id']==$threadid) {
			$newviews = $line['views']+1;
		}
		$children[$line['parent']][] = $line['id'];
		$date[$line['id']] = $line['postdate'];
		$n = 0;
		while (strpos($line['subject'],'Re: ')===0) {
			$line['subject'] = substr($line['subject'],4);
			$n++;
		}
		if ($n==1) {
			$line['subject'] = 'Re: '.$line['subject'];
		} else if ($n>1) {
			$line['subject'] = "Re<sup>$n</sup>: ".$line['subject'];
		}
			
		$subject[$line['id']] = $line['subject'];
		if ($sessiondata['graphdisp']==0) {
			$line['message'] = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $line['message']);
		}
		$message[$line['id']] = $line['message'];
		$posttype[$line['id']] = $line['posttype'];
		$ownerid[$line['id']] = $line['userid'];
		$points[$line['id']] = 1*$line['points'];
		if ($line['isanon']==1) {
			$poster[$line['id']] = "Anonymous";
			$ownerid[$line['id']] = 0;
		} else {
			$poster[$line['id']] = $line['FirstName'] . ' ' . $line['LastName'];
			$email[$line['id']] = $line['email'];
		}
		
	}
	//update view count
	$query = "UPDATE imas_forum_posts SET views='$newviews' WHERE id='$threadid'";
	mysql_query($query) or die("Query failed : $query " . mysql_error());
	
	$query = "UPDATE imas_forum_threads SET views=views+1 WHERE id='$threadid'";
	mysql_query($query) or die("Query failed : $query " . mysql_error());
	
	//mark as read
	$query = "SELECT lastview,tagged FROM imas_forum_views WHERE userid='$userid' AND threadid='$threadid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$now = time();
	if (mysql_num_rows($result)>0) {
		$lastview = mysql_result($result,0,0);
		$tagged = mysql_result($result,0,1);
		$query = "UPDATE imas_forum_views SET lastview=$now WHERE userid='$userid' AND threadid='$threadid'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	} else {
		$lastview = 0;
		$tagged = 0;
		$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','$threadid',$now)";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
	if ($page==-3) {
		echo "<a href=\"newthreads.php?cid=$cid\">New Threads</a> ";
	} else {
		echo "<a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">$forumname</a> ";
	}
	echo "&gt; Posts</div>\n";
	
	echo '<div id="headerposts" class="pagetitle"><h2>Forum: '.$forumname.'</h2></div>';
	echo "<b style=\"font-size: 120%\">Post: {$subject[$threadid]}</b><br/>\n";
	
	$query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND threadid<'$threadid' AND parent=0 ORDER BY threadid DESC LIMIT 1";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$nextth = mysql_result($result,0,0);
		echo "<a href=\"posts.php?cid=$cid&forum=$forumid&thread=$nextth\">Prev</a> ";
	} else {
		echo "Prev ";
	}
	
	$query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND threadid>'$threadid' AND parent=0 ORDER BY threadid LIMIT 1";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$nextth = mysql_result($result,0,0);
		echo "<a href=\"posts.php?cid=$cid&forum=$forumid&thread=$nextth\">Next</a>";
	} else {
		echo "Next";
	}
	echo " | <a href=\"posts.php?cid=$cid&forum=$forumid&thread=$threadid&page=$page&markunread=true\">Mark Unread</a>";
	if ($tagged) {
		echo " | <a href=\"posts.php?cid=$cid&forum=$forumid&thread=$threadid&page=$page&markuntagged=true\">Unflag</a>";
	} else {
		echo " | <a href=\"posts.php?cid=$cid&forum=$forumid&thread=$threadid&page=$page&marktagged=true\">Flag</a>";
	}
	//echo "<br/><b style=\"font-size: 120%\">Post: {$subject[$threadid]}</b><br/>\n";
	//echo "<b style=\"font-size: 100%\">Forum: $forumname</b></p>";
	echo " | <input type=button value=\"Expand All\" onclick=\"expandall()\"/>";
	echo "<input type=button value=\"Collapse All\" onclick=\"collapseall()\"/> | ";
	echo " <input type=button value=\"Show All\" onclick=\"showall()\"/>";
	echo "<input type=button value=\"Hide All\" onclick=\"hideall()\"/> ";
	/*if ($view==2) {
		echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=0\">View Expanded</a>";
	} else {
		echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=2\">View Condensed</a>";
	}*/
	
?>
	<script type="text/javascript">
	function toggleshow(bnum) {
	   var node = document.getElementById('block'+bnum);
	   var butn = document.getElementById('butb'+bnum);
	   if (node.className == 'forumgrp') {
	       node.className = 'hidden';
	       //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
	//       butn.value = 'Expand';
		butn.src = imasroot+'/img/expand.gif';
	   } else { 
	       node.className = 'forumgrp';
	       //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
	//       butn.value = 'Collapse';
		butn.src = imasroot+'/img/collapse.gif';
	   }
	}
	function toggleitem(inum) {
	   var node = document.getElementById('item'+inum);
	   var butn = document.getElementById('buti'+inum);
	   if (node.className == 'blockitems') {
	       node.className = 'hidden';
	       butn.value = 'Show';
	   } else { 
	       node.className = 'blockitems';
	       butn.value = 'Hide';
	   }
	}
	function expandall() {
	   for (var i=0;i<bcnt;i++) {
	     var node = document.getElementById('block'+i);
	     var butn = document.getElementById('butb'+i);
	     node.className = 'forumgrp';
	//     butn.value = 'Collapse';
	       //if (butn.value=='Expand' || butn.value=='Collapse') {butn.value = 'Collapse';} else {butn.value = '-';}
	       butn.src = imasroot+'/img/collapse.gif';
	   }
	}
	function collapseall() {
	   for (var i=0;i<bcnt;i++) {
	     var node = document.getElementById('block'+i);
	     var butn = document.getElementById('butb'+i);
	     node.className = 'hidden';
	//     butn.value = 'Expand';
	       //if (butn.value=='Collapse' || butn.value=='Expand' ) {butn.value = 'Expand';} else {butn.value = '+';}
	       butn.src = imasroot+'/img/expand.gif';
	   }
	}
	
	function showall() {
	   for (var i=0;i<icnt;i++) {
	     var node = document.getElementById('item'+i);
	     var buti = document.getElementById('buti'+i);
	     node.className = "blockitems";
	     buti.value = "Hide";
	   }
	}
	function hideall() {
	   for (var i=0;i<icnt;i++) {
	     var node = document.getElementById('item'+i);
	     var buti = document.getElementById('buti'+i);
	     node.className = "hidden";
	     buti.value = "Show";
	   }
	}
	</script>
<?php
	$bcnt = 0;
	$icnt = 0;
	function printchildren($base,$restricttoowner=false) {
		$curdir = rtrim(dirname(__FILE__), '/\\');
		global $children,$date,$subject,$message,$poster,$email,$forumid,$threadid,$isteacher,$cid,$userid,$ownerid,$points,$posttype,$lastview,$bcnt,$icnt,$myrights,$allowreply,$allowmod,$allowdel,$view,$page,$allowmsg,$haspoints,$imasroot;
		foreach($children[$base] as $child) {
			if ($restricttoowner && $ownerid[$child] != $userid) {
				continue;
			}
			echo "<div class=block> ";
			echo '<span class="leftbtns">';
			if (isset($children[$child])) {
				if ($view==1) { 
					$lbl = '+';
					$img = "expand";
				} else {
					$lbl = '-';
					$img = "collapse";
				}
				//echo "<input type=button id=\"butb$bcnt\" value=\"$lbl\" onClick=\"toggleshow($bcnt)\"> ";
				echo "<img class=\"pointer\" id=\"butb$bcnt\" src=\"$imasroot/img/$img.gif\" onClick=\"toggleshow($bcnt)\"/> ";
			}
			if (file_exists("$curdir/../course/files/userimg_sm{$ownerid[$child]}.jpg")) {
				echo "<img src=\"$imasroot/course/files/userimg_sm{$ownerid[$child]}.jpg\" onclick=\"togglepic(this)\"/>";
			}
			echo '</span>';
			/*if ($view==2) {
				echo "<span class=right>";
				if ($haspoints) {
					if ($isteacher) {
						echo "<input type=text size=2 name=\"score[$child]\" value=\"";
						if ($points[$child]!=null) {
							echo $points[$child];
						}
						echo "\"/> Pts. ";
					} else if ($ownerid[$child]==$userid && $points[$child]!=null) {
						echo "<span class=red>{$points[$child]} points</span> ";
					}
				}
				echo "<input type=button id=\"buti$icnt\" value=\"Show\" onClick=\"toggleitem($icnt)\">\n";
				
				if ($isteacher ) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&move=$child\">Move</a> \n";
				} 
				if ($isteacher || ($ownerid[$child]==$userid && $allowmod)) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=$child\">Modify</a> \n";
				}
				if ($isteacher || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&remove=$child\">Remove</a> \n";
				}
				if ($posttype[$child]!=2 && $myrights > 5 && $allowreply) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=reply&replyto=$child\">Reply</a>";
				}
				echo "</span>";
				if (isset($children[$child])) {
					echo "<input type=button id=\"butb$bcnt\" value=\"-\" onClick=\"toggleshow($bcnt)\">\n";
				}
				echo "<b>{$subject[$child]}</b> Posted by: ";
				if ($isteacher && $ownerid[$child]!=0) {
					echo "<a href=\"mailto:{$email[$child]}\">";
				} else if ($allowmsg && $ownerid[$child]!=0) {
					echo "<a href=\"../msgs/msglist.php?cid=$cid&add=new&to={$ownerid[$child]}\">";
				}
				echo $poster[$child];
				if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
					echo "</a>";
				}
				echo ', ';
				echo tzdate("F j, Y, g:i a",$date[$child]);
				if ($date[$child]>$lastview) {
					echo " <span style=\"color:red;\">New</span>\n";
				}
				
				echo "</div>\n";
				echo "<div class=hidden id=\"item$icnt\">";
				
				
				echo filter($message[$child]);
				echo "<div class=\"clear\"></div></div>\n";
				$icnt++;
				if (isset($children[$child])) { //if has children
					echo "<div class=forumgrp id=\"block$bcnt\">\n";
					$bcnt++;
					printchildren($child, ($posttype[$child]==3 && !$isteacher));
					echo "</div>\n";
				}
			} else {
			*/
				echo "<span class=right>";
				if ($haspoints) {
					if ($isteacher) {
						echo "<input type=text size=2 name=\"score[$child]\" value=\"";
						if ($points[$child]!=null) {
							echo $points[$child];
						}
						echo "\"/> Pts. ";
					} else if ($ownerid[$child]==$userid && $points[$child]!=null) {
						echo "<span class=red>{$points[$child]} points</span> ";
					}
				}
				/*if (isset($children[$child])) {
					echo "<input type=button id=\"butb$bcnt\" value=\"Collapse\" onClick=\"toggleshow($bcnt)\">\n";
				}*/
				if ($view==2) {
					echo "<input type=button id=\"buti$icnt\" value=\"Show\" onClick=\"toggleitem($icnt)\">\n";
				} else {
					echo "<input type=button id=\"buti$icnt\" value=\"Hide\" onClick=\"toggleitem($icnt)\">\n";
				}
				
				if ($isteacher) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&move=$child\">Move</a> \n";
				} 
				if ($isteacher || ($ownerid[$child]==$userid && $allowmod)) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=$child\">Modify</a> \n";
				}
				if ($isteacher || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&remove=$child\">Remove</a> \n";
				}
				if ($posttype[$child]!=2 && $myrights > 5 && $allowreply) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=reply&replyto=$child\">Reply</a>";
				}
				echo "</span>\n";
				echo "<b>{$subject[$child]}</b><br/>Posted by: ";
				//if ($isteacher && $ownerid[$child]!=0) {
				//	echo "<a href=\"mailto:{$email[$child]}\">";
				//} else if ($allowmsg && $ownerid[$child]!=0) {
				if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
					echo "<a href=\"../msgs/msglist.php?cid=$cid&add=new&to={$ownerid[$child]}\">";
				}
				echo $poster[$child];
				if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
					echo "</a>";
				}
				if ($isteacher && $ownerid[$child]!=0 && $ownerid[$child]!=$userid) {
					 echo " <a class=\"small\" href=\"$imasroot/course/gradebook.php?cid=$cid&stu={$ownerid[$child]}\" target=\"_popoutgradebook\">[GB]</a>";
				}
				echo ', ';
				echo tzdate("F j, Y, g:i a",$date[$child]);
				
				if ($date[$child]>$lastview) {
					echo " <span style=\"color:red;\">New</span>\n";
				}
				
				echo "</div>\n";
				if ($view==2) {
					echo "<div class=hidden id=\"item$icnt\">";
				} else {
					echo "<div class=blockitems id=\"item$icnt\">";
				}
				echo filter($message[$child]);
				echo "<div class=\"clear\"></div></div>\n";
				$icnt++;
				if (isset($children[$child])) { //if has children
					echo "<div class=";
					if ($view==0 || $view==2) {
						echo '"forumgrp"';
					} else if ($view==1) {
						echo '"hidden"';
					}
					echo " id=\"block$bcnt\">\n";
					$bcnt++;
					printchildren($child, ($posttype[$child]==3 && !$isteacher));
					echo "</div>\n";
				}
			//}
		}
	}
	if ($isteacher && $haspoints) {
		echo "<form method=post action=\"thread.php?cid=$cid&forum=$forumid&page=$page&score=true\">";
	}
	printchildren(0);
	if ($isteacher && $haspoints) {
		echo "<div><input type=submit value=\"Save Grades\" /></div>";
		echo "</form>";
	}
	echo "<img src=\"$imasroot/img/expand.gif\" style=\"visibility:hidden\" />";
	echo "<img src=\"$imasroot/img/collapse.gif\" style=\"visibility:hidden\" />";
	
	echo "<script type=\"text/javascript\">";
	echo "var bcnt =$bcnt; var icnt = $icnt;\n";
	echo "</script>";
	echo "<div class=right><a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Back to Forum Topics</a></div>\n";
	require("../footer.php");
?>
