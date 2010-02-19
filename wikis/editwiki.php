<?php
//IMathAS:  Edit Wiki page
//(c) 2010 David Lippman


/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "wikicontent";

$cid = intval($_GET['cid']);
$id = intval($_GET['id']);

if ($cid==0) {
	$overwriteBody=1;
	$body = "You need to access this page with a course id";
} else if ($id==0) {
	$overwriteBody=1;
	$body = "You need to access this page with a wiki id";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	
	
	$curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/course/course.php?cid=$cid\">$coursename</a> &gt; ";
	$curBreadcrumb .= "<a href=\"$imasroot/wikis/viewwiki.php?cid=$cid&id=$id\">View Wiki</a> &gt; Edit Wiki";
	
	$query = "SELECT name,startdate,enddate,editbydate,avail FROM imas_wikis WHERE id='$id'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	$wikiname = $row[0];
	$now = time();
	if (!isset($teacherid) && ($row[4]==0 || ($row[4]==1 && ($now<$row[1] || $now>$row[2])) || $now>$row[3])) {
		$overwriteBody=1;
		$body = "This wiki is not currently available for editing";
	} else {
	
		if ($_POST['wikicontent']!= null) { //FORM SUBMITTED, DATA PROCESSING
			$inconflict = false;
			$stugroupid = 0;
			
			//clean up wiki content
			require_once("../includes/htmLawed.php");
			$htmlawedconfig = array('elements'=>'*-script');
			$wikicontent = htmLawed(stripslashes($_POST['wikicontent']),$htmlawedconfig);
			$wikicontent = str_replace(array("\r","\n"),' ',$wikicontent);
			$wikicontent = preg_replace('/\s+/',' ',$wikicontent);
			$now = time();
			
			//check for conflicts
			$query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName FROM ";
			$query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
			$query .= "WHERE i_w_r.wikiid='$id' ORDER BY id DESC LIMIT 1";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {//editing existing wiki
				$row = mysql_fetch_row($result);
				$revisionid = $row[0];
				if ($revisionid!=$_POST['baserevision']) { //someone else has updated this wiki since we retrieved it
					$inconflict = true;
					$lastedittime = tzdate("F j, Y, g:i a",$row[2]);
					$lasteditedby = $row[3].', '.$row[4];
					$revisiontext = $row[1];
				} else { //we're all good for a diff calculation
					require("../includes/diff.php");
					$diff = diffsparsejson($wikicontent,$row[1]);
					
					if ($diff != '') {
						//verify the diff
						$base = diffstringsplit($wikicontent);
						$diffs = explode('],[',substr($diff,2,strlen($diff)-4));
						for ($i = count($diffs)-1; $i>=0; $i--) {
							$diffs[$i] = explode(',',$diffs[$i]);
							if ($diffs[$i][0]==2) { //replace
								if (count($diffs[$i])>4) {
									$diffs[$i][3] = implode(',',array_slice($diffs[$i],3));
								}
								$diffs[$i][3] = str_replace(array('\\"','\\\\'),array('"','\\'),substr($diffs[$i][3],1,strlen($diffs[$i][3])-2));
								array_splice($base,$diffs[$i][1],$diffs[$i][2],$diffs[$i][3]);
							} else if ($diffs[$i][0]==0) { //insert
								if (count($diffs[$i])>3) {
									$diffs[$i][2] = implode(',',array_slice($diffs[$i],2));
								}
								$diffs[$i][2] = str_replace(array('\\"','\\\\'),array('"','\\'),substr($diffs[$i][2],1,strlen($diffs[$i][2])-2));
								array_splice($base,$diffs[$i][1],0,$diffs[$i][2]);
							} else if ($diffs[$i][0]==1) { //delete
								array_splice($base,$diffs[$i][1],$diffs[$i][2]);
							}
						}
						$comp = diffstringsplit($row[1]);
						if (count(array_diff($comp,$base))>0 || count(array_diff($base,$comp))>0) {
							echo "<p>Uh oh, it appears something weird happened.  Giving up</p>";
							print_r($base);
							print_r($comp);
							exit;
						}
					
					
						$diffstr = addslashes($diff);
						$wikicontent = addslashes($wikicontent);
						//insert latest content
						$query = "INSERT INTO imas_wiki_revisions (wikiid,stugroupid,userid,revision,time) VALUES ";
						$query .= "($id,'$stugroupid','$userid','$wikicontent',$now)";
						mysql_query($query) or die("Query failed : " . mysql_error());
						//replace previous version with diff off current version
						$query = "UPDATE imas_wiki_revisions SET revision='$diffstr' WHERE id='$revisionid'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
				}	
			} else { //no wiki page exists yet - just need to insert revision
				$wikicontent = addslashes($wikicontent);
				$query = "INSERT INTO imas_wiki_revisions (wikiid,stugroupid,userid,revision,time) VALUES ";
				$query .= "($id,'$stugroupid','$userid','$wikicontent',$now)";
				mysql_query($query) or die("Query failed : " . mysql_error());
				
			}
			if (!$inconflict) {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewwiki.php?cid=$cid&id=$id");
				exit;
			}
				
		} else {
			$query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName FROM ";
			$query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
			$query .= "WHERE i_w_r.wikiid='$id' ORDER BY id DESC LIMIT 1";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) { //wikipage exists already
				$row = mysql_fetch_row($result);
				$lastedittime = tzdate("F j, Y, g:i a",$row[2]);
				$lasteditedby = $row[3].', '.$row[4];
				$revisionid = $row[0];
				$revisiontext = str_replace('</span></p>','</span> </p>',$row[1]);
			} else { //new wikipage
				$revisionid = 0;
				$revisiontext = '';
			}
			$inconflict = false;
		}
	}
	
}

//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 $pagetitle = "Edit Wiki: $wikiname";
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  // DISPLAY 	
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headereditwiki" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>

<?php
if ($inconflict) {
?>
	<p><span style="color:#f00;">Conflict</span>.  Someone else has already submitted a revision to this page since you opened it.
	Your submission is displayed here, and the recently submitted revision has been loaded into the editor so you can reapply your
	changes to the current version of the page</p>
	
	<div class="editor wikicontent"><?php echo $wikicontent; ?></div>
<?php
}
if (isset($lasteditedby)) {
	echo "<p>Last Edited by $lasteditedby on $lastedittime</p>";
}
?>
	<form method=post action="editwiki.php?cid=<?php echo $cid;?>&id=<?php echo $id;?>">
	<input type="hidden" name="baserevision" value="<?php echo $revisionid;?>" />
	<div class="editor">
	<textarea cols=60 rows=30 id="wikicontent" name="wikicontent" style="width: 100%">
	<?php echo htmlentities($revisiontext);?></textarea>
	</div>
	
	<div class=submit><input type=submit value=Submit></div>
	</form>		
<?php
}

require("../footer.php");
?>
