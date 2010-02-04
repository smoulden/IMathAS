<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
//$pagetitle = "Manage Student Groups";
//$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=" . $_GET['cid'] . "\">$coursename</a> ";


if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else {
	
	$cid = $_GET['cid'];
	
	if (isset($_POST['chgcnt'])) {
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		
		$cnt = $_POST['chgcnt'];
		$blockchg = 0;
		for ($i=0; $i<$cnt; $i++) {
			require_once("parsedatetime.php");
			if ($_POST['sdatetype'.$i]=='0') {
				$startdate = 0;
			} else {
				$startdate = parsedatetime($_POST['sdate'.$i],$_POST['stime'.$i]);
			}
			
			if ($_POST['edatetype'.$i]=='0') {
				$enddate = 2000000000;
			} else {
				$enddate = parsedatetime($_POST['edate'.$i],$_POST['etime'.$i]);
			}
			
			if (isset($_POST['rdatetype'.$i])) {
				if ($_POST['rdatetype'.$i]=='0') {
					$reviewdate = $_POST['rdatean'.$i];
				} else {
					$reviewdate = parsedatetime($_POST['rdate'.$i],$_POST['rtime'.$i]);
				}
			}
			
			$type = $_POST['type'.$i];
			$id = $_POST['id'.$i];
			if ($type=='Assessment') {
				if ($id>0) {
					$query = "UPDATE imas_assessments SET startdate='$startdate',enddate='$enddate',reviewdate='$reviewdate' WHERE id='$id'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($type=='Forum') {
				if ($id>0) {
					$query = "UPDATE imas_forums SET startdate='$startdate',enddate='$enddate' WHERE id='$id'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($type=='InlineText') {
				if ($id>0) {
					$query = "UPDATE imas_inlinetext SET startdate='$startdate',enddate='$enddate' WHERE id='$id'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($type=='LinkedText') {
				if ($id>0) {
					$query = "UPDATE imas_linkedtext SET startdate='$startdate',enddate='$enddate' WHERE id='$id'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($type=='Block') {
				$blocktree = explode('-',$id);
				$sub =& $items;
				if (count($blocktree)>1) {
					for ($j=1;$j<count($blocktree)-1;$j++) {
						$sub =& $sub[$blocktree[$j]-1]['items']; //-1 to adjust for 1-indexing
					}
				}
				$sub =& $sub[$blocktree[$j]-1];
				$sub['startdate'] = $startdate;
				$sub['enddate'] = $enddate;
				$blockchg++;
			}
			
		}
		if ($blockchg>0) {
			$itemorder = addslashes(serialize($items));
			$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
			
		exit;
	} else { //DEFAULT DATA MANIPULATION
		$pagetitle = "Mass Change Dates";
		$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/masschgdates.js\"></script>";
		$placeinhead .= "<style>.show {display:inline;} \n .hide {display:none;} img {cursor:pointer;}\n</style>";
	}
}	


/******* begin html output ********/
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {		
	
	$shortdays = array("Su","M","Tu","W","Th","F","Sa");
	function getshortday($atime) {
		global $shortdays;
		return $shortdays[date('w',$atime)];
	}
	

	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";	
	echo "&gt; Mass Change Dates</div>\n";
	echo '<div id="headermasschgdates" class="pagetitle"><h2>Mass Change Dates</h2></div>';
	echo '<script type="text/javascript">';
	echo 'var basesdates = new Array(); var baseedates = new Array(); var baserdates = new Array();';
	echo '</script>';
	
	if (isset($_GET['orderby'])) {
		$orderby = $_GET['orderby'];
		$sessiondata['mcdorderby'.$cid] = $orderby;
		writesessiondata();
	} else if (isset($sessiondata['mcdorderby'.$cid])) {
		$orderby = $sessiondata['mcdorderby'.$cid];
	} else {
		$orderby = 0;
	}
	if (isset($_GET['filter'])) {
		$filter = $_GET['filter'];
		$sessiondata['mcdfilter'.$cid] = $filter;
		writesessiondata();
	} else if (isset($sessiondata['mcdfilter'.$cid])) {
		$filter = $sessiondata['mcdfilter'.$cid];
	} else {
		$filter = "all";
	}
	echo "<script type=\"text/javascript\">var filteraddr = \"$imasroot/course/masschgdates.php?cid=$cid&orderby=$orderby\";";
	
	echo "var orderaddr = \"$imasroot/course/masschgdates.php?cid=$cid&filter=$filter\";</script>";
	
	echo '<p>Order by: <select id="orderby" onchange="chgorderby()">';
	echo '<option value="0" ';
	if ($orderby==0) {echo 'selected="selected"';}
	echo '>Start Date</option>';
	echo '<option value="1" ';
	if ($orderby==1) {echo 'selected="selected"';}
	echo '>End Date</option>';
	echo '<option value="2" ';
	if ($orderby==2) {echo 'selected="selected"';}
	echo '>Name</option>';
	echo '</select> ';
	
	echo 'Filter by type: <select id="filter" onchange="filteritems()">';
	echo '<option value="all" ';
	if ($filter=='all') {echo 'selected="selected"';}
	echo '>All</option>';
	echo '<option value="assessments" ';
	if ($filter=='assessments') {echo 'selected="selected"';}
	echo '>Assessments</option>';
	echo '<option value="inlinetext" ';
	if ($filter=='inlinetext') {echo 'selected="selected"';}
	echo '>Inline Text</option>';
	echo '<option value="linkedtext" ';
	if ($filter=='linkedtext') {echo 'selected="selected"';}
	echo '>Linked Text</option>';
	echo '<option value="forums" ';
	if ($filter=='forums') {echo 'selected="selected"';}
	echo '>Forums</option>';
	echo '<option value="blocks" ';
	if ($filter=='blocks') {echo 'selected="selected"';}
	echo '>Blocks</option>';
	echo '</select>';
	echo '</p>';
	
	echo "<p><input type=checkbox id=\"onlyweekdays\" checked=\"checked\"> Shift by weekdays only</p>";
	echo "<p>Once changing dates in one row, you can click <i>Send Down List</i> to send the date change ";
	echo "difference to all rows below.  Click the <img src=\"$imasroot/img/swap.gif\"> icon in each cell to swap from ";
	echo "Always/Never to Dates.  Swaps to/from Always/Never cannot be sent down the list.</p>";
	echo "<form method=post action=\"masschgdates.php?cid=$cid\">";
	
	echo '<p>Check/Uncheck All: <input type="checkbox" name="ca" value="1" onClick="chkAll(this.form, this.checked)"/>. ';
	echo 'Change selected items <select id="swaptype"><option value="s">Start Date</option><option value="e">End Date</option><option value="r">Review Date</option></select>';
	echo ' to <select id="swapselected"><option value="always">Always/Never</option><option value="dates">Dates</option></select>';
	echo ' <input type="button" value="Go" onclick="MCDtoggleselected(this.form)" />';
	
	echo '<table class=gb><thead><tr><th>Name</th><th>Type</th><th>Start Date</th><th>End Date</th><th>Review Date</th><th>Send Date Chg Down List</th></thead><tbody>';
	
	$names = Array();
	$startdates = Array();
	$enddates = Array();
	$reviewdates = Array();
	$ids = Array();
	$types = Array();
	
	if ($filter=='all' || $filter=='assessments') {
		$query = "SELECT name,startdate,enddate,reviewdate,id FROM imas_assessments WHERE courseid='$cid' ";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$types[] = "Assessment";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = $row[3];
			$ids[] = $row[4];
		}
	}
	if ($filter=='all' || $filter=='inlinetext') {
		$query = "SELECT title,startdate,enddate,id FROM imas_inlinetext WHERE courseid='$cid' ";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$types[] = "InlineText";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = 0;
			$ids[] = $row[3];
		}
	}
	if ($filter=='all' || $filter=='linkedtext') {
		$query = "SELECT title,startdate,enddate,id FROM imas_linkedtext WHERE courseid='$cid' ";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$types[] = "LinkedText";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = 0;
			$ids[] = $row[3];
		}
	}
	if ($filter=='all' || $filter=='forums') {
		$query = "SELECT name,startdate,enddate,id FROM imas_forums WHERE courseid='$cid' ";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$types[] = "Forum";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = 0;
			$ids[] = $row[3];
		}
	}
	if ($filter=='all' || $filter=='blocks') {
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		
		function getblockinfo($items,$parent) {
			global $ids,$types,$names,$startdates,$enddates,$reviewdates,$ids;
			foreach($items as $k=>$item) {
				if (is_array($item)) {
					$ids[] = $parent.'-'.($k+1);
					$types[] = "Block";
					$names[] = stripslashes($item['name']);
					$startdates[] = $item['startdate'];
					$enddates[] = $item['enddate'];
					$reviewdates[] = 0;
					if (count($item['items'])>0) {
						getblockinfo($item['items'],$parent.'-'.($k+1));
					}
				} 
			}
		}
		getblockinfo($items,'0');
	}
	$cnt = 0;
	$now = time();
	if ($orderby==0) {
		asort($startdates);
		$keys = array_keys($startdates);
	} else if ($orderby==1) {
		asort($enddates);
		$keys = array_keys($enddates);
	} else if ($orderby==2) {
		natcasesort($names);
		$keys = array_keys($names);
	}
	foreach ($keys as $i) {
		echo '<tr class=grid>';
		echo '<td>';
		echo "<input type=\"checkbox\" id=\"cb$cnt\" value=\"$cnt\" /> ";
		echo "{$names[$i]}<input type=hidden name=\"id$cnt\" value=\"{$ids[$i]}\"/>";
		echo "<script> basesdates[$cnt] = ";
		if ($startdates[$i]==0) { echo '"NA"';} else {echo $startdates[$i];}
		echo "; baseedates[$cnt] = ";
		if ($enddates[$i]==0 || $enddates[$i]==2000000000) { echo '"NA"';} else {echo $enddates[$i];}
		echo "; baserdates[$cnt] = ";
		if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {echo '"NA"';} else { echo $reviewdates[$i];}
		echo ";</script>";
		echo "</td><td>";
		echo "{$types[$i]}<input type=hidden name=\"type$cnt\" value=\"{$types[$i]}\"/>";
		if ($types[$i]=='Assessment') {
			if ($now>$startdates[$i] && $now<$enddates[$i]) {
				echo " <i><a href=\"addquestions.php?aid={$ids[$i]}&cid=$cid\">Q</a></i>";	
			} else {
				echo " <a href=\"addquestions.php?aid={$ids[$i]}&cid=$cid\">Q</a>";
			}
			echo " <a href=\"addassessment.php?id={$ids[$i]}&cid=$cid&from=mcd\">S</a>\n";
		}
		echo "</td>";
		
		
		echo "<td><img src=\"$imasroot/img/swap.gif\" onclick=\"MCDtoggle('s',$cnt)\"/>";
		if ($startdates[$i]==0) {
			echo "<input type=hidden id=\"sdatetype$cnt\" name=\"sdatetype$cnt\" value=\"0\"/>";
		} else {
			echo "<input type=hidden id=\"sdatetype$cnt\" name=\"sdatetype$cnt\" value=\"1\"/>";
		}
		if ($startdates[$i]==0) {
			echo "<span id=\"sspan0$cnt\" class=\"show\">Always</span>";
		} else {
			echo "<span id=\"sspan0$cnt\" class=\"hide\">Always</span>";
		}
		if ($startdates[$i]==0) {
			echo "<span id=\"sspan1$cnt\" class=\"hide\">";
		} else {
			echo "<span id=\"sspan1$cnt\" class=\"show\">";
		}
		if ($startdates[$i]==0) {
			$startdates[$i] = time();
		}
		$sdate = tzdate("m/d/Y",$startdates[$i]);
		$stime = tzdate("g:i a",$startdates[$i]);
		
		echo "<input type=text size=10 id=\"sdate$cnt\" name=\"sdate$cnt\" value=\"$sdate\" onblur=\"ob(this)\"/>(";
		echo "<span id=\"sd$cnt\">".getshortday($startdates[$i]).'</span>';
		//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].sdate$cnt,'anchor$cnt','MM/dd/yyyy',document.forms[0].sdate$cnt.value); return false;\" NAME=\"anchor$cnt\" ID=\"anchor$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		echo ") <a href=\"#\" onClick=\"displayDatePicker('sdate$cnt', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		
		echo " at <input type=text size=8 id=\"stime$cnt\" name=\"stime$cnt\" value=\"$stime\">";
		echo '</span></td>';
		
		echo "<td><img src=\"$imasroot/img/swap.gif\"  onclick=\"MCDtoggle('e',$cnt)\"/>";
		if ($enddates[$i]==2000000000) {
			echo "<input type=hidden id=\"edatetype$cnt\" name=\"edatetype$cnt\" value=\"0\"/>";
		} else {
			echo "<input type=hidden id=\"edatetype$cnt\" name=\"edatetype$cnt\" value=\"1\"/>";
		}
		if ($enddates[$i]==2000000000) {
			echo "<span id=\"espan0$cnt\" class=\"show\">Always</span>";
		} else {
			echo "<span id=\"espan0$cnt\" class=\"hide\">Always</span>";
		}
		if ($enddates[$i]==2000000000) {
			echo "<span id=\"espan1$cnt\" class=\"hide\">";
		} else {
			echo "<span id=\"espan1$cnt\" class=\"show\">";
		}
		if ($enddates[$i]==2000000000) {
			$enddates[$i]  = $startdates[$i] + 7*24*60*60;
		}
		$edate = tzdate("m/d/Y",$enddates[$i]);
		$etime = tzdate("g:i a",$enddates[$i]);
		
		echo "<input type=text size=10 id=\"edate$cnt\" name=\"edate$cnt\" value=\"$edate\" onblur=\"ob(this)\"/>(";
		echo "<span id=\"ed$cnt\">".getshortday($enddates[$i]).'</span>';
		//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].edate$cnt,'anchor2$cnt','MM/dd/yyyy',document.forms[0].edate$cnt.value); return false;\" NAME=\"anchor2$cnt\" ID=\"anchor2$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		echo ") <a href=\"#\" onClick=\"displayDatePicker('edate$cnt', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		
		echo " at <input type=text size=8 id=\"etime$cnt\" name=\"etime$cnt\" value=\"$etime\">";
		echo '</span></td>';
				
		echo "<td>";
		if ($types[$i]=='Assessment') {
			echo "<img src=\"$imasroot/img/swap.gif\"  onclick=\"MCDtoggle('r',$cnt)\"/>";
			if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
				echo "<input type=hidden id=\"rdatetype$cnt\" name=\"rdatetype$cnt\" value=\"0\"/>";
			} else {
				echo "<input type=hidden id=\"rdatetype$cnt\" name=\"rdatetype$cnt\" value=\"1\"/>";
			}
			if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
				echo "<span id=\"rspan0$cnt\" class=\"show\">";
			} else {
				echo "<span id=\"rspan0$cnt\" class=\"hide\">";
			}
			echo "<input type=radio name=\"rdatean$cnt\" value=\"0\" ";
			if ($reviewdates[$i]!=2000000000) {
				echo 'checked=1';
			} 
			echo " />Never <input type=radio name=\"rdatean$cnt\" value=\"2000000000\" ";
			if ($reviewdates[$i]==2000000000) {
				echo 'checked=1';
			} 
			echo " />Always</span>";
			
			if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
				echo "<span id=\"rspan1$cnt\" class=\"hide\">";
			} else {
				echo "<span id=\"rspan1$cnt\" class=\"show\">";
			}
			if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
				$reviewdates[$i] = $enddates[$i] + 7*24*60*60;
			}
			$rdate = tzdate("m/d/Y",$reviewdates[$i]);
			$rtime = tzdate("g:i a",$reviewdates[$i]);
		
			echo "<input type=text size=10 id=\"rdate$cnt\" name=\"rdate$cnt\" value=\"$rdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"rd$cnt\">".getshortday($reviewdates[$i]).'</span>';
			//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].rdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].rdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo ") <a href=\"#\" onClick=\"displayDatePicker('rdate$cnt', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		
			echo " at <input type=text size=8 id=\"rtime$cnt\" name=\"rtime$cnt\" value=\"$rtime\"></span>";
		}
		echo '</td>';
		echo "<td><input type=button value=\"Send Down List\" onclick=\"senddown($cnt)\"/></td>";
		echo "</tr>";
		$cnt++;
	}
	echo '</tbody></table>';
	echo "<input type=hidden name=\"chgcnt\" value=\"$cnt\" />";
	echo '<input type=submit value="Save Changes"/>';
	echo '</form>';
	//echo "<script>var acnt = $cnt;</script>";
}
	
require("../footer.php");

?>
