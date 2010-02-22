<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

	
 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Review Library";
$page_updatedMsg = "";
 
	//CHECK PERMISSIONS AND SET FLAGS
if ($myrights<20) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {
	//data manipulation here
	
	$isadmin = false;
	$isgrpadmin = false; 
	
	$cid = $_GET['cid'];
	if (isset($_GET['source'])) {
		$source = $_GET['source'];
	} else {
		$source = 0;
	}
	
	if ($_GET['cid']==="admin") {
		$curBreadcrumb = "$breadcrumbbase <a href=\"../admin/admin.php\">Admin</a>";
		$curBreadcrumb .= "&gt; <a href=\"managelibs.php?cid=admin\">Manage Libraries</a> &gt; Review Library";
		if ($myrights == 100) {
			$isadmin = true;
		} else if ($myrights==75) {
			$isgrpadmin = true;
		}
	} else if ($_GET['cid']==0) {
		$curBreadcrumb = "<a href=\"../index.php\">Home</a> ";
		$curBreadcrumb .= "&gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Review Library";
	} else {
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a>";
		$curBreadcrumb .= "&gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Review Library";
	}
		
	if (!isset($_REQUEST['lib'])) {

		if (isset($sessiondata['lastsearchlibs'])) {
			//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
			$inlibs = $sessiondata['lastsearchlibs'];
		} else {
			$inlibs = '0';
		}
		if (substr($inlibs,0,1)=='0') {
			$lnames[] = "Unassigned";
		}
		$inlibssafe = "'".implode("','",explode(',',$inlibs))."'";
		$query = "SELECT name FROM imas_libraries WHERE id IN ($inlibssafe)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$lnames[] = $row[0];
		}
		$lnames = implode(", ",$lnames);

	} else {
	
		$lib = $_REQUEST['lib'];
		if (isset($_GET['offset'])) {
			$offset = $_GET['offset'];
		} else {
			$offset = 0;
		}
		
		$query = "SELECT count(qsetid) FROM imas_library_items WHERE libid='$lib'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$cnt = mysql_result($result,0,0);
		if ($cnt==0) {
			$overwriteBody = 1;
			$body = "Library empty";
		}
		
		$query = "SELECT qsetid FROM imas_library_items WHERE libid='$lib' LIMIT $offset,1";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$qsetid = mysql_result($result,0,0);
		
		
		if (isset($_POST['remove']) || isset($_POST['delete'])) {
			if (!isset($_POST['confirm'])) {
				
				if (isset($_POST['remove'])) {
					$page_ConfirmMsg = "<p>Are you SURE you want to remove this question from this library?</p><input type=hidden name=remove value=1>";
				} 
				if (isset($_POST['delete'])) {
					$page_ConfirmMsg = "<p>Are you SURE you want to delete this question?  Question will be removed from ALL libraries.</p><input type=hidden name=delete value=1>";
				}

			} else {
				if (isset($_POST['delete'])) {
					if ($isgrpadmin) {
						$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE ";
						$query .= "imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
						$query .= "imas_questionset.id='$qsetid'";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						if (mysql_num_rows($result)>0) {
							$query = "DELETE FROM imas_questionset WHERE id='{$_GET['remove']}'";
							$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
							if (mysql_affected_rows()>0) {
								$query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid'";
								$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
								delqimgs($qsetid);
								$cnt--;
							}
						} 
					} else {
						$query = "DELETE FROM imas_questionset WHERE id='$qsetid'";
						if (!$isadmin) {
							$query .= " AND ownerid='$userid'";
						}
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						if (mysql_affected_rows()>0) {
							$query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid'";
							$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
							delqimgs($qsetid);
							$cnt--;
						}
					}
					//$query = "DELETE FROM imas_questionset WHERE id='$qsetid'";
					
				}
				if (isset($_POST['remove'])) {
					$madechange = false;
					if ($isgrpadmin) {
						//$query = "DELETE imas_library_items FROM imas_library_items,imas_users WHERE ";
						//$query .= "imas_library_items.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
						//$query .= "imas_library_items.qsetid='$qsetid' AND imas_library_items.libid='$libid'";
						$query = "SELECT imas_library_items FROM imas_library_items,imas_users WHERE ";
						$query .= "imas_library_items.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
						$query .= "imas_library_items.qsetid='$qsetid' AND imas_library_items.libid='$libid'";
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						if (mysql_num_rows($result)>0) {
							$query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid' AND libid='$libid'";
							$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
							if (mysql_affected_rows()>0) {
								$madechange = true;
							}
						}
							
					} else {
						$query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid' AND libid='$libid'";
						if (!$isadmin) {
							$query .= " AND ownerid='$userid'";
						}
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						if (mysql_affected_rows()>0) {
							$madechange = true;
						}
					}
					
					if ($madechange) {
						$query = "SELECT id FROM imas_library_items WHERE qsetid='$qsetid'";
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						if (mysql_num_rows($result)==0) {
							$query = "INSERT INTO imas_library_items (qsetid,libid,ownerid) VALUES ";
							$query .= "('$qsetid',0,$userid)";
							mysql_query($query) or die("Query failed : $query " . mysql_error());
						}
						$cnt--;
					}
				}
		
				if ($offset==$cnt) { //Just deleted last problem in library
					if ($offset == 0) { //if already on first question
						$overwriteBody = 1;
						$body = "Library empty";
					} else {  //go back to last question
						$offset--;
					}
				}
				$query = "SELECT qsetid FROM imas_library_items WHERE libid='$lib' LIMIT $offset,1";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$qsetid = mysql_result($result,0,0);
			}
		} elseif (isset($_POST['update'])) {
			$_POST['qtext'] = preg_replace('/<([^<>]+?)>/',"&&&L$1&&&G",$_POST['qtext']);
			$_POST['qtext'] = str_replace(array("<",">"),array("&lt;","&gt;"),$_POST['qtext']);
			$_POST['qtext'] = str_replace(array("&&&L","&&&G"),array("<",">"),$_POST['qtext']);
			$_POST['description'] = str_replace(array("<",">"),array("&lt;","&gt;"),$_POST['description']);
			$now = time();

			if ($isgrpadmin) {
				$query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
				$query .= "WHERE iq.id='$qsetid' AND iq.ownerid=imas_users.id AND (imas_users.groupid='$groupid' OR iq.userights>2)";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$query = "UPDATE imas_questionset SET description='{$_POST['description']}',";
					$query .= "qtype='{$_POST['qtype']}',control='{$_POST['control']}',qcontrol='{$_POST['qcontrol']}',";
					$query .= "qtext='{$_POST['qtext']}',answer='{$_POST['answer']}',lastmoddate=$now ";
					$query .= "WHERE id='$qsetid'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				}

			} else {
				$query = "UPDATE imas_questionset SET description='{$_POST['description']}',";
				$query .= "qtype='{$_POST['qtype']}',control='{$_POST['control']}',qcontrol='{$_POST['qcontrol']}',";
				$query .= "qtext='{$_POST['qtext']}',answer='{$_POST['answer']}',lastmoddate=$now ";
				$query .= "WHERE id='$qsetid'";
				if (!$isadmin) { $query .= " AND (ownerid='$userid' OR userights>2);";}
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			}
			
			$page_updatedMsg = "Question Updated. ";	
			
		} 
	
		//DEFAULT DISPLAY
		if ($offset>0) {
			$last = $offset -1;
			$page_lastLink =  "<a href=\"reviewlibrary.php?cid=$cid&source=$source&offset=$last&lib=$lib\">Last</a> ";
		} else {
			$page_lastLink = "Last ";
		}
		
		if ($offset<$cnt-1) {
			$next = $offset +1;
			$page_nextLink = "<a href=\"reviewlibrary.php?cid=$cid&source=$source&offset=$next&lib=$lib\">Next</a>";
		} else {
			$page_nextLink = "Next";
		}
	
	
		//$query = "SELECT * FROM imas_questionset WHERE id=$qsetid";
		$query = "SELECT imas_library_items.ownerid,imas_users.groupid FROM imas_library_items,imas_users WHERE ";
		$query .= "imas_library_items.ownerid=imas_users.id AND imas_library_items.libid='$lib'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$row = mysql_fetch_row($result);
		$myli = ($row[0]==$userid);
		if ($isadmin || ($isgrpadmin && $row[1]==$groupid)) {
			$myli = true;
		}
		
		$query = "SELECT imas_questionset.*,imas_users.groupid FROM imas_questionset,imas_users WHERE ";
		$query .= "imas_questionset.ownerid=imas_users.id AND imas_questionset.id='$qsetid'";	
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$lineQSet = mysql_fetch_array($result, MYSQL_ASSOC);
		$myq = ($lineQSet['ownerid']==$userid);
		if ($isadmin || ($isgrpadmin && $lineQSet['groupid']==$groupid) || $lineQSet['userights']==3) {
			$myq = true;
		}
		
		$page_deleteForm = "";
		if ($myq || $myli) {
			$page_deleteForm .= "<form method=post action=\"reviewlibrary.php?cid=$cid&source=$source&offset=$offset&lib=$lib\">\n";
			if ($myq) {$page_deleteForm .=  "<input type=submit name=delete value=\"Delete\">\n";}
			if ($myli) {$page_deleteForm .=  "<input type=submit name=remove value=\"Remove from Library\">\n";}
			$page_deleteForm .=  "</form>\n";
		}
		
		$seed = rand(0,10000);
		require("../assessment/displayq2.php");
		if (isset($_POST['seed'])) {
			$score = scoreq(0,$qsetid,$_POST['seed'],$_POST['qn0']);
			$page_lastScore = "<p>Score on last answer: $score/1</p>\n";
		}
		
		$twobx = ($lineQSet['qcontrol']=='' && $lineQSet['answer']=='');
		
		if (!$myq) {
			$page_canModifyMsg = "<p>This question is not set to allow you to modify the code.  You can only view the code and make additional library assignments</p>";
		}
	}
}

/******* begin html output ********/
$sessiondata['coursetheme'] = $coursetheme;
require("../assessment/header.php");

if ($overwriteBody==1) {
	echo $body;
} else { //DISPLAY BLOCK HERE
?>
	
	<div class="breadcrumb"><?php echo $curBreadcrumb; ?></div> 
	<div id="headerreviewlibrary" class="pagetitle"><h2>Review Library</h2></div>
<?php	
	if (!isset($_REQUEST['lib'])) {
?>	
	<script>
		var curlibs = '<?php echo $inlibs ?>';
		function libselect() {
			window.open('libtree.php?libtree=popup&type=radio&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
		}
		function setlib(libs) {
			if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
				libs = libs.substring(2);
			}
			document.getElementById("lib").value = libs;
			curlibs = libs;
		}
		function setlibnames(libn) {
			if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
				libn = libn.substring(11);
			}
			document.getElementById("libnames").innerHTML = libn;
		}
	</script>
		
	<form method=post action="reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=0">	
		Library to review: 
		<span id="libnames"><?php echo $lnames ?></span>
		<input type=hidden name="lib" id="lib" size="10" value="<?php echo $inlibs ?>">
		<input type=button value="Select Libraries" onClick="libselect()"><br/>
		<input type=submit value=Submit>
	</form>

<?php
		
	} elseif (isset($_POST['remove']) || isset($_POST['delete']))  {

		if (!isset($_POST['confirm'])) {
?>		
	<form method=post action="reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>">
		<?php echo $page_ConfirmMsg; ?>
		<p><input type=submit name="confirm" value="Yes, I'm Sure">
		<input type=button value="Never Mind" onclick="window.location='reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>'"></p>
	</form>

<?php
		}
	
	} else { //DEFAULT DISPLAY HERE
?>

	<p style="color: red;"><?php echo $page_updatedMsg; ?></p>
	<p><?php echo $page_lastLink . " | " . $page_nextLink; ?></p>

	<h4><?php echo $qsetid ?>: <?php echo $lineQSet['description'] ?></h4>
	
	<div><?php echo  $page_deleteForm; ?></div>
	<div><?php echo  $page_lastScore; ?></div>
	
	<form method=post action="reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>" onsubmit="doonsubmit()">
		<input type=hidden name=seed value="<?php echo $seed ?>">
		
<?php
		unset($lastanswers);
		displayq(0,$qsetid,$seed,true,true,0);
?>
		<input type=submit value="Submit">
	</form>
<?php		
		if ($source==0) {
			echo "	<p><a href=\"reviewlibrary.php?cid=$cid&offset=$offset&lib=$lib&source=1\">View/Modify Question Code</a></p>\n";
		} else {
?>
	<p>
		<a href="reviewlibrary.php?cid=<?php echo $cid ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>&source=0">
			Don't show Question Code
		</a>
	</p>
	<form method=post action="reviewlibrary.php?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>">
		<div><?php echo $page_canModifyMsg; ?></div>
			
	
		<script>
			function swapentrymode() {
				var butn = document.getElementById("entrymode");
				if (butn.value=="2-box entry") {
					document.getElementById("qcbox").style.display = "none";
					document.getElementById("abox").style.display = "none";
					document.getElementById("control").rows = 20;
					butn.value = "4-box entry";
				} else {
					document.getElementById("qcbox").style.display = "block";
					document.getElementById("abox").style.display = "block";
					document.getElementById("control").rows = 10;
					butn.value = "2-box entry";
				}
			}
			function incboxsize(box) {
				document.getElementById(box).rows += 1;
			}
			function decboxsize(box) {
				if (document.getElementById(box).rows > 1) 
					document.getElementById(box).rows -= 1;
			}
		</script>
		
		<input type=submit name="update" value="Update"><br/>
		
		Description:<BR> 
		<textarea cols=60 rows=4 name=description <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['description'];?></textarea>
		
		<p>
			Question type: 
			<select name=qtype <?php if (!$myq) echo "disabled=\"disabled\"";?>>
				<option value="number" <?php if ($lineQSet['qtype']=="number") {echo "SELECTED";} ?>>Number</option>
				<option value="calculated" <?php if ($lineQSet['qtype']=="calculated") {echo "SELECTED";} ?>>Calculated Number</option>
				<option value="choices" <?php if ($lineQSet['qtype']=="choices") {echo "SELECTED";} ?>>Multiple-Choice</option>
				<option value="multans" <?php if ($lineQSet['qtype']=="multans") {echo "SELECTED";} ?>>Multiple-Answer</option>
				<option value="matching" <?php if ($lineQSet['qtype']=="matching") {echo "SELECTED";} ?>>Matching</option>
				<option value="numfunc" <?php if ($lineQSet['qtype']=="numfunc") {echo "SELECTED";} ?>>Function</option>
				<option value="string" <?php if ($lineQSet['qtype']=="string") {echo "SELECTED";} ?>>String</option>
				<option value="essay" <?php if ($lineQSet['qtype']=="essay") {echo "SELECTED";} ?>>Essay</option>
				<option value="draw" <?php if ($lineQSet['qtype']=="draw") {echo "SELECTED";} ?>>Drawing</option>
				<option value="ntuple" <?php if ($lineQSet['qtype']=="ntuple") {echo "SELECTED";} ?>>N-Tuple</option>
				<option value="calcntuple" <?php if ($lineQSet['qtype']=="calcntuple") {echo "SELECTED";} ?>>Calculated N-Tuple</option>
				<option value="matrix" <?php if ($lineQSet['qtype']=="matrix") {echo "SELECTED";} ?>>Numerical Matrix</option>
				<option value="calcmatrix" <?php if ($lineQSet['qtype']=="calcmatrix") {echo "SELECTED";} ?>>Calculated Matrix</option>
				<option value="interval" <?php if ($lineQSet['qtype']=="interval") {echo "SELECTED";} ?>>Interval</option>
				<option value="calcinterval" <?php if ($lineQSet['qtype']=="calcinterval") {echo "SELECTED";} ?>>Calculated Interval</option>
				<option value="complex" <?php if ($lineQSet['qtype']=="complex") {echo "SELECTED";} ?>>Complex</option>
				<option value="calccomplex" <?php if ($lineQSet['qtype']=="calccomplex") {echo "SELECTED";} ?>>Calculated Complex</option>
				<option value="file" <?php if ($lineQSet['qtype']=="file") {echo "SELECTED";} ?>>File Upload</option>
				<option value="multipart" <?php if ($lineQSet['qtype']=="multipart") {echo "SELECTED";} ?>>Multipart</option>
			</select>
		</p>
		
		<p>
			<a href="#" onclick="window.open('<?php echo $imasroot;?>/help.php?section=writingquestions','Help','width=400,height=300,toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420))">Writing Questions Help</a> 
			<a href="#" onclick="window.open('<?php echo $imasroot;?>/assessment/libs/libhelp.php','Help','width=400,height=300,toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420))">Macro Library Help</a><BR>
			Switch to: 
			<input type=button id=entrymode value="<?php if ($twobx) {echo "4-box entry";} else {echo "2-box entry";}?>" onclick="swapentrymode()" <?php if ($lineQSet['qcontrol']!='' || $lineQSet['answer']!='') echo "DISABLED"; ?>/>
			
		</p>
		<div id=ccbox>
			Common Control: 
			<span class=pointer onclick="incboxsize('control')">[+]</span>
			<span class=pointer onclick="decboxsize('control')">[-]</span><BR>
			<textarea cols=60 rows=<?php if ($twobx) {echo "20";} else {echo "10";}?> id=control name=control <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['control'];?></textarea>
		</div>
		<div id=qcbox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
			Question Control: 
			<span class=pointer onclick="incboxsize('qcontrol')">[+]</span>
			<span class=pointer onclick="decboxsize('qcontrol')">[-]</span><BR>
			<textarea cols=60 rows=10 id=qcontrol name=qcontrol <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['qcontrol'];?></textarea>
		</div>
		<div id=qtbox>
			Question Text: 
			<span class=pointer onclick="incboxsize('qtext')">[+]</span>
			<span class=pointer onclick="decboxsize('qtext')">[-]</span><BR>
			<textarea cols=60 rows=10 id=qtext name=qtext <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['qtext'];?></textarea>
		</div>
		<div id=abox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
			Answer: 
			<span class=pointer onclick="incboxsize('answer')">[+]</span>
			<span class=pointer onclick="decboxsize('answer')">[-]</span><BR>
			<textarea cols=60 rows=10 id=answer name=answer <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['answer'];?></textarea>
		</div>
		<input type=submit name="update" value="Update">
	</form>

<?php	
		}
	}
}

require("../footer.php");


	
function delqimgs($qsid) {
	$query = "SELECT id,filename,var FROM imas_qimages WHERE qsetid='$qsid'";
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$query = "SELECT id FROM imas_qimages WHERE filename='{$row[1]}'";
		$r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
		if (mysql_num_rows($r2)==1) { //don't delete if file is used in other questions
			unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
		}
		$query = "DELETE FROM imas_qimages WHERE id='{$row[0]}'";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
	}
}
?>
	
