<?php  
//change counter; increase by 1 each time a change is made
$latest = 29;

if (!empty($dbsetup)) {  //initial setup - just write upgradecounter.txt
	$handle = fopen("upgradecounter.txt",'w');
	fwrite($handle,$latest);
	fclose($handle);	
} else { //doing upgrade
	require("validate.php");
	if ($myrights<100) {
		echo "No rights, aborting";
		exit;
	}
	
	$handle = @fopen("upgradecounter.txt",'r');
	if ($handle===false) {
		$last = 0;
	} else {
		$last = intval(trim(fgets($handle)));
		fclose($handle);
	}
	
	if ($last==$latest) {
		echo "No changes to make.";
	} else {
		if ($last < 1) {
			$query = "ALTER TABLE `imas_forums` CHANGE `settings` `settings` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "ALTER TABLE `imas_forums` ADD `sortby` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());		
		}
		if ($last < 2) {
			 $query = " ALTER TABLE `imas_gbcats` CHANGE `chop` `chop` DECIMAL( 3, 2 ) UNSIGNED NOT NULL DEFAULT '1'"; 
			 mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 3) {
			$sql = 'CREATE TABLE `imas_forum_threads` (`id` INT(10) UNSIGNED NOT NULL, `forumid` INT(10) UNSIGNED NOT NULL, ';
			$sql .= '`lastposttime` INT(10) UNSIGNED NOT NULL, `lastpostuser` INT(10) UNSIGNED NOT NULL, `views` INT(10) UNSIGNED NOT NULL, ';
			$sql .= 'PRIMARY KEY (`id`), INDEX (`forumid`), INDEX(`lastposttime`))  COMMENT = \'Forum threads\'';	
			mysql_query($sql) or die("Query failed : " . mysql_error());
			
			$query = "INSERT INTO imas_forum_threads (id,forumid,lastpostuser,lastposttime) SELECT threadid,forumid,userid,max(postdate) FROM imas_forum_posts GROUP BY threadid";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "UPDATE imas_forum_threads ift, imas_forum_posts ifp SET ift.views=ifp.views WHERE ift.id=ifp.threadid AND ifp.parent=0";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "ALTER TABLE `imas_exceptions` ADD `islatepass` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 4) {
			$query = "ALTER TABLE `imas_assessments` ADD `endmsg` TEXT NOT NULL ;";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 5) {
			$query = "ALTER TABLE `imas_gbcats` ADD `hidden` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "UPDATE imas_gbscheme SET defaultcat=CONCAT(defaultcat,',0');";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "ALTER TABLE `imas_gbscheme` CHANGE `defaultcat` `defaultcat` VARCHAR( 254 ) NOT NULL DEFAULT '0,0,1,0,-1,0'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 6) {
			//add imas_tutors table
			$query = 'CREATE TABLE `imas_tutors` (`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `userid` INT(10) UNSIGNED NOT NULL, `courseid` INT(10) UNSIGNED NOT NULL, `section` VARCHAR(40) NOT NULL, INDEX (`userid`, `courseid`)) COMMENT = \'course tutors\'';
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = 'ALTER TABLE `imas_students` CHANGE `section` `section` VARCHAR( 40 ) NULL DEFAULT NULL';
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 7) {
			//for existing diag, put level2 selector as section
			$query = "SELECT imas_students.id,imas_users.email FROM imas_students JOIN imas_users ON imas_users.id=imas_students.userid AND imas_users.SID LIKE '%~%~%'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$epts = explode('@',$row[1]);
				$query = "UPDATE imas_students SET section='{$epts[1]}' WHERE id='{$row[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		if ($last < 8) {
			//move existing tutors to new system
			$query = "SELECT u.id,t.id,t.courseid FROM imas_users as u JOIN imas_teachers as t ON u.id=t.userid AND u.rights=15";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$lastuser = -1;
			while ($row = mysql_fetch_row($result)) {
				if ($row[0]!=$lastuser) {
					$query = "UPDATE imas_users SET rights=10 WHERE id='{$row[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$lastuser = $row[0];
				}
				$query = "DELETE FROM imas_teachers WHERE id='{$row[1]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "INSERT INTO imas_tutors (userid,courseid,section) VALUES ('{$row[0]}','{$row[2]}','')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		if ($last < 9) {
			//if postback
			if (isset($_POST['diag'])) {
				foreach ($_POST['diag'] as $did=>$uid) {
					$query = "UPDATE imas_diags SET ownerid='$uid' WHERE id='$did'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else {
				//change diag owner to userid from groupid
				$ambig = false;
				$out = '';
				$query = "SELECT id,ownerid,name FROM imas_diags";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$owners = array();
					$dnames = array();
					while ($row = mysql_fetch_row($result)) {
						$owners[$row[1]][] = $row[0];
						$dnames[$row[0]] = $row[2];
					}
					$ow = array_keys($owners);
					$users = array();
					foreach ($ow as $ogrp) {
						$query = "SELECT id,LastName,FirstName FROM imas_users WHERE groupid='$ogrp' AND rights>59 ORDER BY id";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						if (mysql_num_rows($result)==0) {
							echo "Orphaned Diags: ".implode(',',$owners[$ogrp]).'<br/>';
						} else if (mysql_num_rows($result)==1) {
							$uid = mysql_result($result,0,0);
							$query = "UPDATE imas_diags SET ownerid=$uid WHERE id IN (".implode(',',$owners[$ogrp]).")";
							mysql_query($query) or die("Query failed : " . mysql_error());
						} else {
							$ops = '';
							while ($row = mysql_fetch_row($result)) {
								$ops .= "<option value=\"{$row[0]}\">{$row[1]}, {$row[2]}</option>";
							}
							foreach ($owners[$ogrp] as $did) {
								$out .= "Diag <b>".$dnames[$did]."</b> Owner: <select name=\"diag[$did]\">";
								$out .= $ops;
								$out .= "</select><br/>";
							}
							$ambig = true;
						}
					}
					if ($ambig) {
						echo "<form method=\"post\" action=\"upgrade.php\">";
						echo "<p>Converting diagnostic ownership to userid.  Ambiguous situations exist.  Please select the ";
						echo "appropriate owner for each diagnostic</p><p>";
						echo $out;
						echo '</p><input type="submit" value="Submit"/></form>';
						//exit - will continue on postback
						//update counter to 8, so will continue at 9 on submit, but won't redo earlier ones
						$handle = fopen("upgradecounter.txt",'w');
						fwrite($handle,8);
						fclose($handle);
						exit;
					}
				}
			}
		}
		if ($last < 10) {
			$query = "ALTER TABLE `imas_gbitems` ADD `tutoredit` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 11) {
			$query = "ALTER TABLE `imas_assessments` ADD `tutoredit` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "ALTER TABLE `imas_students` ADD `locked` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 12) {
			$query = "ALTER TABLE `imas_diags` ADD `reentrytime` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 13) {
			$query = "CREATE TABLE `imas_diag_onetime` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`diag` INT( 10 ) UNSIGNED NOT NULL ,
				`time` INT( 10 ) UNSIGNED NOT NULL ,
				`code` VARCHAR( 9 ) NOT NULL ,
				INDEX (`diag`), INDEX(`time`), INDEX(`code`)
				) TYPE = innodb;";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 14) {
			$query = "ALTER TABLE `imas_forums` ADD `cntingb` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 15) {
			$query = "ALTER TABLE `imas_linkedtext` ADD `target` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 16) {
			 $query = "ALTER TABLE `imas_forum_posts` CHANGE `points` `points` DECIMAL( 5, 1 ) UNSIGNED NULL DEFAULT NULL"; 
			 $res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 echo "<p>SimpleLTI has been deprecated and replaced with BasicLTI.  If you have enablesimplelti in your config.php, change it to enablebasiclti.  ";
			 echo "If you do not have either currently in your config.php and want to allow imathas to act as a BasicLTI producer, add \$enablebasiclti = true to config.php</p>";
		}
		if ($last < 17) {
			 $query = "ALTER TABLE `imas_assessments` ADD `eqnhelper` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 18) {
			 $query = "ALTER TABLE `imas_courses` ADD `newflag` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			$res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 19) {
			 $query = "ALTER TABLE `imas_students` ADD `timelimitmult` DECIMAL(3,2) UNSIGNED NOT NULL DEFAULT '1.0';"; 
			 $res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 20) {
			 $query = "ALTER TABLE `imas_assessments` ADD `caltag` CHAR( 2 ) NOT NULL DEFAULT '?R';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 21) {
			 $query = "ALTER TABLE `imas_courses` ADD `showlatepass` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 22) {
			 $query = "ALTER TABLE `imas_assessments` ADD `showtips` TINYINT( 1 ) NOT NULL DEFAULT '1';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 23) {
			$query = 'CREATE TABLE `imas_stugroupset` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `courseid` INT(10) UNSIGNED NOT NULL, '
				. ' `name` VARCHAR(254) NOT NULL, '
				. ' INDEX (`courseid`)'
				. ' )'
				. ' TYPE = innodb'
				. ' COMMENT = \'Student Group Sets\';';
			$res = mysql_query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			echo '<p>imas_stugroupset created<br/>';
			
			$query = 'CREATE TABLE `imas_stugroups` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `groupsetid` INT(10) UNSIGNED NOT NULL, '
				. ' `name` VARCHAR(254) NOT NULL, '
				. ' INDEX (`groupsetid`)'
				. ' )'
				. ' TYPE = innodb'
				. ' COMMENT = \'Student Groups\';';
			$res = mysql_query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			echo 'imas_stugroups created<br/>';
			
			$query = 'CREATE TABLE `imas_stugroupmembers` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `stugroupid` INT(10) UNSIGNED NOT NULL, '
				. ' `userid` INT(10) UNSIGNED NOT NULL, '
				. ' INDEX (`stugroupid`), INDEX (`userid`)'
				. ' )'
				. ' TYPE = innodb'
				. ' COMMENT = \'Student Group Members\';';
			$res = mysql_query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			echo 'imas_stugroupmembers created<br/></p>';
			echo '<p>It is now possible to specify a default course theme by setting $defaultcoursetheme = "theme.css"; in config.php</p>';
			
		}
		if ($last < 24) {
			 $query = "ALTER TABLE `imas_assessments` ADD `groupsetid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
	
			 $query = "ALTER TABLE `imas_forums` ADD `groupsetid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 /*later (once groups are done)
			 $query = "ALTER TABLE `imas_forums` DROP COLUMN `grpaid`"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 */
			 $query = "ALTER TABLE `imas_forum_threads` ADD `stugroupid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 
			 $query = "ALTER TABLE `imas_forum_threads` ADD INDEX(`stugroupid`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			
		}
		if ($last < 25) {
			 $query = "ALTER TABLE `imas_libraries` ADD `sortorder` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 26) {
			 $query = "ALTER TABLE `imas_users` ADD `homelayout` VARCHAR(32) NOT NULL DEFAULT '|0,1,2||0,1'"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 27) {
			$query = "ALTER TABLE `imas_diag_onetime` ADD `goodfor` INT(10) NOT NULL DEFAULT '0'"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 28) {
			$sql = 'CREATE TABLE `imas_wikis` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `name` VARCHAR(50) NOT NULL, '
				. ' `description` TEXT NOT NULL, '
				. ' `courseid` INT(10) UNSIGNED NOT NULL, '
				. ' `startdate` INT(10) UNSIGNED NOT NULL, '
				. ' `editbydate` INT(10) UNSIGNED NOT NULL, '
				. ' `enddate` INT(10) UNSIGNED NOT NULL, '
				. ' `settings` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
				. ' `groupsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
				. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\','
				. ' INDEX (`courseid`), '
				. ' INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`), INDEX(`editbydate`) '
				. ' )'
				. ' TYPE = innodb'
				. ' COMMENT = \'Wikis\';';
			 $res = mysql_query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".mysql_error()."</p>";
			 }
			
			$sql = 'CREATE TABLE `imas_wiki_revisions` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `wikiid` INT(10) UNSIGNED NOT NULL, '
				. ' `stugroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
				. ' `userid` INT(10) UNSIGNED NOT NULL, '
				. ' `time` INT(10) UNSIGNED NOT NULL,'
				. ' `revision` TEXT NOT NULL, '
				. ' INDEX (`wikiid`), INDEX(`stugroupid`), INDEX(`time`) '
				. ' )'
				. ' TYPE = innodb'
				. ' COMMENT = \'Wiki revisions\';';
			 $res = mysql_query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".mysql_error()."</p>";
			 }
			
			$sql = 'CREATE TABLE `imas_wiki_views` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `userid` INT(10) UNSIGNED NOT NULL, '
				. ' `wikiid` INT(10) UNSIGNED NOT NULL, '
				. ' `lastview` INT(10) UNSIGNED NOT NULL,'
				 . ' INDEX (`userid`), INDEX(`wikiid`)'
				. ' )'
				. ' TYPE = innodb'
				. ' COMMENT = \'Wiki last viewings\';';
			 $res = mysql_query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".mysql_error()."</p>";
			 }
		}
		if ($last<29) {
			//this is a bug fix for a typo in the homelayout default
			$query = 'ALTER TABLE `imas_users` CHANGE `homelayout` `homelayout` VARCHAR( 32 ) NOT NULL DEFAULT \'|0,1,2||0,1\'';
			$res = mysql_query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			$query = 'UPDATE `imas_users` SET homelayout = CONCAT(\'|0,1,2\',SUBSTR(homelayout,7))';
			$res = mysql_query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
		}
		$handle = fopen("upgradecounter.txt",'w');
		if ($handle===false) {
			echo '<p>Error: unable open upgradecounter.txt for writing</p>';
		} else {
			$fwrite = fwrite($handle,$latest);
			if ($fwrite === false) {
				echo '<p>Error: unable to write to upgradecounter.txt</p>';
			}
			fclose($handle);
		}
		echo "Upgrades complete";
	}	
}

?>
