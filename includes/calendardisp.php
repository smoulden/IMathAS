<?php

function showcalendar($refpage) {

global $imasroot,$cid,$userid,$teacherid,$previewshift,$latepasses;

$now= time();
if ($previewshift!=-1) {
	$now = $now + $previewshift;
}
$today = $now;

if (isset($_GET['calpageshift'])) {
	$pageshift = $_GET['calpageshift'];
} else {
	$pageshift = 0;
}
$today = $today + $pageshift*28*24*60*60;

$dayofweek = date('w',$today);
$dayofmo = date('j',$today);
$curmo = date('M',$today);
$longcurmo = date('F',$today);
$curyr = date('Y',$today);
$curmonum = date('n',$today);
$daysinmo = date('t',$today);
$lastmo = date('M',$today - ($dayofmo+2)*24*60*60);
$lastmonum = date('n',$today - ($dayofmo+2)*24*60*60);
$daysinlastmo = date('t',$today - ($dayofmo+2)*24*60*60);
$nextmo = date('M',$today + ($daysinmo-$dayofmo+2)*24*60*60);
$nextmonum = date('n',$today + ($daysinmo-$dayofmo+2)*24*60*60);

$hdrs = array();
$ids = array();

for ($i=0;$i<$dayofweek;$i++) {
	$curday = $dayofmo - $dayofweek + $i;
	if ($curday<1) {
		if ($i==0) {
			$hdrs[0][$i] = $lastmo . " " . ($daysinlastmo+$curday);
			$ids[0][$i] = $lastmonum.'-'.($daysinlastmo + $curday);
		} else {
			$hdrs[0][$i] = ($daysinlastmo + $curday);
			$ids[0][$i] = $lastmonum.'-'.($daysinlastmo + $curday);
		}
	} else {
		if ($i==0) {
			$hdrs[0][$i] = $curmo . " " . $curday;
			$ids[0][$i] = $curmonum.'-'.$curday;
		} else {
			$hdrs[0][$i] = $curday;
			$ids[0][$i] = $curmonum.'-'.$curday;
		}
	}
	$dates[$ids[0][$i]] = date('l F j, Y',$today - ($dayofweek - $i)*24*60*60);
}

for ($i=$dayofweek;$i<28;$i++) {
	$row = floor($i/7);
	$col = $i%7;
	$curday = $dayofmo -$dayofweek+ $i;
	if ($curday > $daysinmo) {
		if ($curday == $daysinmo+1) {
			$hdrs[$row][$col] = $nextmo." 1";
		} else {
			$hdrs[$row][$col] = $curday - $daysinmo;
		}
		$ids[$row][$col] = $nextmonum.'-'.($curday - $daysinmo);
	} else {
		if ($curday==1) {
			$hdrs[0][$i] = $curmo . " " . $curday;
		} else {
			$hdrs[$row][$col] = $curday;
		}
		$ids[$row][$col] = $curmonum.'-'.$curday;
	}
	$dates[$ids[$row][$col]] = date('l F j, Y',$today + ($i-$dayofweek)*24*60*60);
}

?>


<?php
//echo '<div class="floatleft">Jump to <a href="'.$refpage.'.php?calpageshift=0&cid='.$cid.'">Now</a></div>';
echo '<div class=center><a href="'.$refpage.'.php?calpageshift='.($pageshift-1).'&cid='.$cid.'">&lt; &lt;</a> ';
//echo $longcurmo.' ';

if ($pageshift==0) {
	echo "Now ";
} else {
	echo '<a href="'.$refpage.'.php?calpageshift=0&cid='.$cid.'">Now</a> ';
}
echo '<a href="'.$refpage.'.php?calpageshift='.($pageshift+1).'&cid='.$cid.'">&gt; &gt;</a> ';
echo '</div> ';
echo "<table class=\"cal\" >";  //onmouseout=\"makenorm()\"

$exlowertime = mktime(0,0,0,$curmonum,$dayofmo - $dayofweek,$curyr);
$lowertime = max($now,$exlowertime);
$uppertime = mktime(0,0,0,$curmonum,$dayofmo - $dayofweek + 28,$curyr);

$exceptions = array();
if (!isset($teacherid)) {
	$query = "SELECT assessmentid,startdate,enddate FROM imas_exceptions WHERE userid='$userid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$exceptions[$row[0]] = array($row[1],$row[2]);
	}
}

/*
$gbcats = array();
$query = "SELECT id,UPPER(SUBSTRING(name,1,1)) FROM imas_gbcats WHERE courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if ($row[1]==strtolower($row[1])) {
		continue;
	} else {
		$gbcats[$row[0]] = $row[1];
	}
}
*/
$assess = array();
$colors = array();
$tags = array();
$k = 0;
$query = "SELECT id,name,startdate,enddate,reviewdate,gbcategory,reqscore,reqscoreaid,timelimit,allowlate,caltag,calrtag FROM imas_assessments WHERE avail=1 AND courseid='$cid' AND enddate<2000000000";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if (isset($exceptions[$row[0]])) {
		$row[2] = $exceptions[$row[0]][0];
		$row[3] = $exceptions[$row[0]][1];
	}
	if ($row[3]>$uppertime && ($row[4]==0 || $row[4]>$uppertime || $row[4]<$row[3])) {
		continue;
	}
	if ($row[3]<$now && $row[4]>$uppertime) { 
		continue;
	}
	//echo "{$row[1]}, {$row[3]}, $uppertime, {$row[4]}<br/>";
	if (($row[2]>$now && !isset($teacherid))) {  //if startdate is past now
		continue;
	} 
	if ($row[4]>0 && $now>$row[4] && !isset($teacherid)) { //if has reviewdate and we're past it   //|| ($now>$row[3] && $row[4]==0)
		//continue;
	}
	
	if (!isset($teacherid) && $row[6]>0) {
		$query = "SELECT bestscores FROM imas_assessment_sessions WHERE assessmentid='{$row[7]}' AND userid='$userid'";
		   $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		   if (mysql_num_rows($r2)==0) {
			   continue;
		   } else {
			   $scores = mysql_result($r2,0,0);
			   if (getpts($scores)<$row[6]) {
				   continue;
			   }
		   }
	}
	if ($row[4]<$uppertime && $row[4]>0 && $now>$row[3]) { //has review, and we're past enddate
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[4]));
		$row[1] = str_replace('"','\"',$row[1]);
		$tag = $row[11];
		if ($now<$row[4]) { $colors[$k] = '#99f';} else {$colors[$k] = '#ccc';}
		$assess[$moday][$k] = "{type:\"AR\", time:\"$time\", tag:\"$tag\", ";
		if ($now<$row[4] || isset($teacherid)) { $assess[$moday][$k] .= "id:\"$row[0]\",";}
		$assess[$moday][$k] .=  "color:\"".$colors[$k]."\",name:\"$row[1]\"".((isset($teacherid))?", editlink:true":"")."}";
	} else if ($row[3]<$uppertime && $row[3]>$exlowertime) {// taking out "hide if past due" && ($now<$row[3] || isset($teacherid))) {
		/*if (isset($gbcats[$row[5]])) {
			$tag = $gbcats[$row[5]];
		} else {
			$tag = '?';
		}*/
		$tag = $row[10];
		if ($row[9]==1 && $latepasses>0) {
			$lp = 1;
		} else {
			$lp = 0;
		}
		$tags[$k] = $tag;
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[3]));
		$row[1] = str_replace('"','\"',$row[1]);
		$colors[$k] = makecolor2($row[2],$row[3],$now);
		$assess[$moday][$k] = "{type:\"A\", time:\"$time\", ";
		if ($now<$row[3] || isset($teacherid)) { $assess[$moday][$k] .= "id:\"$row[0]\",";}
		$assess[$moday][$k] .= "name:\"$row[1]\", color:\"".$colors[$k]."\", allowlate:\"$lp\", tag:\"$tag\"".(($row[8]!=0)?", timelimit:true":"").((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
	} 
	$tags[$k] = $tag;
	$k++;
}
//if (isset($teacherid)) {
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";
//} else {
//	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$lowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";  //chg 10/23/09: replace $now with $uppertime
//}
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if ($row[1]=='##hidden##') {
		$row[1] = strip_tags( $row[3]);
	}
	if ($row[5]==1) {
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[4]));
	} else {
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[2]));
	}
	$row[1] = str_replace('"','\"',$row[1]);
	$colors[$k] = makecolor2($row[4],$row[2],$now);
	$assess[$moday][$k] = "{type:\"I\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\", color:\"".$colors[$k]."\", tag:\"{$row[6]}\"".((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
	$tags[$k] = $row[6];
	$k++;
}
//$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_linkedtext WHERE ((oncal=2 AND enddate>$lowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";
//if (isset($teacherid)) {
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_linkedtext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";
//} else {
//	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_linkedtext WHERE ((oncal=2 AND enddate>$lowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";
//}
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if ($row[5]==1) {
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[4]));
	} else {
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[2]));
	}
	$row[1] = str_replace('"','\"',$row[1]);
	 if ((substr($row[3],0,4)=="http") && (strpos($row[3]," ")===false)) { //is a web link
		   $alink = trim($row[3]);
	   } else if (substr($row[3],0,5)=="file:") {
		   $filename = substr($row[3],5);
		   $alink = $imasroot . "/course/files/".$filename;
	   } else {
		   $alink = '';
	   }
	$colors[$k] = makecolor2($row[4],$row[2],$now);
	$assess[$moday][$k] = "{type:\"L\", time:\"$time\", ";
	if (isset($teacherid) || ($now<$row[2] && $now>$row[4])) {
		$assess[$moday][$k] .= "id:\"$row[0]\", ";
	}
	$assess[$moday][$k] .= "name:\"$row[1]\", link:\"$alink\", color:\"".$colors[$k]."\", tag:\"{$row[6]}\"".((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
	$tags[$k] = $row[6];
	$k++;
}
$query = "SELECT id,name,postby,replyby,startdate FROM imas_forums WHERE enddate>$exlowertime AND ((postby>$exlowertime AND postby<$uppertime) OR (replyby>$exlowertime AND replyby<$uppertime)) AND avail>0 AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if (($row[4]>$now && !isset($teacherid))) {
		continue;
	}
	if ($row[2]!=2000000000) { //($row[2]>$now || isset($teacherid))
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[2]));
		$row[1] = str_replace('"','\"',$row[1]);
		$colors[$k] = makecolor2($row[4],$row[2],$now);
		$assess[$moday][$k] = "{type:\"FP\", time:\"$time\", ";
		if ($row[2]>$now || isset($teacherid)) {
			$assess[$moday][$k] .= "id:\"$row[0]\",";
		}
		$assess[$moday][$k] .= "name:\"$row[1]\", color:\"".$colors[$k]."\"".((isset($teacherid))?", editlink:true":"")."}";
		$k++;
	}
	if ($row[3]!=2000000000) { //($row[3]>$now || isset($teacherid)) 
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[3]));
		$colors[$k] = makecolor2($row[4],$row[3],$now);
		$assess[$moday][$k] = "{type:\"FR\", time:\"$time\",";
		if ($row[3]>$now || isset($teacherid)) {
			$assess[$moday][$k] .= "id:\"$row[0]\",";
		}
		$assess[$moday][$k] .= "name:\"$row[1]\", color:\"".$colors[$k]."\"".((isset($teacherid))?", editlink:true":"")."}";
		$k++;	
	}
}

$query = "SELECT title,tag,date FROM imas_calitems WHERE date>$exlowertime AND date<$uppertime and courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	list($moday,$time) = explode('~',date('n-j~g:i a',$row[2]));
	$row[0] = str_replace('"','\"',$row[0]);
	$assess[$moday][$k] = "{type:\"C\", time:\"$time\", tag:\"$row[1]\", name:\"$row[0]\"}";
	$tags[$k] = $row[1];
	$k++;
}

$jsarr = '{';
foreach ($dates as $moday=>$val) {
	if ($jsarr!='{') {
		$jsarr .= ',';
	}
	if (isset($assess[$moday])) {
		$jsarr .= '"'.$moday.'":{date:"'.$dates[$moday].'",data:['.implode(',',$assess[$moday]).']}';
	} else {
		$jsarr .= '"'.$moday.'":{date:"'.$dates[$moday].'"}';
	}
}
$jsarr .= '}';
		
echo '<script type="text/javascript">';
echo "cid = $cid;";
echo "caleventsarr = $jsarr;";
echo '</script>';
echo "<thead><tr><th>Sunday</th> <th>Monday</th> <th>Tuesday</th> <th>Wednesday</th> <th>Thursday</th> <th>Friday</th> <th>Saturday</th></tr></thead>";
echo "<tbody>";
for ($i=0;$i<count($hdrs);$i++) {
	echo "<tr>";
	for ($j=0; $j<count($hdrs[$i]);$j++) {
		if ($i==0 && $j==$dayofweek && $pageshift==0) { //onmouseover="makebig(this)"
			echo '<td id="'.$ids[$i][$j].'" onclick="showcalcontents(this)" class="today"><div class="td"><span class=day>'.$hdrs[$i][$j]."</span><div class=center>";
		
		} else {
			echo '<td id="'.$ids[$i][$j].'" onclick="showcalcontents(this)" ><div class="td"><span class=day>'.$hdrs[$i][$j]."</span><div class=center>";
		}
		if (isset($assess[$ids[$i][$j]])) {
			foreach ($assess[$ids[$i][$j]] as $k=>$info) {
				//echo $assess[$ids[$i][$j]][$k];
				if (strpos($info,'type:"AR"')!==false) {
					echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">{$tags[$k]}</span> ";
				} else if (strpos($info,'type:"A"')!==false) {
					echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">{$tags[$k]}</span> ";
				} else if (strpos($info,'type:"F')!==false) { 
					echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">F</span> ";
				} else if (strpos($info,'type:"C')!==false) { 
					echo "<span class=\"calitem\" style=\"background-color: #0ff;\">{$tags[$k]}</span> ";
				} else { //textitems
					if (isset($tags[$k])) {
						echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">{$tags[$k]}</span> ";
					} else {
						echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">!</span> ";
					}
				}
			}
		}
		echo "</div></div></td>";
	}
	echo "</tr>";
}
echo "</tbody></table>";

echo "<div style=\"margin-top: 10px; padding:10px; border:1px solid #000;\">";
echo '<span class=right><a href="#" onclick="showcalcontents('.(1000*($today - $dayofweek*24*60*60)).'); return false;"/>Show all</a></span>';
echo "<div id=\"caleventslist\"></div><div class=\"clear\"></div></div>";
if ($pageshift==0) {
	echo "<script>showcalcontents(document.getElementById('{$ids[0][$dayofweek]}'));</script>";
}

}
?>
