<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require("../includes/parsedatetime.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "text,summary";


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Linked Text\n";
	$pagetitle = "Modify Linked Text";
} else {
	$curBreadcrumb .= "&gt; Add Linked Text\n";
	$pagetitle = "Add Linked Text";
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
	$page_formActionTag = "addlinkedtext.php?block=$block&cid=$cid&folder=" . $_GET['folder'];
	$page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";
	$page_formActionTag .= "&tb=$totb";
	$uploaderror = false;
	if ($_POST['title']!= null) { //if the form has been submitted
		if ($_POST['sdatetype']=='0') {
			$startdate = 0;
		} else if ($_POST['sdatetype']=='now') {
			$startdate = time();
		} else {
			$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		}
		if ($_POST['edatetype']=='2000000000') {
			$enddate = 2000000000;
		} else {
			$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
		}
		if ($_FILES['userfile']['name']!='') {
			$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			//$uploadfile = $uploaddir . "$cid-" . basename($_FILES['userfile']['name']);
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
				while(file_exists($uploadfile)){ //make sure filename is unused
					$filename = substr($filename,0,strpos($userfilename,"."))."_$t".strstr($userfilename,".");
					$uploadfile=$uploaddir.$filename;
					$t++;
				}
				
				if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
					//echo "<p>File is valid, and was successfully uploaded</p>\n";
					$_POST['text'] = "file:$filename";
				} else {
					switch ($_FILES['userfile']['error']) {
						case 1:
						case 2:
							$errormsg = "File size too large";
							break;
						default:
							$errormsg = "Try again";
							break;	
					}
					$_POST['text'] = "File upload error - $errormsg";
					$uploaderror = true;
				}
				//$_POST['text'] = "file:$cid-" . basename($_FILES['userfile']['name']);
				
			}
			
		} else if (substr(trim(strip_tags($_POST['text'])),0,4)=="http") {
			$_POST['text'] = trim(strip_tags($_POST['text']));	
		} else if (substr(trim(strip_tags($_POST['text'])),0,5)=="file:") {
			$_POST['text'] = trim(strip_tags($_POST['text']));
			$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			$filename = substr($_POST['text'],5);
			if (!file_exists($uploaddir . $filename)) {
				$_POST['text'] = '<p>File specified, but file is not on server.  Try uploading again</p>';
			}
		} else {
			require_once("../includes/htmLawed.php");
			$htmlawedconfig = array('elements'=>'*-script');
			$_POST['text'] = addslashes(htmLawed(stripslashes($_POST['text']),$htmlawedconfig));	
		}
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script' );
		$_POST['summary'] = addslashes(htmLawed(stripslashes($_POST['summary']),$htmlawedconfig));
		$_POST['text'] = trim($_POST['text']);
		if (isset($_GET['id'])) {  //already have id; update
			$query = "SELECT text FROM imas_linkedtext WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$text = trim(mysql_result($result,0,0));
			if (substr($text,0,5)=='file:') { //has file
				$safetext = addslashes($text);
				if ($_POST['text']!=$safetext) { //if not same file, delete old if not used
					$query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'"; //any others using file?
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($result)==1) { 
						$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
						$filename = substr($text,5);
						if (file_exists($uploaddir . $filename)) {
							unlink($uploaddir . $filename);
						}
					}
				}
			}
			
			$query = "UPDATE imas_linkedtext SET title='{$_POST['title']}',summary='{$_POST['summary']}',text='{$_POST['text']}',startdate=$startdate,enddate=$enddate,avail='{$_POST['avail']}',oncal='{$_POST['oncal']}',caltag='{$_POST['caltag']}',target='{$_POST['target']}' ";
			$query .= "WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		} else { //add new
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate,avail,oncal,caltag,target) VALUES ";
		$query .= "('$cid','{$_POST['title']}','{$_POST['summary']}','{$_POST['text']}',$startdate,$enddate,'{$_POST['avail']}','{$_POST['oncal']}','{$_POST['caltag']}','{$_POST['target']}');";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		
		$newtextid = mysql_insert_id();
		
		$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
		$query .= "('$cid','LinkedText','$newtextid');";
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
		if ($uploaderror == true) {
			$body = "<p>Error uploading file! $errormsg</p>\n";
			$body .= "<p><a href=\"addlinkedtext.php?cid={$_GET['cid']}";
			if (isset($_GET['id'])) {
				$body .= "&id={$_GET['id']}";
			} else {
				$body .= "&id=$newtextid";
			}
			$body .= "\">Try Again</a></p>\n";
			echo "<html><body>$body</body></html>";
		} else {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		}
		exit;
	} else {
		if (isset($_GET['id'])) {
			$query = "SELECT * FROM imas_linkedtext WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$startdate = $line['startdate'];
			$enddate = $line['enddate'];
		} else {
			//set defaults
			$line['title'] = "Enter title here";
			$line['summary'] = "<p>Enter summary here (displays on course page)</p>";
			$line['text'] = "<p>Enter text here</p>";
			$line['avail'] = 1;
			$line['oncal'] = 0;
			$line['caltag'] = '!';
			$line['target'] = 0;
			$startdate = time();
			$enddate = time() + 7*24*60*60;
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
	}
}
	
/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";

 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?> 	
	
	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<div id="headeraddlinkedtext" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>


	<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
		<span class=form><label for="title">Title:</label></span>
		<span class=formright><input type=text size=60 name=title id=title value="<?php echo str_replace('"','&quot;',$line['title']);?>">
		</span><BR class=form>
		
		Summary<BR>
		<div class=editor>
			<textarea cols=60 rows=10 id=summary name=summary style="width: 100%"><?php echo htmlentities($line['summary']);?></textarea>
		</div>
		<BR>
		Text or weblink (start with http://)<BR>
		<div class=editor>
			<textarea cols=80 rows=20 id=text name=text style="width: 100%"><?php echo htmlentities($line['text']);?></textarea>
		</div>
		<BR>
		<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
		<span class=form><label for="userfile">Or attach file (Max 2MB)<sup>*</sup>:</label></span>
		<span class=formright><input name="userfile" id="userfile" type="file" /></span><br class=form>
		
		<span class="form">Open page in:</span>
		<span class="formright"><fieldset class="invisible"><legend>Open page in:</legend><ul>
			<li><input type=radio name="target" id="targetcurrent" value="0" <?php writeHtmlChecked($line['target'],0);?>/><label for="targetcurrent">Current window/tab</label></li>
			<li><input type=radio name="target" id="targetblank" value="1" <?php writeHtmlChecked($line['target'],1);?>/><label for="targetblank">New window/tab</label></li>
		</ul></fieldset></span><br class="form"/>
		
		<span class=form>Show:</span>
		<span class=formright><fieldset class="invisible"><legend>Show:</legend><ul>
			<li><input type=radio name="avail" id="availhide" value="0" <?php writeHtmlChecked($line['avail'],0);?>/><label for="availhide">Hide</label></li>
			<li><input type=radio name="avail" id="availdates" value="1" <?php writeHtmlChecked($line['avail'],1);?>/><label for="availdates">Show by Dates</label></li>
			<li><input type=radio name="avail" id="availalways" value="2" <?php writeHtmlChecked($line['avail'],2);?>/><label for="availalways">Show Always</label></li>
		</ul></fieldset></span><br class="form"/>
		<span class=form>Available after:</span>
		<span class=formright><fieldset class="invisible"><legend>Available after:</legend><ul>
			<li><input type=radio name="sdatetype" id="suntile" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/> 
			<label for="suntile">Always until end date</label></li>
			<li><input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?>/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>"></li>
		</ul></fieldset></span><BR class=form>
		
		<span class=form>Available until:</span>
		<span class=formright><fieldset class="invisible"><legend>Available until:</legend><ul>
			<li><input type=radio name="edatetype" id="eafters" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/><label for="eafters">Always after start date</label></li>
			<li><input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>"></li>
		</ul></fieldset></span><BR class=form>
		<span class=form>Place on calendar?</span>
		<span class=formright><fieldset class="invisible"><legend>Place on calendar?</legend><ul>
			<li><input type=radio name="oncal" id="calno" value=0 <?php writeHtmlChecked($line['oncal'],0); ?> /><label for="calno">No</label></li>
			<li><input type=radio name="oncal" id="calyesafterd" value=1 <?php writeHtmlChecked($line['oncal'],1); ?> /><label for="calyesafterd">Yes, on Available after date (will only show after that date)</label></li>
			<li><input type=radio name="oncal" id="calyesuntild" value=2 <?php writeHtmlChecked($line['oncal'],2); ?> /><label for="calyesuntild">Yes, on Available until date</label></li></ul>
			<label for="caltag">With tag:</label> <input name="caltag" id="caltag" type=text size=1 value="<?php echo $line['caltag'];?>"/>
		</fieldset></span><br class="form" />
		
		<div class=submit><input type=submit value=Submit></div>	
	</form>
	
	<p><sup>*</sup>Avoid quotes in the filename</p>
<?php
}
	require("../footer.php");
?>
