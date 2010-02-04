<?php
//IMathAS:  Alt main page
//(c) 2010 David Lippman

/*** master includes ***/
require("./validate.php");

//0: classes you're teaching
//1: classes you're tutoring
//2: classes you're taking
//10: messages widget
//11: forum posts widget

//pagelayout:  array of arrays.  pagelayout[0] = fullwidth header, [1] = left bar 25%, [2] = rigth bar 75%
//[3]: 0 for newmsg note next to courses, 1 for newpost note next to courses
$query = "SELECT homelayout FROM imas_users WHERE id='$userid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$homelayout = mysql_result($result,0,0);

$pagelayout = explode('|',$homelayout);
foreach($pagelayout as $k=>$v) {
	if ($v=='') {
		$pagelayout[$k] = array();
	} else {
		$pagelayout[$k] = explode(',',$v);
	}
}
//$pagelayout = array(array(),array(0,1,2),array(10,11),array());

$shownewmsgnote = in_array(0,$pagelayout[3]);
$shownewpostnote = in_array(1,$pagelayout[3]);

$showmessagesgadget = (in_array(10,$pagelayout[1]) || in_array(10,$pagelayout[0]) || in_array(10,$pagelayout[2]));
$showpostsgadget = (in_array(11,$pagelayout[1]) || in_array(11,$pagelayout[0]) || in_array(11,$pagelayout[2]));

$twocolumn = (count($pagelayout[1])>0 && count($pagelayout[2])>0);

$placeinhead = ' 
  <style type="text/css">
   div.pagetitle h2 {
  	margin-top: 0px;
  	}
  </style>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	
$nologo = true;


//check for new posts - do for each type if there are courses
$newpostscnt = array();
$postcheckcids = array();
$page_coursenames = array();
$page_newpostlist = array();

$newmsgcnt = array();
if ($showmessagesgadget) {
	$page_newmessagelist = array();
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.courseid ";
	$query .= "FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom WHERE ";
	$query .= "imas_msgs.msgto='$userid' AND (imas_msgs.isread=0 OR imas_msgs.isread=4)";
	$query .= "ORDER BY senddate DESC ";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (!isset($newmsgcnt[$line['courseid']])) {
			$newmsgcnt[$line['courseid']] = 1;
		} else {
			$newmsgcnt[$line['courseid']]++;
		}
		$page_newmessagelist[] = $line;
	}
} else {
	//check for new messages    
	
	$query = "SELECT courseid,COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread=0 OR isread=4) GROUP BY courseid";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$newmsgcnt[$row[0]] = $row[1];
	}
}

$page_studentCourseData = array();

// check to see if the user is enrolled as a student
$query = "SELECT imas_courses.name,imas_courses.id FROM imas_students,imas_courses ";
$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid='$userid' ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ORDER BY imas_courses.name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	$noclass = true;
} else {
	$noclass = false;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$page_studentCourseData[] = $line;	
		$page_coursenames[$line['id']] = $line['name'];
		$postcheckcids[] = $line['id'];
	}
}

$page_teacherCourseData = array();
if ($myrights>10) {
	// check to see if the user is enrolled as a teacher
	$query = "SELECT imas_courses.name,imas_courses.id,imas_courses.available,imas_courses.lockaid FROM imas_teachers,imas_courses ";
	$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid='$userid' ";
	$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ORDER BY imas_courses.name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		$noclass = true;
	} else {
		$noclass = false;
		$tchcids = array();
		
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$page_teacherCourseData[] = $line;	
			$page_coursenames[$line['id']] = $line['name'];
			$postcheckcids[] = $line['id'];
		}
	}
}

$page_tutorCourseData = array();
// check to see if the user is enrolled as a tutor
$query = "SELECT imas_courses.name,imas_courses.id,imas_courses.available,imas_courses.lockaid FROM imas_tutors,imas_courses ";
$query .= "WHERE imas_tutors.courseid=imas_courses.id AND imas_tutors.userid='$userid' ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ORDER BY imas_courses.name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	$noclass = true;
} else {
	$noclass = false;
	$tchcids = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$page_tutorCourseData[] = $line;	
		$page_coursenames[$line['id']] = $line['name'];
		$postcheckcids[] = $line['id'];
	}
}

//get new posts
//check for new posts
$postcidlist = implode(',',$postcheckcids);
if ($showpostsgadget && count($postcheckcids)>0) {
	$postthreads = array();
	$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ($postcidlist) AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ORDER BY imas_forum_threads.lastposttime DESC";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (!isset($newpostcnt[$line['courseid']])) {
			$newpostcnt[$line['courseid']] = 1;
		} else {
			$newpostcnt[$line['courseid']]++;
		}
		if ($newpostcnt[$line['courseid']]<10) {
			$page_newpostlist[] = $line;	
			$postthreads[] = $line['threadid'];
		}
	}
} else if (count($postcheckcids)>0) {
	$query = "SELECT courseid,count(*) FROM ";
	$query .= "(SELECT imas_forums.courseid,imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ";
	$query .= "($postcidlist) AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))) AS newitems ";
	$query .= "GROUP BY courseid";
	$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($r2)) {
		$newpostcnt[$row[0]] = $row[1];
	}
}

/*** done pulling stuff.  Time to display something ***/
require("header.php");
$msgtotal = array_sum($newmsgcnt);
echo '<div class="floatright" id="homelinkbox">';
echo "<a href=\"forms.php?action=chguserinfo\">Change User Info</a> | \n";
echo "<a href=\"forms.php?action=chgpwd\">Change Password</a> | \n";
echo '<a href="actions.php?action=logout">Log Out</a>';
echo '<br/><a href="msgs/msglist.php?cid=0">Messages</a>';
if ($msgtotal>0) {
	echo ' <a href="msgs/newmsglist.php?cid=0" class="newnote">New (' . $msgtotal . ')</a>';
}
if ($myrights > 10) {
	echo " | <a href=\"docs/docs.php\">Documentation</a>\n";
} else if ($myrights > 9) {
	echo " | <a href=\"help.php?section=usingimas\">Help</a>\n";
}
		
echo '</div>';
echo '<div class="pagetitle" id="headerhome"><h2>';
if (isset($CFG['GEN']['hometitle'])) {
	echo $CFG['GEN']['hometitle'];
} else {
	echo "Welcome to $installname, $userfullname";
}
echo '</h2></div>';

echo '<p style="color:#090;">The home page has been improved.  Most of the links that were at the bottom of the ';
echo 'page are now in the upper right.<br/> You can now customize what shows on this page by clicking the Change User Info link</p>';
for ($i=0; $i<3; $i++) {
	if ($i==0) {
		echo '<div id="homefullwidth">';
	}
	if ($twocolumn) {
		if ($i==1) {
			echo '<div id="leftcolumn">';
		} else if ($i==2) {
			echo '<div id="rightcolumn">';
		}
	}
	for ($j=0; $j<count($pagelayout[$i]); $j++) {
		switch ($pagelayout[$i][$j]) {
			case 0: 
				if ($myrights>10) {
					printCourses($page_teacherCourseData,'Courses you\'re teaching','teach');
				}
				break;
			case 1:
				printCourses($page_tutorCourseData,'Courses you\'re tutoring','tutor');
				break;
			case 2: 
				printCourses($page_studentCourseData,'Courses you\'re taking','take');
				break;
			case 10:
				printMessagesGadget();
				break;
			case 11:
				printPostsGadget();
				break;
		}
	}
	if ($i==2 || $twocolumn) {
		echo '</div>';
	}
}

require('./footer.php');


function printCourses($data,$title,$type=null) {
	global $shownewmsgnote, $shownewpostnote;
	if (count($data)==0 && $type=='tutor') {return;}
	global $myrights,$showmessagesgadget,$showpostsgadget,$newmsgcnt,$newpostcnt;
	echo '<div class="block"><h3>'.$title.'</h3></div>';
	echo '<div class="blockitems"><ul class="nomark courselist">';
	for ($i=0; $i<count($data); $i++) {
		echo '<li><a href="course/course.php?folder=0&cid='.$data[$i]['id'].'">';
		echo $data[$i]['name'].'</a>';
		if (isset($data[$i]['available']) && (($data[$i]['available']&1)==1)) {
			echo ' <span style="color:green;">Hidden</span>';
		}
		if (isset($data[$i]['lockaid']) && $data[$i]['lockaid']>0) {
			echo ' <span style="color:green;">Lockdown</span>';
		}
		if ($shownewmsgnote && $newmsgcnt[$data[$i]['id']]>0) {
			echo ' <a class="newnote" href="msgs/msglist.php?cid='.$data[$i]['id'].'">Messages ('.$newmsgcnt[$data[$i]['id']].')</a>';
		}
		if ($shownewpostnote && $newpostcnt[$data[$i]['id']]>0) {
			echo ' <a class="newnote" href="forums/newthreads.php?cid='.$data[$i]['id'].'">Posts ('.$newpostcnt[$data[$i]['id']].')</a>';
		}
		
		echo '</li>';
	}
	echo '</ul>';
	if ($type=='take') {
		echo '<div class="center"><a class="abutton" href="forms.php?action=enroll">Enroll in a New Class</a></div>';
	} else if ($type=='teach' && $myrights>39) {
		echo '<div class="center"><a class="abutton" href="admin/admin.php">Admin Page</a></div>';
	}
	echo '</div>';
}

function printMessagesGadget() {
	global $page_newmessagelist, $page_coursenames;
	echo '<div class="block"><h3>New messages</h3></div>';
	echo '<div class="blockitems">';
	if (count($page_newmessagelist)==0) {
		echo '<p>No new messages</p>';
		echo '</div>';
		return;
	}
	echo '<table class="gb" id="newmsglist"><thead><tr><th>Message</th><th>From</th><th>Course</th><th>Sent</th></tr></thead>';
	echo '<tbody>';
	foreach ($page_newmessagelist as $line) {
		echo '<tr>';
		if (trim($line['title'])=='') {
			$line['title'] = '[No Subject]';
		}
		$n = 0;
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		if ($n==1) {
			$line['title'] = 'Re: '.$line['title'];
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".$line['title'];
		}
		echo "<td><a href=\"msgs/viewmsg.php?cid={$line['courseid']}&type=new&msgid={$line['id']}\">";
		echo $line['title'];
		echo '</a></td>';
		echo '<td>'.$line['LastName'].', '.$line['FirstName'].'</td>';
		echo '<td>'.$page_coursenames[$line['courseid']].'</td>';
		echo '<td>'.tzdate("D n/j/y, g:i a",$line['senddate']).'</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '<script type="text/javascript">initSortTable("newmsglist",Array("S","S","S","D"),false);</script>';
	echo '</div>';
	
}
function printPostsGadget() {
	global $page_newpostlist, $page_coursenames, $postthreads;
	
	echo '<div class="block"><h3>New forum posts</h3></div>';
	echo '<div class="blockitems">';
	if (count($page_newpostlist)==0) {
		echo '<p>No new posts</p>';
		echo '</div>';
		return;
	}
	
	$threadlist = implode(',',$postthreads);
	$threaddata = array();
	$query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
	$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.id IN ($threadlist)";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($tline = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$threaddata[$tline['id']] = $tline;
	}
	
	echo '<table class="gb" id="newpostlist"><thead><tr><th>Thread</th><th>Started By</th><th>Course</th><th>Last Post</th></tr></thead>';
	echo '<tbody>';
	foreach ($page_newpostlist as $line) {
		echo '<tr>';
		$subject = $threaddata[$line['threadid']]['subject'];
		if (trim($subject)=='') {
			$subject = '[No Subject]';
		}
		$n = 0;
		while (strpos($subject,'Re: ')===0) {
			$subject = substr($subject,4);
			$n++;
		}
		if ($n==1) {
			$subject = 'Re: '.$subject;
		} else if ($n>1) {
			$subject = "Re<sup>$n</sup>: ".$subject;
		}
		echo "<td><a href=\"forums/posts.php?page=-3&cid={$line['courseid']}&forum={$line['id']}&thread={$line['threadid']}\">";
		echo $subject;
		echo '</a></td>';
		if ($threaddata[$line['threadid']]['isanon']==1) {
			echo '<td>Anonymous</td>';
		} else {
			echo '<td>'.$threaddata[$line['threadid']]['LastName'].', '.$threaddata[$line['threadid']]['FirstName'].'</td>';
		}
		echo '<td>'.$page_coursenames[$line['courseid']].'</td>';
		echo '<td>'.tzdate("D n/j/y, g:i a",$line['lastposttime']).'</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '<script type="text/javascript">initSortTable("newpostlist",Array("S","S","S","D"),false);</script>';
	
	echo '</div>';
}


?>
