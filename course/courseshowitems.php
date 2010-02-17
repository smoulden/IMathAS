<?php
//IMathAS:  show items function for main course page
//(c) 2007 David Lippman


function beginitem($canedit,$aname=0) {
	if ($canedit) {
		echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
	 }
	 echo "<div class=item>\n";
	 if ($aname != 0) {
		 echo "<a name=\"$aname\"></a>";
	 }
}
function enditem($canedit) {
	echo "</div>\n";
	if ($canedit) {
		echo '</div>'; //itemwrapper
	}
}

  function showitems($items,$parent,$inpublic=false) {
	   global $teacherid,$tutorid,$cid,$imasroot,$userid,$openblocks,$firstload,$sessiondata,$previewshift,$hideicons,$exceptions,$latepasses,$graphicalicons,$ispublic,$studentinfo,$CFG;
	   
	   if (!isset($CFG['CPS']['itemicons'])) {
	   	   $itemicons = array('folder'=>'folder2.gif', 'assess'=>'assess.png',
			'inline'=>'inline.png',	'web'=>'web.png', 'doc'=>'doc.png', 'wiki'=>'wiki.png',
			'html'=>'html.png', 'forum'=>'forum.png', 'pdf'=>'pdf.png',
			'ppt'=>'ppt.png', 'zip'=>'zip.png', 'png'=>'image.png', 'xls'=>'xls.png',
			'gif'=>'image.png', 'jpg'=>'image.png', 'bmp'=>'image.png', 
			'mp3'=>'sound.png', 'wav'=>'sound.png', 'wma'=>'sound.png', 
			'swf'=>'video.png', 'avi'=>'video.png', 'mpg'=>'video.png', 
			'nb'=>'mathnb.png', 'mws'=>'maple.png', 'mw'=>'maple.png'); 
	   } else {
	   	   $itemicons = $CFG['CPS']['itemicons'];
	   }
	   
	   if (isset($teacherid)) {
		   $canedit = true;
		   $viewall = true;
	   } else if (isset($tutorid)) {
		    $canedit = false;
		    $viewall = true;
	   } else {
		    $canedit = false;
		    $viewall = false;
	   }
	   
	   
	   $now = time() + $previewshift;
	   $blocklist = array();
	   for ($i=0;$i<count($items);$i++) {
		   if (is_array($items[$i])) { //if is a block
			   $blocklist[] = $i+1;
		   }
	   }
	   if ($canedit) {echo generateadditem($parent,'t');}
	   for ($i=0;$i<count($items);$i++) {
		   if (is_array($items[$i])) { //if is a block
			   $turnonpublic = false;
			   if ($ispublic && !$inpublic) {
				   if (isset($items[$i]['public']) && $items[$i]['public']==1) {
					   $turnonpublic = true;
				   } else {
					   continue;
				   }
				   
			   }
			if (isset($items[$i]['grouplimit']) && count($items[$i]['grouplimit'])>0 && !$viewall) {
				if (!in_array('s-'.$studentinfo['section'],$items[$i]['grouplimit'])) {
					continue;
				}
			}  
			$items[$i]['name'] = stripslashes($items[$i]['name']);
			if ($canedit) {
				echo generatemoveselect($i,count($items),$parent,$blocklist);
			}
			if ($items[$i]['startdate']==0) {
				$startdate = "Always";
			} else {
				$startdate = formatdate($items[$i]['startdate']);
			}
			if ($items[$i]['enddate']==2000000000) {
				$enddate = "Always";
			} else {
				$enddate = formatdate($items[$i]['enddate']);
			}
			
			$bnum = $i+1;
			if (in_array($items[$i]['id'],$openblocks)) { $isopen=true;} else {$isopen=false;}
			if (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O') {
				$availbeh = "Expanded";
			} else if ($items[$i]['SH'][1]=='F') {
				$availbeh = "as Folder";
			} else {
				$availbeh = "Collapsed";
			}
			if ($items[$i]['colors']=='') {
				$titlebg = '';
			} else {
				list($titlebg,$titletxt,$bicolor) = explode(',',$items[$i]['colors']);
			}
			if (!isset($items[$i]['avail'])) { //backwards compat
				$items[$i]['avail'] = 1;
			}
			if ($items[$i]['avail']==2 || ($items[$i]['avail']==1 && $items[$i]['startdate']<$now && $items[$i]['enddate']>$now)) { //if "available"
				if ($firstload && (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O')) {
					echo "<script> oblist = oblist + ',".$items[$i]['id']."';</script>\n";
					$isopen = true;
				}
				if ($items[$i]['avail']==2) {
					$show = "Showing $availbeh Always";
				} else {
					$show = "Showing $availbeh $startdate until $enddate";
				}
				if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='F') { //show as folder
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					
					if (($hideicons&16)==0) {
						if ($ispublic) {
							echo "<span class=left><a href=\"public.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						} else {
							echo "<span class=left><a href=\"course.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						}
						if ($graphicalicons) {
							echo "<img src=\"$imasroot/img/{$itemicons['folder']}\"></a></span>";
						} else {
							echo "<img src=\"$imasroot/img/folder.gif\"></a></span>";
						}
						echo "<div class=title>";
					}
					if ($ispublic) {
						echo "<a href=\"public.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
					} else {
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
					}
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">New</span>";
					}
					if ($viewall) { 
						echo '<span class="instrdates">';
						echo "<br>$show ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\" $astyle>Copy</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
						echo '</span>';
					}
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo '<br class="clear" />';
					echo "</div>";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
				} else {
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					
					//echo "<input class=\"floatright\" type=button id=\"but{$items[$i]['id']}\" value=\"";
					//if ($isopen) {echo "Collapse";} else {echo "Expand";}
					//echo "\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">\n";
					
					if (($hideicons&16)==0) {
						echo "<span class=left>";
						echo "<img style=\"cursor:pointer;\" id=\"img{$items[$i]['id']}\" src=\"$imasroot/img/";
						if ($isopen) {echo "collapse";} else {echo "expand";}
						echo ".gif\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\" /></span>";
						echo "<div class=title>";
					}
					if (!$canedit) {
						echo '<span class="right">';
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>Isolate</a>";
						echo '</span>';
					}
					echo "<span class=pointer onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">";
					echo "<b><a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a></b></span> ";
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">New</span>";
					}
					if ($viewall) { 
						echo '<span class="instrdates">';
						echo "<br>$show ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>Isolate</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\" $astyle>Copy</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
						echo '</span>';
					} 
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo "</div>\n";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
					if ($isopen) {
						echo "<div class=blockitems ";
					} else {
						echo "<div class=hidden ";
					}
					$style = '';
					if (isset($items[$i]['fixedheight']) && $items[$i]['fixedheight']>0) {
						if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 6')!==false) {
							$style .= 'overflow: auto; height: expression( this.offsetHeight > '.$items[$i]['fixedheight'].' ? \''.$items[$i]['fixedheight'].'px\' : \'auto\' );';
						} else {
							$style .= 'overflow: auto; max-height:'.$items[$i]['fixedheight'].'px;';
						}
					}
					if ($titlebg!='') {
						$style .= "background-color:$bicolor;";
					}
					if ($style != '') {
						echo "style=\"$style\" ";
					}
					echo "id=\"block{$items[$i]['id']}\">";
					if ($isopen) {
						//if (isset($teacherid)) {echo generateadditem($parent.'-'.$bnum,'t');}
						showitems($items[$i]['items'],$parent.'-'.$bnum,$inpublic||$turnonpublic);
						//if (isset($teacherid) && count($items[$i]['items'])>0) {echo generateadditem($parent.'-'.$bnum,'b');}
					} else {
						echo "Loading content...";
					}
					
					echo "</div>";
				}
			} else if ($viewall || ($items[$i]['SH'][0]=='S' && $items[$i]['avail']>0)) { //if "unavailable"
				if ($items[$i]['avail']==0) {
					$show = "Hidden";
				} else if ($items[$i]['SH'][0] == 'S') {
					$show = "Currently Showing";
					if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='F') {
						$show .= " as Folder. ";
					} else {
						$show .= " Collapsed. ";
					}
					$show .= "Showing $availbeh $startdate to $enddate";
				} else { //currently hidden, using dates
					$show = "Currently Hidden. ";
					$show .= "Showing $availbeh $startdate to $enddate";
				}
				if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='F') { //show as folder
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					if (($hideicons&16)==0) {
						echo "<span class=left><a href=\"course.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						if ($graphicalicons) {
							echo "<img src=\"$imasroot/img/{$itemicons['folder']}\"></a></span>";
						} else {
							echo "<img src=\"$imasroot/img/folder.gif\"></a></span>";
						}
						echo "<div class=title>";
					}
					echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>";
					if ($items[$i]['SH'][0]=='S') {echo "{$items[$i]['name']}</b></a> ";} else {echo "<i>{$items[$i]['name']}</i></b></a>";}
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo " <span style=\"color:red;\">New</span>";
					}
					if ($viewall) { 
						echo '<span class="instrdates">';
						echo "<br><i>$show</i> ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\">Copy</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
						echo '</span>';
					}
					
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo '<br class="clear" />';
					echo "</div>";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
				} else {
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					
					//echo "<input class=\"floatright\" type=button id=\"but{$items[$i]['id']}\" value=\"";
					//if ($isopen) {echo "Collapse";} else {echo "Expand";}
					//echo "\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">\n";
					if (($hideicons&16)==0) {
						echo "<span class=left>";
						echo "<img style=\"cursor:pointer;\" id=\"img{$items[$i]['id']}\" src=\"$imasroot/img/";
						if ($isopen) {echo "collapse";} else {echo "expand";}
						echo ".gif\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\" /></span>";
						echo "<div class=title>";
					}
					
					echo "<span class=pointer onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">";
					echo "<b>";
					if ($items[$i]['SH'][0]=='S') {
						echo "<a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a>";
					} else {
						echo "<i><a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a></i>";
					}
					echo "</b></span> ";
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">New</span>";
					}
					if ($viewall) {
						echo '<span class="instrdates">';
						echo "<br><i>$show</i> ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>Isolate</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a>";
						echo " | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\">Copy</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
						echo '</span>';
					} else {
						echo '<span class="instronly">';
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>Isolate</a>";
						echo '</span>';
					}
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo "</div>\n";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
					if ($isopen) {
						echo "<div class=blockitems ";
					} else {
						echo "<div class=hidden ";
					}
					//if ($titlebg!='') {
					//	echo "style=\"background-color:$bicolor;\"";
					//}
					$style = '';
					if ($items[$i]['fixedheight']>0) {
						if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 6')!==false) {
							$style .= 'overflow: auto; height: expression( this.offsetHeight > '.$items[$i]['fixedheight'].' ? \''.$items[$i]['fixedheight'].'px\' : \'auto\' );';
						} else {
							$style .= 'overflow: auto; max-height:'.$items[$i]['fixedheight'].'px;';
						}
					}
					if ($titlebg!='') {
						$style .= "background-color:$bicolor;";
					}
					if ($style != '') {
						echo "style=\"$style\" ";
					}
					echo "id=\"block{$items[$i]['id']}\">";
					if ($isopen) {
						//if (isset($teacherid)) {echo generateadditem($parent.'-'.$bnum,'t');}
						showitems($items[$i]['items'],$parent.'-'.$bnum,$inpublic||$turnonpublic);
						
						//if (isset($teacherid) && count($items[$i]['items'])>0) {echo generateadditem($parent.'-'.$bnum,'b');}
					} else {
						echo "Loading content...";
					}
					echo "</div>";	
				}
			}
			continue;
		   } else if ($ispublic && !$inpublic) {
			   continue;
		   }
		   $query = "SELECT itemtype,typeid FROM imas_items WHERE id='{$items[$i]}'";
		   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		   $line = mysql_fetch_array($result, MYSQL_ASSOC);
		   
		   if ($canedit) {
			   echo generatemoveselect($i,count($items),$parent,$blocklist);
		   }
		   if ($line['itemtype']=="Calendar") {
			   if ($ispublic) { continue;}
			   //echo "<div class=item>\n";
			   beginitem($canedit);
			   if ($canedit) {
				   echo '<span class="instronly">';
				   echo "<a href=\"addcalendar.php?id={$items[$i]}&block=$parent&cid=$cid&remove=true\">Delete</a>";
				   echo " | <a id=\"mcelink\" href=\"managecalitems.php?cid=$cid\">Manage Events</a>";
				   echo '</span>';
			   }
			   showcalendar("course");
			   enditem($canedit);// echo "</div>";
		   } else if ($line['itemtype']=="Assessment") {
			   if ($ispublic) { continue;}
			   $typeid = $line['typeid'];
			   $query = "SELECT name,summary,startdate,enddate,reviewdate,deffeedback,reqscore,reqscoreaid,avail,allowlate,timelimit FROM imas_assessments WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			   //do time limit mult
			   if (isset($studentinfo['timelimitmult'])) {
				$line['timelimit'] *= $studentinfo['timelimitmult'];
	    		   }
			   if (strpos($line['summary'],'<p>')!==0) {
				   $line['summary'] = '<p>'.$line['summary'].'</p>';
			   }
			   //check for exception
			   if (isset($exceptions[$items[$i]])) {
				   $line['startdate'] = $exceptions[$items[$i]][0];
				   $line['enddate'] = $exceptions[$items[$i]][1];
			   }
			   
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Never";
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['reviewdate']==2000000000) {
				   $reviewdate = "Always";
			   } else {
				   $reviewdate = formatdate($line['reviewdate']);
			   }
			   $nothidden = true;
			   if ($line['reqscore']>0 && $line['reqscoreaid']>0 && !$viewall && $line['enddate']>$now ) {
				   $query = "SELECT bestscores FROM imas_assessment_sessions WHERE assessmentid='{$line['reqscoreaid']}' AND userid='$userid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)==0) {
					   $nothidden = false;
				   } else {
					   $scores = mysql_result($result,0,0);
					   if (getpts($scores)+.02<$line['reqscore']) {
					   	   $nothidden = false;
					   }
				   }
			   }
			   if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now && $nothidden) { //regular show
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   if ($graphicalicons) {
						   echo "<img class=\"floatleft\" src=\"$imasroot/img/{$itemicons['assess']}\" />";
					   } else {
						  echo "<div class=icon style=\"background-color: " . makecolor2($line['startdate'],$line['enddate'],$now) . ";\">?</div>";
					   }
				   }
				   if (substr($line['deffeedback'],0,8)=='Practice') {
					   $endname = "Available until";
				   } else {
					   $endname = "Due";
				   }
				   $line['timelimit'] = abs($line['timelimit']);
				   if ($line['timelimit']>0) {
					   if ($line['timelimit']>3600) {
						$tlhrs = floor($line['timelimit']/3600);
						$tlrem = $line['timelimit'] % 3600;
						$tlmin = floor($tlrem/60);
						$tlsec = $tlrem % 60;
						$tlwrds = "$tlhrs hour";
						if ($tlhrs > 1) { $tlwrds .= "s";}
						if ($tlmin > 0) { $tlwrds .= ", $tlmin minute";}
						if ($tlmin > 1) { $tlwrds .= "s";}
						if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
						if ($tlsec > 1) { $tlwrds .= "s";}
					} else if ($line['timelimit']>60) {
						$tlmin = floor($line['timelimit']/60);
						$tlsec = $line['timelimit'] % 60;
						$tlwrds = "$tlmin minute";
						if ($tlmin > 1) { $tlwrds .= "s";}
						if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
						if ($tlsec > 1) { $tlwrds .= "s";}
					} else {
						$tlwrds = $line['timelimit'] . " second(s)";
					}
				   } else {
					   $tlwrds = '';
				   }
				   echo "<div class=title><b><a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\" ";
				   if ($tlwrds != '') {
					   echo "onclick='return confirm(\"This assessment has a time limit of $tlwrds.  Click OK to start or continue working on the assessment.\")' ";
				   }
				   echo ">{$line['name']}</a></b>";
				   if ($line['enddate']!=2000000000) {
					   echo "<BR> $endname $enddate \n";
				   }
				   if ($canedit) { 
				        echo '<span class="instronly">';
					echo " <i><a href=\"addquestions.php?aid=$typeid&cid=$cid\">Questions</a></i> | <a href=\"addassessment.php?id=$typeid&block=$parent&cid=$cid\">Settings</a></i> \n";
					echo " | <a href=\"deleteassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					echo " | <a href=\"gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">Grades</a>";
					echo '</span>';
					
				   } else if ($line['allowlate']==1 && $latepasses>0) {
					echo " <a href=\"redeemlatepass.php?cid=$cid&aid=$typeid\">Use LatePass</a>";
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
				  
			   } else if ($line['avail']==1 && $line['enddate']<$now && $line['reviewdate']>$now) { //review show // && $nothidden
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   if ($graphicalicons) {
						   echo "<img class=\"floatleft\" src=\"$imasroot/img/{$itemicons['assess']}\" />";
					   } else {
						  echo "<div class=icon style=\"background-color: #99f;\">?</div>";
					   }
				   }
				   echo "<div class=title><b><a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\">{$line['name']}</a></b><BR> Past Due Date.  Showing as Review";
				   if ($line['reviewdate']!=2000000000) { 
					   echo " until $reviewdate \n";
				   }
				   if ($canedit) { 
					echo '<span class="instronly">';   
				   	echo " <i><a href=\"addquestions.php?aid=$typeid&cid=$cid\">Questions</a></i> | <a href=\"addassessment.php?id=$typeid&block=$parent&cid=$cid\">Settings</a>\n";
					echo " | <a href=\"deleteassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					echo " | <a href=\"gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">Grades</a>";
					echo '</span>';
					
				   } 
				   echo filter("<br/><i>This assessment is in review mode - no scores will be saved</i></div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) { //not avail to stu
				   if ($line['avail']==0) {
					   $show = "Hidden";
				   } else {
					   $show = "Available $startdate until $enddate";
					   if ($line['reviewdate']>0 && $line['enddate']!=2000000000) {
						   $show .= ", Review until $reviewdate";
					   }
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   
					   if ($graphicalicons) {
						   echo "<img class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['assess']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: #ccc;\">?</div>";
					   }
				   }
				   echo "<div class=title><i> <a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\" >{$line['name']}</a></i>";
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show</i>\n";
				   echo '</span>';
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addquestions.php?aid=$typeid&cid=$cid\">Questions</a> | <a href=\"addassessment.php?id=$typeid&cid=$cid\">Settings</a> | \n";
					   echo "<a href=\"deleteassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					   echo " | <a href=\"gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">Grades</a>";
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); // echo "</div>\n";
			   }
			   
		   } else if ($line['itemtype']=="InlineText") {
		
			   $typeid = $line['typeid'];
			   $query = "SELECT title,text,startdate,enddate,fileorder,avail FROM imas_inlinetext WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			
			   if (strpos($line['text'],'<p>')!==0) {
				   $line['text'] = '<p>'.$line['text'].'</p>';
			   }
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   if ($line['avail']==2 || ($line['startdate']<$now && $line['enddate']>$now && $line['avail']==1)) {
				   if ($line['avail']==2) {
					   $show = "Showing Always ";
					   $color = '#0f0';
				   } else {
					   $show = "Showing until: $enddate ";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   beginitem($canedit,$items[$i]);// echo "<div class=item>\n";
				   if ($line['title']!='##hidden##') {
					   if (($hideicons&2)==0) {			   
						   if ($graphicalicons) {
							   echo "<img class=\"floatleft\" src=\"$imasroot/img/{$itemicons['inline']}\" />";
						   } else {
							   echo "<div class=icon style=\"background-color: $color;\">!</div>";
						   }
					   }
					   echo "<div class=title> <b>{$line['title']}</b>\n";
					   if ($viewall) { 
						   echo '<span class="instrdates">';
						   echo "<br/>$show ";
						   echo '</span>';
					   }
					   if ($canedit) {
						   echo '<span class="instronly">';
						   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
						   echo "<a href=\"deleteinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
						   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
						   echo '</span>';
					   }
					   echo "</div>";
				   } else {
					   if ($viewall) { 
						  echo '<span class="instrdates">';
						   echo "<br/>$show ";
						   echo '</span>';
					   }
					   if ($canedit) {
						   echo '<span class="instronly">';
						   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
						   echo "<a href=\"deleteinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
						   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
						   echo '</span>';
					   } 
					   
				   }
				   echo filter("<div class=itemsum>{$line['text']}\n");
				   $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='$typeid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)>0) {
					   echo "<ul>";
					   $filenames = array();
					   $filedescr = array();
					   while ($row = mysql_fetch_row($result)) {
						   $filenames[$row[0]] = $row[2];
						   $filedescr[$row[0]] = $row[1];
					   }
					   foreach (explode(',',$line['fileorder']) as $fid) {
						   echo "<li><a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
					   }
					  
					   echo "</ul>";
				   }
				   echo "</div>";
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = "Hidden";
				   } else {
					   $show = "Showing $startdate until $enddate";
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if ($line['title']!='##hidden##') {
					   if ($graphicalicons) {
						   echo "<img class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['inline']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: #ccc;\">!</div>";
					   }
					   echo "<div class=title><i> <b>{$line['title']}</b> </i><br/>";
				   } else {
					   echo "<div class=title>";
				   }
				   echo '<span class="instrdates">';
				   echo "<i>$show</i> ";
				   echo '</span>';
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"deleteinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['text']}\n");
				   $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='$typeid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)>0) {
					   echo "<ul>";
					   $filenames = array();
					   $filedescr = array();
					   while ($row = mysql_fetch_row($result)) {
						   $filenames[$row[0]] = $row[2];
						   $filedescr[$row[0]] = $row[1];
					   }
					   foreach (explode(',',$line['fileorder']) as $fid) {
						   echo "<li><a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
					   }
					  
					   echo "</ul>";
				   }
				   echo "</div>";
				   enditem($canedit); //echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="LinkedText") {
			   $typeid = $line['typeid'];
			   $query = "SELECT title,summary,text,startdate,enddate,avail,target FROM imas_linkedtext WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			  
			   if (strpos($line['summary'],'<p>')!==0) {
				   $line['summary'] = '<p>'.$line['summary'].'</p>';
			   }
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   if ($line['target']==1) {
				   $target = 'target="_blank"';
			   } else {
				   $target = '';
			   }
			   if ((substr($line['text'],0,4)=="http") && (strpos(trim($line['text'])," ")===false)) { //is a web link
				   $alink = trim($line['text']);
				   $icon = 'web';
			   } else if (substr(strip_tags($line['text']),0,5)=="file:") {
				   $filename = substr(strip_tags($line['text']),5);
				   $alink = $imasroot . "/course/files/".$filename;
				   $ext = substr($filename,strrpos($filename,'.')+1);
				   switch($ext) {
				   	  case 'xls': $icon = 'xls'; break;
					  case 'pdf': $icon = 'pdf'; break;
					  case 'html': $icon = 'html'; break;
					  case 'ppt': $icon = 'ppt'; break;
					  case 'zip': $icon = 'zip'; break;
					  case 'png':
					  case 'gif':
					  case 'jpg':
					  case 'bmp': $icon = 'image'; break;
					  case 'mp3':
					  case 'wav':
					  case 'wma': $icon = 'sound'; break;
					  case 'swf':
					  case 'avi':
					  case 'mpg': $icon = 'video'; break;
					  case 'nb': $icon = 'mathnb'; break;
					  case 'mws':
					  case 'mw': $icon = 'maple'; break;
					  default : $icon = 'doc'; break;
				   }
				   if (!isset($itemicons[$icon])) {
				   	   $icon = 'doc';
				   }
						   	   
			   } else {
				   if ($ispublic) { 
					   $alink = "showlinkedtextpublic.php?cid=$cid&id=$typeid";
				   } else {
					   $alink = "showlinkedtext.php?cid=$cid&id=$typeid";
				   }
				   $icon = 'html';
			   }
			   
			   
			   if ($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
				   if ($line['avail']==2) {
					   $show = "Showing Always ";
					   $color = '#0f0';
				   } else {
					   $show = "Showing until: $enddate ";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&4)==0) {
					   if ($graphicalicons) {
						  echo "<img class=\"floatleft\" src=\"$imasroot/img/{$itemicons[$icon]}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: $color;\">!</div>";
					   }
				   }
				   echo "<div class=title>";
				   echo "<b><a href=\"$alink\" $target>{$line['title']}</a></b>\n";
				   if ($viewall) { 
					   echo '<span class="instrdates">';
					   echo "<br/>$show ";
					   echo '</span>';
				   }
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"deletelinkedtext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = "Hidden";
				   } else {
					   $show = "Showing $startdate until $enddate";
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				  if ($graphicalicons) {
					  echo "<img class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons[$icon]}\" />";
				  } else {
					   echo "<div class=icon style=\"background-color: #ccc;\">!</div>";
				   }
				   echo "<div class=title>";
				   echo "<i> <b><a href=\"$alink\" $target>{$line['title']}</a></b> </i>";
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show</i> ";
				   echo '</span>';
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"deletelinkedtext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); // echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="Forum") {
			   if ($ispublic) { continue;}
			   $typeid = $line['typeid'];
			   $query = "SELECT id,name,description,startdate,enddate,grpaid,avail,postby,replyby FROM imas_forums WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			   $dofilter = false;
			   if ($line['grpaid']>0) {
				if (!$viewall) {
					$query = "SELECT agroupid FROM imas_assessment_sessions WHERE assessmentid='{$line['grpaid']}' AND userid='$userid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					if (mysql_num_rows($result)>0) {
						$agroupid = mysql_result($result,0,0);
					} else {
						$agroupid=0;
					}
					$dofilter = true;
				} 
				if ($dofilter) {
					$query = "SELECT userid FROM imas_assessment_sessions WHERE agroupid='$agroupid' AND assessmentid='{$line['grpaid']}'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					$limids = array();
					while ($row = mysql_fetch_row($result)) {
						$limids[] = $row[0];
					}
					$query = "SELECT userid FROM imas_teachers WHERE courseid='$cid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$limids[] = $row[0];
					}
					$query = "SELECT userid FROM imas_tutors WHERE courseid='$cid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$limids[] = $row[0];
					}
					$limids = "'".implode("','",$limids)."'";
				}   
			   }
			   
			   $query = "SELECT COUNT( DISTINCT threadid )FROM imas_forum_posts WHERE forumid='$typeid'";
			   if ($dofilter) {
				   $query .= " AND userid IN ($limids)";
			   }
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $numthread = mysql_result($result,0,0);
			   $query = "SELECT imas_forum_views.lastview,MAX(imas_forum_posts.postdate) FROM imas_forum_views ";
			   $query .= "LEFT JOIN imas_forum_posts ON imas_forum_views.threadid=imas_forum_posts.threadid AND imas_forum_views.userid='$userid' ";
			   $query .= "WHERE imas_forum_posts.forumid='$typeid' ";
			   if ($dofilter) {
				   $query .= " AND imas_forum_posts.userid IN ($limids) ";
			   }
			   $query .= "GROUP BY imas_forum_posts.threadid";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $hasnewitems = false;
			   if (mysql_num_rows($result)<$numthread) {
				   $hasnewitems = true;
			   } else {
				   while ($row = mysql_fetch_row($result)) {
					   if ($row[0]<$row[1]) {
						   $hasnewitems = true;
						   break;
					   }
				   }
			   }
			  
			  
			   if (strpos($line['description'],'<p>')!==0) {
				   $line['description'] = '<p>'.$line['description'].'</p>';
			   }
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   if ($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
				   if ($line['avail']==2) {
					   $show = "Showing Always ";
					   $color = '#0f0';
				   } else {
					   $show = "Showing until: $enddate ";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   $duedates = "";
				   if ($line['postby']>$now && $line['postby']!=2000000000) {
					   $duedates .= "New Threads due ". formatdate($line['postby']) . ". ";
				   }
				   if ($line['replyby']>$now && $line['replyby']!=2000000000) {
					   $duedates .= "Replies due ". formatdate($line['replyby']) . ". ";
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&8)==0) {
					   if ($graphicalicons) {
						   echo "<img class=\"floatleft\" src=\"$imasroot/img/{$itemicons['forum']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: $color;\">F</div>";
					   }
				   }
				   echo "<div class=title> ";
				   echo "<b><a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b>\n";
				   if ($hasnewitems) {
					   echo " <a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}&page=-1\" style=\"color:red\">New Posts</a>";
				   }
				   if ($viewall) { 
					   echo '<span class="instrdates">';
					   echo "<br/>$show ";
					   echo '</span>';
				   }
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"deleteforum.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					   echo '</span>';
				   }
				   if ($duedates!='') {echo "<br/>$duedates";}
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = "Hidden";
				   } else {
					   $show = "Showing $startdate until $enddate";
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if ($graphicalicons) {
					   echo "<img class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['forum']}\" />";
				   } else {
					   echo "<div class=icon style=\"background-color: #ccc;\">F</div>";
				   }   
				   echo "<div class=title><i> <b><a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b></i> ";
				   
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show </i>";
				   echo '</span>';
				   if ($hasnewitems) {
					   echo " <span style=\"color:red\">New Posts</span>";
				   }
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"deleteforum.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="Wiki") {
		   	   if ($ispublic) { continue;}
			   $typeid = $line['typeid'];
			   $query = "SELECT id,name,description,startdate,enddate,editbydate,avail,settings FROM imas_wikis WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			   if (strpos($line['description'],'<p>')!==0) {
				   $line['description'] = '<p>'.$line['description'].'</p>';
			   }
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   if ($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
				   if ($line['avail']==2) {
					   $show = "Showing Always ";
					   $color = '#0f0';
				   } else {
					   $show = "Showing until: $enddate ";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   $duedates = "";
				   if ($line['editbydate']>$now && $line['editbydate']!=2000000000) {
					   $duedates .= "Edits due by ". formatdate($line['editbydate']) . ". ";
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&8)==0) {
					   if ($graphicalicons) {
						   echo "<img class=\"floatleft\" src=\"$imasroot/img/{$itemicons['wiki']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: $color;\">W</div>";
					   }
				   }
				   echo "<div class=title> ";
				   echo "<b><a href=\"../wikis/viewwiki.php?cid=$cid&id={$line['id']}\">{$line['name']}</a></b>\n";
				   if ($viewall) { 
					   echo '<span class="instrdates">';
					   echo "<br/>$show ";
					   echo '</span>';
				   }
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addwiki.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"deletewiki.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					   echo '</span>';
				   }
				   if ($duedates!='') {echo "<br/>$duedates";}
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = "Hidden";
				   } else {
					   $show = "Showing $startdate until $enddate";
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if ($graphicalicons) {
					   echo "<img class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['wiki']}\" />";
				   } else {
					   echo "<div class=icon style=\"background-color: #ccc;\">W</div>";
				   }   
				   echo "<div class=title><i> <b><a href=\"../wikis/viewwiki.php?cid=$cid&id={$line['id']}\">{$line['name']}</a></b></i> ";
				   
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show </i>";
				   echo '</span>';
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addwiki.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"deletewiki.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   }
		   }
	   }
	   if (count($items)>0) {
		   if ($canedit) {echo generateadditem($parent,'b');}
	   }
   }
   
   function generateadditem($blk,$tb) {
   	global $cid, $CFG;
   	if (isset($CFG['CPS']['additemtype']) && $CFG['CPS']['additemtype'][0]=='links') {
   		if ($tb=='BB' || $tb=='LB') {$tb = 'b';}
   		if ($tb=='t' && $blk=='0') {
   			$html = '<div id="topadditem" class="additembox"><span><b>Add:</b> ';
   		} else {
   			$html = '<div class="additembox"><span><b>Add:</b> ';
   		}
		$html .= "<a href=\"addassessment.php?block=$blk&tb=$tb&cid=$cid\">Assessment</a> | ";
		$html .= "<a href=\"addinlinetext.php?block=$blk&tb=$tb&cid=$cid\">Text</a> | ";
		$html .= "<a href=\"addlinkedtext.php?block=$blk&tb=$tb&cid=$cid\">Link</a> | ";
		$html .= "<a href=\"addforum.php?block=$blk&tb=$tb&cid=$cid\">Forum</a> | ";
		$html .= "<a href=\"addblock.php?block=$blk&tb=$tb&cid=$cid\">Block</a> | ";
		$html .= "<a href=\"addcalendar.php?block=$blk&tb=$tb&cid=$cid\">Calendar</a>";
		$html .= '</span>';
		$html .= '</div>';
   		
   	} else {
   		$html = "<select name=addtype id=\"addtype$blk-$tb\" onchange=\"additem('$blk','$tb')\" ";
		if ($tb=='t') {
			$html .= 'style="margin-bottom:5px;"';
		}
		$html .= ">\n";
		$html .= "<option value=\"\">Add An Item...</option>\n";
		$html .= "<option value=\"assessment\">Add Assessment</option>\n";
		$html .= "<option value=\"inlinetext\">Add Inline Text</option>\n";
		$html .= "<option value=\"linkedtext\">Add Linked Text</option>\n";
		$html .= "<option value=\"forum\">Add Forum</option>\n";
		$html .= "<option value=\"block\">Add Block</option>\n";
		$html .= "<option value=\"calendar\">Add Calendar</option>\n";
		$html .= "</select><BR>\n";
   		
   	}
	return $html;
   }
   
   function generatemoveselect($num,$count,$blk,$blocklist) {
	$num = $num+1;  //adjust indexing
	$html = "<select class=\"mvsel\" id=\"$blk-$num\" onchange=\"moveitem($num,'$blk')\">\n";
	for ($i = 1; $i <= $count; $i++) {
		$html .= "<option value=\"$i\" ";
		if ($i==$num) { $html .= "SELECTED";}
		$html .= ">$i</option>\n";
	}
	for ($i=0; $i<count($blocklist); $i++) {
		if ($num!=$blocklist[$i]) {
			$html .= "<option value=\"B-{$blocklist[$i]}\">Into {$blocklist[$i]}</option>\n";
		}
	}
	if ($blk!='0') {
		$html .= '<option value="O-' . $blk . '">Out of Block</option>';
	}
	$html .= "</select>\n";
	return $html;
   }
   
   function makecolor($etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   //$now = time();
	   if ($etime<$now) {
		   $color = "#ccc";
	   } else if ($etime-$now < 605800) {  //due within a week
		   $color = "#f".dechex(floor(16*($etime-$now)/605801))."0";
	   } else if ($etime-$now < 1211600) { //due within two weeks
		   $color = "#". dechex(floor(16*(1-($etime-$now-605800)/605801))) . "f0";
	   } else {
		   $color = "#0f0";
	   }
	   return $color;
   }
   function makecolor2($stime,$etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   if ($etime==2000000000 && $now >= $stime) {
		   return '#0f0';
	   } else if ($stime==0) {
		   return makecolor($etime,$now);
	   }
	   if ($etime==$stime) {
		   return '#ccc';
	   }
	   $r = ($etime-$now)/($etime-$stime);  //0 = etime, 1=stime; 0:#f00, 1:#0f0, .5:#ff0
	   if ($etime<$now || $stime>$now) {
		   $color = '#ccc';
	   } else if ($r<.5) {
		   $color = '#f'.dechex(floor(32*$r)).'0';
	   } else if ($r<1) {
		   $color = '#'.dechex(floor(32*(1-$r))).'f0';
	   } else {
		   $color = '#0f0';
	   }
	    return $color;
   }
   
   function formatdate($date) {
	return tzdate("D n/j/y, g:i a",$date);   
	//return tzdate("M j, Y, g:i a",$date);   
   }
   
   function upsendexceptions(&$items) {
	   global $exceptions;
	   $minsdate = 9999999999;
	   $maxedate = 0;
	   foreach ($items as $k=>$item) {
		   if (is_array($item)) {
			  $hasexc = upsendexceptions($items[$k]['items']);
			  if ($hasexc!=FALSE) {
				  if ($hasexc[0]<$items[$k]['startdate']) {
					  $items[$k]['startdate'] = $hasexc[0];
				  }
				  if ($hasexc[1]>$items[$k]['enddate']) {
					  $items[$k]['enddate'] = $hasexc[1];
				  }
				//return ($hasexc);
				if ($hasexc[0]<$minsdate) { $minsdate = $hasexc[0];}
				if ($hasexc[1]>$maxedate) { $maxedate = $hasexc[1];}
			  }
		   } else {
			   if (isset($exceptions[$item])) {
				  // return ($exceptions[$item]);
				   if ($exceptions[$item][0]<$minsdate) { $minsdate = $exceptions[$item][0];}
				   if ($exceptions[$item][1]>$maxedate) { $maxedate = $exceptions[$item][1];}
			   }
		   }
	   }
	   if ($minsdate<9999999999 || $maxedate>0) {
		   return (array($minsdate,$maxedate));
	   } else {
		   return false;
	   }
   }
   function getpts($scs) {
	$tot = 0;
  	foreach(explode(',',$scs) as $sc) {
		if (strpos($sc,'~')===false) {
			if ($sc>0) { 
				$tot += $sc;
			} 
		} else {
			$sc = explode('~',$sc);
			foreach ($sc as $s) {
				if ($s>0) { 
					$tot+=$s;
				}
			}
		}
	}
	return $tot;
   }
   
   //instructor-only tree-based quick view of full course
   function quickview($items,$parent,$showdates=false,$showlinks=true) { 
	   global $teacherid,$cid,$imasroot,$userid,$openblocks,$firstload,$sessiondata,$previewshift,$hideicons,$exceptions,$latepasses;
	   if (!is_array($openblocks)) {$openblocks = array();}
	   $itemtypes = array();  $iteminfo = array();
	   $query = "SELECT id,itemtype,typeid FROM imas_items WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $itemtypes[$row[0]] = array($row[1],$row[2]);
	   }
	   $query = "SELECT id,name,startdate,enddate,reviewdate,avail FROM imas_assessments WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['Assessment'][$id] = $row;
	   }
	   $query = "SELECT id,title,text,startdate,enddate,avail FROM imas_inlinetext WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['InlineText'][$id] = $row;
	   }
	   $query = "SELECT id,title,startdate,enddate,avail FROM imas_linkedtext WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['LinkedText'][$id] = $row;
	   }
	   $query = "SELECT id,name,startdate,enddate,avail FROM imas_forums WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['Forum'][$id] = $row;
	   }
	   $now = time() + $previewshift;
	   for ($i=0;$i<count($items); $i++) {
		   if (is_array($items[$i])) { //is a block
			$items[$i]['name'] = stripslashes($items[$i]['name']);
			
			if ($items[$i]['startdate']==0) {
				$startdate = "Always";
			} else {
				$startdate = formatdate($items[$i]['startdate']);
			}
			if ($items[$i]['enddate']==2000000000) {
				$enddate = "Always";
			} else {
				$enddate = formatdate($items[$i]['enddate']);
			}
			$bnum = $i+1;
			if (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O') {
				$availbeh = "Expanded";
			} else if ($items[$i]['SH'][1]=='F') {
				$availbeh = "as Folder";
			} else {
				$availbeh = "Collapsed";
			}
			if ($items[$i]['avail']==2) {
				$show = "Showing $availbeh Always";
			} else if ($items[$i]['avail']==0) {
				$show = "Hidden";
			} else {
				$show = "Showing $availbeh $startdate until $enddate";
			}
			if ($items[$i]['avail']==2) {
				$color = '#0f0';
			} else if ($items[$i]['avail']==0) {
				$color = '#ccc';
			} else {
				$color = makecolor2($items[$i]['startdate'],$items[$i]['enddate'],$now);
			}
			if (in_array($items[$i]['id'],$openblocks)) { $isopen=true;} else {$isopen=false;}
			if ($isopen) {
				$liclass = 'blockli';
				$qviewstyle = '';
			} else {
				$liclass = 'blockli nCollapse';
				$qviewstyle = 'style="display:none;"';
			}
			echo '<li class="'.$liclass.'" id="'."$parent-$bnum".'" obn="'.$items[$i]['id'].'"><span class=icon style="background-color:'.$color.'">B</span>';
			if ($items[$i]['avail']==2 || ($items[$i]['avail']==1 && $items[$i]['startdate']<$now && $items[$i]['enddate']>$now)) {
				echo '<b><span id="B'.$parent.'-'.$bnum.'" onclick="editinplace(this)">'.$items[$i]['name']. "</span></b>";
				//echo '<b>'.$items[$i]['name'].'</b>';
			} else {
				echo '<i><b><span id="B'.$parent.'-'.$bnum.'" onclick="editinplace(this)">'.$items[$i]['name']. "</span></b></i>";
				//echo '<i><b>'.$items[$i]['name'].'</b></i>';
			}
			if ($showdates) {
				echo " $show";
			}
			if ($showlinks) {
				echo '<span class="links">';
				echo " <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\">Modify</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\">Delete</a>";
				echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\">Copy</a>";
				echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\">NewFlag</a>";
				echo '</span>';
			}
			if (count($items[$i]['items'])>0) {
				echo '<ul class=qview '.$qviewstyle.'>';
				quickview($items[$i]['items'],$parent.'-'.$bnum,$showdats,$showlinks);
				echo '</ul>';
			}
			echo '</li>';
		   } else if ($itemtypes[$items[$i]][0] == 'Calendar') {
			echo '<li id="'.$items[$i].'"><span class=icon style="background-color:#0f0;">C</span>Calendar</li>';
			   
	   	   } else if ($itemtypes[$items[$i]][0] == 'Assessment') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['startdate'],$line['enddate'],$line['reviewdate'],$line['avail']) = $iteminfo['Assessment'][$typeid];
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['reviewdate']==2000000000) {
				   $reviewdate = "Always";
			   } else {
				   $reviewdate = formatdate($line['reviewdate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
			   } else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
			   }
			echo '<li id="'.$items[$i].'"><span class=icon style="background-color:'.$color.'">?</span>';
			   if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   $show = "Available until $enddate";
				   echo '<b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				   //echo '<b>'.$line['name'].'</b> ';
			   } else if ($line['avail']==1 && $line['startdate']<$now && $line['reviewdate']>$now) {
				   $show = "Review until $reviewdate";
				   //echo '<b>'.$line['name'].'</b> ';
				   echo '<b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
			   } else {
				   $show = "Available $startdate to $enddate";
				   if ($line['reviewdate']>0 && $line['enddate']!=2000000000) {
					   $show .= ", review until $reviewdate";
				   }
				   //echo '<i><b>'.$line['name'].'</b></i> ';
				   echo '<i><b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
			   }
			   if ($showdates) {
				   echo $show;
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				    echo " <a href=\"addquestions.php?aid=$typeid&cid=$cid\">Questions</a> | <a href=\"addassessment.php?id=$typeid&cid=$cid\">Settings</a> | \n";
				   echo "<a href=\"deleteassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
				   echo " | <a href=\"gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">Grades</a>";
				   echo '</span>';
			   }
			   echo "</li>";
			  
		   } else if ($itemtypes[$items[$i]][0] == 'InlineText') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['text'],$line['startdate'],$line['enddate'],$line['avail']) = $iteminfo['InlineText'][$typeid];
			   if ($line['name'] == '##hidden##') {
				   $line['name'] = strip_tags($line['text']);
			   }
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
				} else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
				}
			   echo '<li id="'.$items[$i].'"><span class=icon style="background-color:'.$color.'">!</span>';
			   if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   echo '<b><span id="I'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				  // echo '<b>'.$line['name']. "</b>";
				   if ($showdates) {
					   echo " showing until $enddate";
				   }
			   } else {
				   //echo '<i><b>'.$line['name']. "</b></i>";
				   echo '<i><b><span id="I'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
				   if ($showdates) {
					   echo " showing $startdate until $enddate";
				   }
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				   echo " <a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
				  echo "<a href=\"deleteinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				  echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
				  echo '</span>';
			   }
			   echo '</li>';
		   } else if ($itemtypes[$items[$i]][0] == 'LinkedText') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['startdate'],$line['enddate'],$line['avail']) = $iteminfo['LinkedText'][$typeid];
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
				} else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
				}
			   echo '<li id="'.$items[$i].'"><span class=icon style="background-color:'.$color.'">!</span>';
			   if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   //echo '<b>'.$line['name']. "</b>";
				   echo '<b><span id="L'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				   if ($showdates) {
					   echo " showing until $enddate";
				   }
			   } else {
				   //echo '<i><b>'.$line['name']. "</b></i>";
				   echo '<i><b><span id="L'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
				   if ($showdates) {
					   echo " showing $startdate until $enddate";
				   }
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				   echo " <a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
				  echo "<a href=\"deletelinkedtext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				  echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
				  echo '</span>';
			   }
			   echo '</li>';
		   } else if ($itemtypes[$items[$i]][0] == 'Forum') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['startdate'],$line['enddate'],$line['avail']) = $iteminfo['Forum'][$typeid];
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
				} else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
				}
			   echo '<li id="'.$items[$i].'"><span class=icon style="background-color:'.$color.'">F</span>';
			  if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   //echo '<b>'.$line['name']. "</b>";
				   echo '<b><span id="F'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				   if ($showdates) {
					   echo " showing until $enddate";
				   }
			   } else {
				   echo '<i><b><span id="F'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
				   //echo '<i><b>'.$line['name']. "</b></i>";
				   if ($showdates) {
					   echo " showing $startdate until $enddate";
				   }
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				   echo " <a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
				  echo "<a href=\"deleteforum.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				  echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">Copy</a>";
				  echo '</span>';
			   }
			   echo '</li>';
		   }
	   
	   }
  }
   
?>
