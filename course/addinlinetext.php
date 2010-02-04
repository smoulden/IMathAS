<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require("../includes/parsedatetime.php");

/*** pre-html data manipulation, including function code *******/

function generatemoveselect($count,$num) {
	$num = $num+1;  //adjust indexing
	$html = "<select id=\"ms-$num\" onchange=\"movefile($num)\">\n";
	for ($i = 1; $i <= $count; $i++) {
		$html .= "<option value=\"$i\" ";
		if ($i==$num) { $html .= "selected=1";}
		$html .= ">$i</option>\n";
	}
	$html .= "</select>\n";
	return $html;
}

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "text";


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Inline Text\n";
	$pagetitle = "Modify Inline Text";
} else {
	$curBreadcrumb .= "&gt; Add Inline Text\n";
	$pagetitle = "Add Inline Text";
}	
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
}

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = $_GET['cid'];
	$block = $_GET['block'];	
	$page_formActionTag = "addinlinetext.php?block=$block&cid=$cid&folder=" . $_GET['folder'];
	$page_formActionTag .= "&tb=$totb";
	
	if ($_POST['title']!= null || $_POST['text']!=null || $_POST['sdate']!=null) { //if the form has been submitted
		if ($_POST['sdatetype']=='0') {
			$startdate = 0;
		} else {
			$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		}
		if ($_POST['edatetype']=='2000000000') {
			$enddate = 2000000000;
		} else {
			$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
		}
		if (isset($_POST['hidetitle'])) {
			$_POST['title']='##hidden##';
		}

		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script');
		$_POST['text'] = addslashes(htmLawed(stripslashes($_POST['text']),$htmlawedconfig));

		$filestoremove = array();
		if (isset($_GET['id'])) {  //already have id; update
			$query = "UPDATE imas_inlinetext SET title='{$_POST['title']}',text='{$_POST['text']}',startdate=$startdate,enddate=$enddate,avail='{$_POST['avail']}',";
			$query .= "oncal='{$_POST['oncal']}',caltag='{$_POST['caltag']}' ";
			$query .= "WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			//update attached files
			$query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if (isset($_POST['delfile-'.$row[0]])) {
					$filestoremove[] = $row[0];
					$query = "DELETE FROM imas_instr_files WHERE id='{$row[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$query = "SELECT id FROM imas_instr_files WHERE filename='{$row[2]}'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($r2)==0) {
						$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
						unlink($uploaddir . $row[2]);
					}
				} else if ($_POST['filedescr-'.$row[0]]!=$row[1]) {
					$query = "UPDATE imas_instr_files SET description='{$_POST['filedescr-'.$row[0]]}' WHERE id='{$row[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}	
			$newtextid = $_GET['id'];
		} else { //add new
			
			$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate,avail,oncal,caltag) VALUES ";
			$query .= "('$cid','{$_POST['title']}','{$_POST['text']}',$startdate,$enddate,'{$_POST['avail']}','{$_POST['oncal']}','{$_POST['caltag']}');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$newtextid = mysql_insert_id();
			
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "('$cid','InlineText','$newtextid');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$itemid = mysql_insert_id();
						
			$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$items = unserialize($line['itemorder']);
			
			$blocktree = explode('-',$block);
			$sub =& $items;
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
			if ($totb=='b') {
				$sub[] = $itemid;
			} else if ($totb=='t') {
				array_unshift($sub,$itemid);
			}
			$itemorder = addslashes(serialize($items));
			$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
		}
		if ($_FILES['userfile']['name']!='') { 
			$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			$userfilename = preg_replace('/[^\w\.]/','',basename($_FILES['userfile']['name']));
			$filename = $userfilename;
			$extension = strtolower(strrchr($userfilename,"."));
			$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
			if (in_array($extension,$badextensions)) {
				$overwriteBody = 1;
				$body = "<p>File type is not allowed</p>";
			} else {
				$uploadfile = $uploaddir . $filename;
				$t=0;
				while(file_exists($uploadfile)){
					$filename = substr($filename,0,strpos($userfilename,"."))."_$t".strstr($userfilename,".");
					$uploadfile=$uploaddir.$filename;
					$t++;
				}
				
				if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
					//echo "<p>File is valid, and was successfully uploaded</p>\n";
					if (trim($_POST['newfiledescr'])=='') {
						$_POST['newfiledescr'] = $filename;
					}
					$query = "INSERT INTO imas_instr_files (description,filename,itemid) VALUES ('{$_POST['newfiledescr']}','$filename','$newtextid')";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					$addedfile = mysql_insert_id();
					$_GET['id'] = $newtextid;
				} else {
					$overwriteBody = 1;
					$body = "<p>Error uploading file!</p>\n";
				}
			}
		}
	} 

	if (isset($addedfile) || count($filestoremove)>0 || isset($_GET['movefile'])) {
		$query = "SELECT fileorder FROM imas_inlinetext WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$fileorder = explode(',',mysql_result($result,0,0));
		if ($fileorder[0]=='') {
			$fileorder = array();
		}
		if (isset($addedfile)) {
			$fileorder[] = $addedfile;
		}
		if (count($filestoremove)>0) {
			for ($i=0; $i<count($filestoremove); $i++) {
				$k = array_search($filestoremove[$i],$fileorder);
				if ($k!==FALSE) {
					array_splice($fileorder,$k,1);
				}
			}
		}
		if (isset($_GET['movefile'])) {
			$from = $_GET['movefile'];
			$to = $_GET['movefileto'];
			$itemtomove = $fileorder[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
			array_splice($fileorder,$from-1,1);
			array_splice($fileorder,$to-1,0,$itemtomove);
		}
		$fileorder = implode(',',$fileorder);
		$query = "UPDATE imas_inlinetext SET fileorder='$fileorder' WHERE id='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	if ($_POST['submitbtn']=='Submit') {
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		exit;
	}
	
	if (isset($_GET['id'])) {
		$query = "SELECT * FROM imas_inlinetext WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($line['title']=='##hidden##') {
			$hidetitle = true;
			$line['title']='';
		}
		$startdate = $line['startdate'];
		$enddate = $line['enddate'];
		$fileorder = explode(',',$line['fileorder']);
	} else {
		//set defaults
		$line['title'] = "Enter title here";
		$line['text'] = "<p>Enter text here</p>";
		$line['avail'] = 1;
		$line['oncal'] = 0;
		$line['caltag'] = '!';
		$startdate = time();
		$enddate = time() + 7*24*60*60;
		$hidetitle = false;
		$fileorder = array();
	}   
	if ($startdate!=0) {
		$sdate = tzdate("m/d/Y",$startdate);
		$stime = tzdate("g:i a",$startdate);
	} else {
		$sdate = tzdate("m/d/Y",time());
		$stime = tzdate("g:i a",time());
	}
	if ($enddate!=2000000000) {
		$edate = tzdate("m/d/Y",$enddate);
		$etime = tzdate("g:i a",$enddate);	
	} else {
		$edate = tzdate("m/d/Y",time()+7*24*60*60);
		$etime = tzdate("g:i a",time()+7*24*60*60);
	}    
	
	if (isset($_GET['id'])) {
	$query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$_GET['id']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$page_fileorderCount = count($fileorder);
	$i = 0;
	$page_FileLinks = array();
	if (mysql_num_rows($result)>0) {
		while ($row = mysql_fetch_row($result)) {
			$filedescr[$row[0]] = $row[1];
			$filenames[$row[0]] = rawurlencode($row[2]);
		}
		foreach ($fileorder as $k=>$fid) {
			$page_FileLinks[$k]['link'] = $filenames[$fid];
			$page_FileLinks[$k]['desc'] = $filedescr[$fid];
			$page_FileLinks[$k]['fid'] = $fid;

			//echo generatemoveselect(count($fileorder),$k);
			//echo "<a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">View</a> ";
			//echo "<input type=\"text\" name=\"filedescr-$fid\" value=\"{$filedescr[$fid]}\"/> Delete? <input type=checkbox name=\"delfile-$fid\"/><br/>";	
		}
	}
} 
	
$page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";	
}
	
	
 /******* begin html output ********/
 $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?> 	
<script type="text/javascript">
function movefile(from) {
	var to = document.getElementById('ms-'+from).value; 
	var address = "http://<?php echo $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addinlinetext.php?cid=$cid&block=$block&id=" . $_GET['id'] ?>";
	
	if (to != from) {
 	var toopen = address + '&movefile=' + from + '&movefileto=' + to;
  	window.location = toopen;
  }
}
</script>


	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<div id="headeraddinlinetext" class="pagetitle"><h2><?php echo $pagetitle ?><img src="<?php echo $imasroot ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=inlinetextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></h2></div>

	<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
	<span class=form>Title: </span>
	<span class=formright>
		<input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$line['title']);?>"><br/>
		<input type="checkbox" name="hidetitle" value="1" <?php writeHtmlChecked($hidetitle,true) ?>/>
		Hide title and icon
	</span><BR class=form>
	
	Text:<BR>
	<div class=editor>
		<textarea cols=60 rows=20 id=text name=text style="width: 100%"><?php echo htmlentities($line['text']);?></textarea>
	</div>
	
	<span class=form>
	Attached Files:</span>
	<span class=wideformright>

<?php
	if (isset($_GET['id'])) {
		foreach ($page_FileLinks as $k=>$arr) {
			echo generatemoveselect($page_fileorderCount,$k);
?>			
		<a href="<?php echo $imasroot ?>/course/files/<?php echo $arr['link'] ?>" target="_blank">
		View</a>
		<input type="text" name="filedescr-<?php echo $arr['fid'] ?>" value="<?php echo $arr['desc'] ?>"/>
		Delete? <input type=checkbox name="delfile-<?php echo $arr['fid'] ?>"/><br/>	
<?php			
		}
	} 
?>

		<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
		New file<sup>*</sup>: <input type="file" name="userfile"/><br/>
		Description: <input type="text" name="newfiledescr"/><br/>
		<input type=submit name="submitbtn" value="Add / Update Files"/>
	</span><br class=form>
	
	<div>
		<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?>/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?>/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($line['avail'],2);?>/>Show Always<br/>
		</span><br class="form"/>
		<span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/>
			 Always until end date<br/>
			<input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?>/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>">
		</span><BR class=form>
	
		<span class=form>Available Until:</span>
		<span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/> 
			Always after start date<br/>
			<input type=radio name="edatetype" value="edate" <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>
		<span class=form>Place on Calendar?</span>
		<span class=formright>
			<input type=radio name="oncal" value=0 <?php writeHtmlChecked($line['oncal'],0); ?> /> No<br/>
			<input type=radio name="oncal" value=1 <?php writeHtmlChecked($line['oncal'],1); ?> /> Yes, on Available after date (will only show after that date)<br/>
			<input type=radio name="oncal" value=2 <?php writeHtmlChecked($line['oncal'],2); ?> /> Yes, on Available until date<br/>
			With tag: <input name="caltag" type=text size=1 maxlength=1 value="<?php echo $line['caltag'];?>"/>
		</span><br class="form" />
		
	</div>
	<div class=submit><input type=submit name="submitbtn" value="Submit"></div>
	</form>
	<p><sup>*</sup>Avoid quotes in the filename</p>
<?php
}
	require("../footer.php");
?>
