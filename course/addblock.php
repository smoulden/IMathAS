<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

//buildExistBlocksArray constructs $existblocks for use in generating
//the existing block select list on the html form
function buildExistBlocksArray($items,$parent) {
	global $existblocks;
	global $existBlocksVals;
	global $existBlocksLabels;
	
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$existblocks[$parent.'-'.($k+1)] = $item['name'];
			if (count($item['items'])>0) {
				buildExistBlocksArray($item['items'],$parent.'-'.($k+1));
			}
		}
	}
	
	$i=0;
	foreach ($existblocks as $k=>$name) {
		$existBlocksVals[$i]=$k;
		$existBlocksLabels[$i]=stripslashes($name);
		$i++;
	}
}

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Block Settings";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a>";
$curBreadcrumb .= (isset($_GET['id'])) ? "&gt; Modify Block\n" : "&gt; Add Block\n";

if (isset($_GET['id'])) {
	$formTitle = "<div id=\"headeraddblock\" class=\"pagetitle\"><h2>Modify Block <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=blocks','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2></div>\n";
} else {
	$formTitle = "<div id=\"headeraddblock\" class=\"pagetitle\"><h2>Add Block <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=blocks','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2></div>\n";
}
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
}


$cid = $_GET['cid'];

/* page load loop, runs only one set of code based on how the page was loaded, 
current options are (in order of code blocks below):
  - loaded by a NON-teacher
  - form posted to itself with new/modified data
  - teacher modifying existing block
  - teacher adding new block 
***************/
if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif ($_POST['title']!= null) { //form posted to itself with new/modified data, update the block
	require_once("parsedatetime.php");
	if ($_POST['sdatetype']=='0') {
		$startdate = 0;
	} else if ($_POST['sdatetype']=='now') {
		$startdate = time()-2;
	} else {
		$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
	}
	if ($_POST['edatetype']=='2000000000') {
		$enddate = 2000000000;
	} else {
		$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
	}

	if (is_numeric($_POST['fixedheight'])) {
		$fixedheight = intval($_POST['fixedheight']);
	} else {
		$fixedheight = 0;
	}
	
	$grouplimit = array();
	if ($_POST['grouplimit']!='none') {
		$grouplimit[] = $_POST['grouplimit'];
	}
	//$_POST['title'] = str_replace(array(',','\\"','\\\'','~'),"",$_POST['title']);

	$query = "SELECT itemorder,blockcnt FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$items = unserialize(mysql_result($result,0,0));
	$blockcnt = mysql_result($result,0,1);

	if (isset($_GET['block'])) { //adding new
		$blocktree = explode('-',$_GET['block']);
	} else { //modifying existing
		$blocktree = explode('-',$_GET['id']);
		$existingid = array_pop($blocktree) - 1; //-1 adjust for 1-index
	}

	if ($_POST['colors']=="def") {
		$colors = '';
	} else if ($_POST['colors']=="copy") {
		$blocktreecol = explode('-',$_POST['copycolors']);
		$sub2 = $items;
		for ($i=1;$i<count($blocktreecol);$i++) {
			$colors = $sub2[$blocktreecol[$i]-1]['colors'];
			$sub2 = $sub2[$blocktreecol[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
	} else {
		$colors = $_POST['titlebg'].','.$_POST['titletxt'].','.$_POST['bi'];
	}
	if (isset($_POST['public'])) {
		$public = 1;
	} else {
		$public = 0;
	}


	$sub =& $items;
	if (count($blocktree)>1) {
		for ($i=1;$i<count($blocktree);$i++) {
			$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
	}
	if (isset($existingid)) {  //already have id; update
		$sub[$existingid]['name'] = stripslashes($_POST['title']);
		$sub[$existingid]['startdate'] = $startdate;
		$sub[$existingid]['enddate'] = $enddate;
		$sub[$existingid]['avail'] = $_POST['avail'];
		$sub[$existingid]['SH'] = $_POST['showhide'] . $_POST['availbeh'];
		$sub[$existingid]['colors'] = $colors;
		$sub[$existingid]['public'] = $public;
		$sub[$existingid]['fixedheight'] = $fixedheight;
		$sub[$existingid]['grouplimit'] = $grouplimit;	
	} else { //add new
		$blockitems = array();
		$blockitems['name'] = stripslashes($_POST['title']);
		$blockitems['id'] = $blockcnt;
		$blockitems['startdate'] = $startdate;
		$blockitems['enddate'] = $enddate;
		$blockitems['avail'] = $_POST['avail'];
		$blockitems['SH'] = $_POST['showhide'] . $_POST['availbeh'];
		$blockitems['colors'] = $colors;
		$blockitems['public'] = $public;
		$blockitems['fixedheight'] = $fixedheight;
		$blockitems['grouplimit'] = $grouplimit;	
		$blockitems['items'] = array();
		if ($totb=='b') {
			array_push($sub,$blockitems);
		} else if ($totb=='t') {
			array_unshift($sub,$blockitems);
		}
		
		$blockcnt++;
	}
	$itemorder = addslashes(serialize($items));
	$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt=$blockcnt WHERE id='$cid';";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		
	exit;
} else { //it is a teacher but the form has not been posted
	
	if (isset($_GET['id'])) { //teacher modifying existing block, load form with block data
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
			
		$blocktree = explode('-',$_GET['id']);
		$existingid = array_pop($blocktree) - 1; //-1 adjust for 1-index
		$blockitems = $items;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$blockitems = $blockitems[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
			
		$title = stripslashes($blockitems[$existingid]['name']);
		$title = str_replace('"','&quot;',$title);
		$startdate = $blockitems[$existingid]['startdate'];
		$enddate = $blockitems[$existingid]['enddate'];
		if (isset($blockitems[$existingid]['avail'])) { //backwards compat
			$avail = $blockitems[$existingid]['avail'];
		} else {
			$avail = 1;
		}
		if (isset($blockitems[$existingid]['public'])) { //backwards compat
			$public = $blockitems[$existingid]['public'];
		} else {
			$public = 0;
		}
		$showhide = $blockitems[$existingid]['SH'][0];
		if (strlen($blockitems[$existingid]['SH'])==1) {
			$availbeh = 'O';
		} else {
			$availbeh = $blockitems[$existingid]['SH'][1];
		}
		if ($blockitems[$existingid]['colors']=='') {
			$titlebg = "#DDDDFF";
			$titletxt = "#000000";
			$bi = "#EEEEFF";
			$usedef = 1;
		} else {
			list($titlebg,$titletxt,$bi) = explode(',',$blockitems[$existingid]['colors']);
			$usedef = 0;
		}
		$fixedheight = $blockitems[$existingid]['fixedheight'];
		$grouplimit = $blockitems[$existingid]['grouplimit'];
		
			

	} else { //teacher adding new block, load form with default data
		//set defaults
		$title = 'Enter Block name here';
		$startdate = time() + 60*60;
		$enddate = time() + 7*24*60*60;
		$availbeh = 'O';
		$showhide = 'H';
		$avail = 1;
		$public = 0;
		$titlebg = "#DDDDFF";
		$titletxt = "#000000";
		$bi = "#EEEEFF";
		$usedef = 1;
		$fixedheight = 0;
		$grouplimit = array();
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
	}

	//set some default data for use with either the add or modify form
	$existblocks = array();
	$existBlocksVals = array();
	$existBlocksLabels = array();
	buildExistBlocksArray($items,'0');
	
	$page_sectionlistval = array("none");
	$page_sectionlistlabel = array("No restriction");
	$query = "SELECT DISTINCT section FROM imas_students WHERE courseid='$cid' ORDER BY section";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$page_sectionlistval[] = 's-'.$row[0];
		$page_sectionlistlabel[] = 'Section '.$row[0];
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

//anything in the placeinhead variable is inserted in the html doc between the HEAD tags
$placeinhead = "<script type=\"text/javascript\">
function init() {
	var inp1 = document.getElementById(\"titlebg\");
	attachColorPicker(inp1);
	var inp2 = document.getElementById(\"titletxt\");
	attachColorPicker(inp2);
	var inp3 = document.getElementById(\"bi\");
	attachColorPicker(inp3);
}
var imgBase = '$imasroot/javascript/cpimages';
window.onload = init;
</script>";
$placeinhead .= "<style type=\"text/css\">img {	behavior:	 url(\"$imasroot/javascript/pngbehavior.htc\");}</style>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/colorpicker.js\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {
?>

<div class=breadcrumb>
	<?php echo $curBreadcrumb; ?>
</div>	

<?php echo $formTitle; ?>	

<form method=post action="addblock.php?cid=<?php echo $cid; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";} if (isset($_GET['block'])) {echo "&block={$_GET['block']}";}?>&folder=<?php echo $_GET['folder'];?>&tb=<?php echo $totb;?>">
	<span class=form>Title: </span>
	<span class=formright><input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$title);?>"></span>
	<BR class=form>
	<span class=form>Show:</span>
	<span class=formright>
		<input type=radio name="avail" value="0" <?php writeHtmlChecked($avail,0);?>/>Hide<br/>
		<input type=radio name="avail" value="1" <?php writeHtmlChecked($avail,1);?>/>Show by Dates<br/>
		<input type=radio name="avail" value="2" <?php writeHtmlChecked($avail,2);?>/>Show Always<br/>
	</span><br class="form"/>
	
	<span class=form>Available After:</span>
	<span class=formright>
	<input type=radio name="sdatetype" value="0" <?php  writeHtmlChecked($startdate,0) ?>/>
	 Always until end date<br/>
	<input type=radio name="sdatetype" value="now"/> Now<br/>
	<input type=radio name="sdatetype" value="sdate" <?php  writeHtmlChecked($startdate,0,1) ?>/>
	<input type=text size=10 name="sdate" value="<?php echo $sdate;?>"> 
	<a href="#" onClick="displayDatePicker('sdate', this); return false">
	<img src="../img/cal.gif" alt="Calendar"/></a>
	at <input type=text size=10 name=stime value="<?php echo $stime;?>"></span>
	<BR class=form>

	<span class=form>Available Until:</span><span class=formright>
	<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000') ?>/>
	 Always after start date<br/>
	<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
	<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
	<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
	<img src="../img/cal.gif" alt="Calendar"/></a>
	at <input type=text size=10 name=etime value="<?php echo $etime;?>"></span>
	<BR class=form>

	<span class=form>When available:</span>
	<span class=formright>
	<input type=radio name=availbeh value="O" <?php writeHtmlChecked($availbeh,'O')?> />Show Expanded<br/>
	<input type=radio name=availbeh value="C" <?php writeHtmlChecked($availbeh,'C')?> />Show Collapsed<br/>
	<input type=radio name=availbeh value="F" <?php writeHtmlChecked($availbeh,'F')?> />Show as Folder
	</span><br class=form />
	<span class=form>When not available:</span>
	<span class=formright>
	<input type=radio name=showhide value="H" <?php writeHtmlChecked($showhide,'H') ?> />Hide from Students<br/>
	<input type=radio name=showhide value="S" <?php writeHtmlChecked($showhide,'S') ?> />Show Collapsed/as folder
	</span><br class=form />
	
	<span class="form">If expanded, limit height to:</span>
	<span class="formright">
	<input type="text" name="fixedheight" size="4" value="<?php if ($fixedheight>0) {echo $fixedheight;};?>" />pixels (blank for no limit)
	</span><br class="form" />
	
	<span class="form">Restrict access to students in section:</span>
	<span class="formright">
	<?php writeHtmlSelect('grouplimit',$page_sectionlistval,$page_sectionlistlabel,$grouplimit[0]); ?>
	</span><br class="form" />
	
	<span class=form>Make items publicly accessible<sup>*</sup>:</span>
	<span class=formright>
	<input type=checkbox name=public value="1" <?php writeHtmlChecked($public,'1') ?> />
	</span><br class=form />
	<span class=form>Block colors:</span>
	<span class=formright>
	<input type=radio name=colors value="def" <?php  writeHtmlChecked($usedef,1) ?> />Use defaults<br/>
	<input type=radio name=colors value="copy" <?php writeHtmlChecked($usedef,2) ?> />Copy colors from block: 
	
	<?php
	writeHtmlSelect("copycolors",$existBlocksVals,$existBlocksLabels);
	?>

	<br />&nbsp;<br/>
	<input type=radio name=colors value="custom" <?php if ($usedef==0) {echo "CHECKED";}?> />Use custom:
	<table style="display: inline; border-collapse: collapse; margin-left: 15px;">
		<tr>
			<td id="ex1" style="border: 1px solid #000;background-color:
			<?php echo $titlebg;?>;color:<?php echo $titletxt;?>;">
			Sample Title Cell</td>
		</tr>
		<tr>
			<td id="ex2" style="border: 1px solid #000;background-color:
			<?php echo $bi;?>;">&nbsp;sample content cell</td>
		</tr>
	</table>
	<br/>
	<table style=" margin-left: 30px;">
		<tr>
			<td>Title Background: </td>
			<td><input type=text id="titlebg" name="titlebg" value="<?php echo $titlebg;?>" />
			</td>
		</tr>
		<tr>
			<td>Title Text: </td>
			<td><input type=text id="titletxt" name="titletxt" value="<?php echo $titletxt;?>" />
			</td>
		</tr>
		<tr>
			<td>Items Background: </td>
			<td><input type=text id="bi" name="bi" value="<?php echo $bi;?>" />
			</td>
		</tr> 
	</table>
	</span>
	<br class="form"/>
	
	<div class=submit><input type=submit value=Submit></div>
</form>
<p><sup>*</sup>If a parent block is set to be publicly accessible, this block will automatically be publicly accessible, regardless of your selection here.<br/>
Items from publicly accessible blocks can viewed without logging in at http://<?php echo $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') ?>/public.php?cid=<?php echo $_GET['cid'];?>. </p>

<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

<?php
}
	require("../footer.php");

/**** end html code ******/
//nothing after the end of html for this page
/***** cleanup code ******/
//no cleanup code for this page	
?>
