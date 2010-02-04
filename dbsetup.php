<html>
<head>
<link rel="stylesheet" href="imas.css" type="text/css">
</head>
<body>
<?php
if (file_exists("upgradecounter.txt")) {
	echo "It appears the database setup has already been run.  Aborting.  If you need to ";
	echo "rerun the setup, delete upgradecounter.txt";
	echo "</body></html>";
	exit;
}
$dbsetup = true;
include("config.php");
//IMathAS Database Setup
//(c) 2006 David Lippman
if (isset($_POST['dbsetupmethod'])) { //called from install script
	if ($_POST['dbsetupmethod']>0) {
		$docreate = 1;
	} else {
		$docreate = 0;
	}
	echo "<h3>This step will set up the database required for IMathAS</h3>\n";
	echo "<form method=post action=\"dbsetup.php\">\n";
	echo "<input type=\"hidden\" name=\"create\" value=\"$docreate\" />";
	echo '<input type="hidden" name="authuser" value="';
	if ($_POST['dbsetupmethod']==2) {
		echo $_POST['dbauthuser'];
	} 
	echo '" />';
	echo '<input type="hidden" name="authpass" value="';
	if ($_POST['dbsetupmethod']==2) {
		echo $_POST['dbauthpass'];
	} 
	echo '" />';
	echo "<fieldset><legend>Initial IMathAS User Information</legend>\n";
	echo "<span class=form>First Name</span>";
	echo "<span class=formright><input type=type name=firstname value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Last Name</span>";
	echo "<span class=formright><input type=type name=lastname value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Username</span>";
	echo "<span class=formright><input type=type name=username value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Password</span>";
	echo "<span class=formright><input type=type name=password value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Email</span>";
	echo "<span class=formright><input type=type name=email value=\"root@$dbserver\"></span><br class=form>\n";
	echo "</fieldset>\n";
	echo "<div class=submit><input type=submit value=\"Set up database\"></div>\n";
	echo "</form>\n";
	echo "</body></html>\n";
	exit;
} else if (!isset($_POST['authuser'])) {
	echo "<form method=post action=\"dbsetup.php\">\n";
	echo "<h3>This script will set up the database required for IMathAS</h3>\n";
	echo "<p><b>Before submitting this form</b> be sure you have edited the config.php file to match the settings for your server</p>\n";
	echo "<p><fieldset><legend>Database Creation</legend>\n";
	echo "<span class=form>Username of MySQL user authorized to create new database tables</span>";
	echo "<span class=formright><input type=text name=authuser></span><br class=form>\n";
	echo "<span class=form>Password</span>";
	echo "<span class=formright><input type=password name=authpass></span><br class=form>\n";
	echo "<span class=form>Have the IMathAS database and database user been created already? <sup>*</sup></span>";
	echo "<span class=formright><input type=radio name=create value=1 CHECKED>No, create them<br/><input type=radio name=create value=0>Yes, already created</span><br class=form>\n";
	echo "</fieldset><fieldset><legend>Initial IMathAS User Information</legend>\n";
	echo "<span class=form>First Name</span>";
	echo "<span class=formright><input type=type name=firstname value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Last Name</span>";
	echo "<span class=formright><input type=type name=lastname value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Username</span>";
	echo "<span class=formright><input type=type name=username value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Password</span>";
	echo "<span class=formright><input type=type name=password value=\"root\"></span><br class=form>\n";
	echo "<span class=form>Email</span>";
	echo "<span class=formright><input type=type name=email value=\"root@$dbserver\"></span><br class=form>\n";
	echo "</fieldset>\n";
	echo "<div class=submit><input type=submit value=\"Set up database\"></div>\n";
	echo "</form>\n";
	echo "<p><sup>*</sup>On some shared servers, your read/write MySQL user can only create new database users and databases ";
	echo "through the provided web administration tool or control panel.  In this case, you will need to create the database and ";
	echo "database users before running this script.  Be sure to modify the config.php file to reflect your chosen database and ";
	echo "database user name.</p>\n";
	echo "</body></html>\n";
	exit;
}

$authuser = $_POST['authuser'];
$authpass = $_POST['authpass'];
$username = $_POST['username'];
$password = $_POST['password'];
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$email = $_POST['email'];
if ($authuser=='' && $authpass=='') {
	$authuser = $dbusername; 
	$authpass = $dbpassword;
}

$link = mysql_connect($dbserver,$authuser, $authpass) 
  or die("Could not connect : " . mysql_error());

//comment out these three pairs of lines (down to but not including
//mysql_select_db) if you've already created the database and database user
if ($_POST['create']==1) {
	$sql = 'CREATE DATABASE `' . $dbname . '` ;';
	mysql_query($sql) or die("Query failed : $sql " . mysql_error());
	
	$sql = 'GRANT USAGE ON *.* TO \'' . $dbusername . '\'@\''. $dbserver . '\' IDENTIFIED BY \''. $dbpassword . '\' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0;';
	mysql_query($sql) or die("Query failed : $sql " . mysql_error());
	
	$sql = 'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE , ALTER  ON `' . $dbname . '`.* TO \'' . $dbusername . '\'@\'' . $dbserver . '\';';
	mysql_query($sql) or die("Query failed : $sql " . mysql_error());
}
mysql_select_db($dbname) 
  or die("Could not select database");

$sql = 'CREATE TABLE `imas_users` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `SID` VARCHAR(50) NOT NULL, '
        . ' `password` VARCHAR(32) NOT NULL, '
	. ' `rights` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'0\', ' 
        . ' `FirstName` VARCHAR(20) NOT NULL, '
        . ' `LastName` VARCHAR(20) NOT NULL, '
        . ' `email` VARCHAR(100) NOT NULL, '
	. ' `lastaccess` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `groupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `msgnotify` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `qrightsdef` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `deflib` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `usedeflib` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `homelayout` VARCHAR(32)  NOT NULL DEFAULT \'|0,2,3||0,1\','
	. ' `remoteaccess` VARCHAR(10) NOT NULL, '
	. ' INDEX (`lastaccess`), INDEX (`rights`), '
        . ' UNIQUE (`SID`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'User Information\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());

echo 'imas_users created<br/>';

$sql = 'CREATE TABLE `imas_students` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `courseid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `section` VARCHAR(40) NULL, '
	. ' `code` SMALLINT(4) UNSIGNED NULL, '
	. ' `gbcomment` TEXT NOT NULL, '
	. ' `gbinstrcomment` TEXT NOT NULL, '
	. ' `latepass` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `lastaccess` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `locked` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `timelimitmult` DECIMAL(3,2) UNSIGNED NOT NULL DEFAULT \'1.0\', '
        . ' INDEX (`userid`), INDEX (`courseid`), '
	. ' INDEX(`code`), INDEX(`section`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Which courses each student is enrolled in\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_students created<br/>';

$sql = 'CREATE TABLE `imas_teachers` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `courseid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
        . ' INDEX (`userid`), INDEX(`courseid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Which courses each teacher is teaching\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_teachers created<br/>';

$sql = 'CREATE TABLE `imas_tutors` ('
	. '`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. '`userid` INT(10) UNSIGNED NOT NULL, '
	. '`courseid` INT(10) UNSIGNED NOT NULL, '
	. '`section` VARCHAR(40) NOT NULL, '
	. 'INDEX (`userid`), INDEX(`courseid`) '
	. ' ) TYPE = innodb '
	. 'COMMENT = \'course tutors\'';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_tutors created<br/>';

$sql = 'CREATE TABLE `imas_courses` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `ownerid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' `enrollkey` VARCHAR(100) NOT NULL, '
	. ' `itemorder` TEXT NOT NULL, '
	. ' `hideicons` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `allowunenroll` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `copyrights` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `blockcnt` INT(10) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `msgset` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `chatset` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `topbar` VARCHAR(32) NOT NULL DEFAULT \'|\', '
	. ' `cploc` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `available` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `lockaid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `theme` VARCHAR(32) NOT NULL DEFAULT \'default.css\', '
	. ' `latepasshrs` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'24\', '
	. ' `picicons` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `newflag` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `ltisecret` VARCHAR(10) NOT NULL, '
	. ' INDEX(`ownerid`), INDEX(`name`), INDEX(`available`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Course list\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_courses created<br/>';

$sql = 'CREATE TABLE `imas_assessments` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `name` VARCHAR(254) NOT NULL, '
	. ' `summary` TEXT NOT NULL, '
        . ' `intro` TEXT NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `enddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `reviewdate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `timelimit` INT(10) NOT NULL DEFAULT \'0\', '
        . ' `displaymethod` VARCHAR(20) NOT NULL, '
        . ' `defpoints` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'10\', '
        . ' `defattempts` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'1\', '
        . ' `deffeedback` VARCHAR(20) NOT NULL, '
        . ' `defpenalty` VARCHAR(6) NOT NULL DEFAULT \'0\', '
	. ' `itemorder` TEXT NOT NULL, '
	. ' `shuffle` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `gbcategory` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `password` VARCHAR(15) NOT NULL, '
	. ' `cntingb` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `minscore` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `showcat` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `showhints` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `showtips` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `isgroup` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `groupsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `reqscoreaid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `reqscore` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `noprint` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\','
	. ' `groupmax` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'6\','
	. ' `allowlate` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\','
	. ' `eqnhelper` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `exceptionpenalty` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `ltisecret` VARCHAR(10) NOT NULL, '
	. ' `endmsg` TEXT NOT NULL, '
	. ' `caltag` CHAR(2) NOT NULL DEFAULT \'?R\', '
	. ' `tutoredit` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`courseid`), INDEX(`startdate`), INDEX(`enddate`),'
	. ' INDEX(`cntingb`), INDEX(`reviewdate`), INDEX(`avail`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Assessment info\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_assessments created<br/>';

$sql = 'CREATE TABLE `imas_questions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `assessmentid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `questionsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `points` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'9999\', '
        . ' `attempts` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'9999\', '
        . ' `penalty` VARCHAR(6) NOT NULL DEFAULT \'9999\', '
	. ' `category` VARCHAR(254) NOT NULL DEFAULT \'0\','
	. ' `regen` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `showans` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `withdrawn` CHAR(1) NOT NULL DEFAULT \'0\','
        . ' INDEX (`assessmentid`), INDEX(`questionsetid`), INDEX(`category`) '
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Questions in an assessment\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_questions created<br/>';

$sql = 'CREATE TABLE `imas_questionset` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `uniqueid` BIGINT(16) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `adddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `lastmoddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `ownerid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `author` VARCHAR( 254 ) NOT NULL DEFAULT \'unknown\', '
        . ' `userights` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'2\', '
        . ' `description` VARCHAR(254) NULL, '
        . ' `qtype` VARCHAR(20) NOT NULL, '
        . ' `control` TEXT NOT NULL, '
        . ' `qcontrol` TEXT NOT NULL, '
        . ' `qtext` TEXT NOT NULL, '
        . ' `answer` TEXT NOT NULL, '
	. ' `hasimg` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\','
	. ' `ancestors` TEXT NOT NULL, '
	. ' INDEX (`ownerid`), INDEX(`userights`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Actual set of questions\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_questionset created<br/>';

$sql = 'CREATE TABLE `imas_qimages` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `qsetid` INT(10) UNSIGNED NOT NULL, '
        . ' `var` VARCHAR(50) NOT NULL, '
        . ' `filename` VARCHAR(100) NOT NULL, '
        . ' `alttext` VARCHAR(254) NOT NULL,'
        . ' INDEX (`qsetid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Static image ref for questionset\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_qimages created<br/>';

$sql = 'CREATE TABLE `imas_items` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `itemtype` VARCHAR(20) NOT NULL, '
        . ' `typeid` INT(10) UNSIGNED NOT NULL,'
        . ' INDEX (`courseid`), INDEX(`typeid`), INDEX(`itemtype`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Items within a course\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_items created<br/>';

$sql = 'CREATE TABLE `imas_assessment_sessions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
	. ' `assessmentid` INT(10) UNSIGNED NOT NULL, '
	. ' `agroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `questions` TEXT NOT NULL, '
        . ' `seeds` TEXT NOT NULL, '
        . ' `scores` TEXT NOT NULL, '
	. ' `attempts` TEXT NOT NULL, '
	. ' `lastanswers` TEXT NOT NULL, '
	. ' `reattempting` VARCHAR(255) NOT NULL, '
        . ' `starttime` INT(10) NOT NULL, '
	. ' `endtime` INT(10) NOT NULL, '
	. ' `bestseeds` TEXT NOT NULL, '
        . ' `bestscores` TEXT NOT NULL, '
	. ' `bestattempts` TEXT NOT NULL, '
	. ' `bestlastanswers` TEXT NOT NULL, '
	. ' `reviewseeds` TEXT NOT NULL, '
        . ' `reviewscores` TEXT NOT NULL, '
	. ' `reviewattempts` TEXT NOT NULL, '
	. ' `reviewlastanswers` TEXT NOT NULL, '
	. ' `reviewreattempting` VARCHAR(255) NOT NULL, '
	. ' `feedback` TEXT NOT NULL,'
        . ' INDEX (`userid`), INDEX(`assessmentid`), INDEX(`agroupid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Assessment Sessions\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_assessment_sessions created<br/>';

$sql = 'CREATE TABLE `imas_sessions` ('
        . ' `sessionid` VARCHAR(32) NOT NULL, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
        . ' `time` INT(10) UNSIGNED NOT NULL, '
	. ' `tzoffset` SMALLINT(4) NOT NULL DEFAULT \'0\', '
	. ' `sessiondata` TEXT NOT NULL, '
        . ' PRIMARY KEY (`sessionid`), INDEX(`time`) '
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Session data\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_sessions created<br/>';

$sql = 'CREATE TABLE `imas_inlinetext` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `title` VARCHAR(254) NOT NULL, '
        . ' `text` TEXT NOT NULL, '
	. ' `startdate` INT(10) UNSIGNED NOT NULL, '
        . ' `enddate` INT(10) UNSIGNED NOT NULL, '
	. ' `fileorder` TEXT NOT NULL, '
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `oncal` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `caltag` CHAR(1) NOT NULL DEFAULT \'!\', '
        . ' INDEX (`courseid`), INDEX(`oncal`), INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Inline text items\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_inlinetext created<br/>';

$sql = 'CREATE TABLE `imas_instr_files` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `description` VARCHAR(254) NOT NULL, '
        . ' `filename` VARCHAR(100) NOT NULL, '
        . ' `itemid` INT(10) UNSIGNED NOT NULL,'
        . ' INDEX (`itemid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Inline text file attachments\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_instr_files created<br/>';

$sql = 'CREATE TABLE `imas_linkedtext` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `title` VARCHAR(254) NOT NULL, '
        . ' `summary` TEXT NOT NULL, '
        . ' `text` TEXT NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL, '
        . ' `enddate` INT(10) UNSIGNED NOT NULL,'
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `oncal` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `target` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `caltag` CHAR(1) NOT NULL DEFAULT \'!\', '
        . ' INDEX (`courseid`), INDEX(`oncal`), INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Linked Text Items\';'; 
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_linkedtext created<br/>';

$sql = 'CREATE TABLE `imas_exceptions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
        . ' `assessmentid` INT(10) UNSIGNED NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL, '
        . ' `enddate` INT(10) UNSIGNED NOT NULL, '
	. ' `islatepass` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`userid`), INDEX(`assessmentid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Per student exceptions to assessment start/end date\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_exceptions created<br/>';

$sql = 'CREATE TABLE `imas_libraries` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `uniqueid` BIGINT(16) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `adddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `lastmoddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' `ownerid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `userights` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'8\', '
	. ' `sortorder` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `parent` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `groupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\','
        . ' INDEX (`ownerid`), INDEX(`userights`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'QuestionSet Libraries\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_libraries created<br/>';

$sql = 'CREATE TABLE `imas_library_items` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `libid` INT(10) UNSIGNED NOT NULL, '
        . ' `qsetid` INT(10) UNSIGNED NOT NULL, '
	. ' `ownerid` INT(10) UNSIGNED NOT NULL,'
        . ' INDEX (`libid`), INDEX(`qsetid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Library assignments\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());	
echo 'imas_library_items created<br/>';

$sql = 'CREATE TABLE `imas_forums` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `name` VARCHAR(50) NOT NULL, '
        . ' `description` TEXT NOT NULL, '
	. ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `startdate` INT(10) UNSIGNED NOT NULL, '
	. ' `enddate` INT(10) UNSIGNED NOT NULL, '
	. ' `settings` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `sortby` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `defdisplay` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `replyby` INT(10) UNSIGNED NOT NULL DEFAULT \'2000000000\', '
	. ' `postby` INT(10) UNSIGNED NOT NULL DEFAULT \'2000000000\', '
	. ' `grpaid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `groupsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `points` SMALLINT(5) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `cntingb` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `gbcategory` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\','
        . ' INDEX (`courseid`), INDEX(`points`), INDEX(`grpaid`), '
	. ' INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`), INDEX(`replyby`), INDEX(`postby`) '
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Forums\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());	
echo 'imas_forums created<br/>';

$sql = 'CREATE TABLE `imas_forum_threads` ('
	. '`id` INT(10) UNSIGNED NOT NULL, '
	. '`forumid` INT(10) UNSIGNED NOT NULL, '
	. '`stugroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. '`lastposttime` INT(10) UNSIGNED NOT NULL, '
	. '`lastpostuser` INT(10) UNSIGNED NOT NULL, '
	. '`views` INT(10) UNSIGNED NOT NULL, '
	. ' PRIMARY KEY (`id`), INDEX (`forumid`), INDEX(`lastposttime`), INDEX(`stugroupid`) ) '
	. ' TYPE = InnoDB '
	. ' COMMENT = \'Forum threads\'';	
mysql_query($sql) or die("Query failed : $sql " . mysql_error());	
echo 'imas_forum_threads created<br/>';	
			
$sql = 'CREATE TABLE `imas_forum_posts` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `forumid` INT(10) UNSIGNED NOT NULL, '
	. ' `threadid` INT(10) UNSIGNED NOT NULL, '
	. ' `userid` INT(10) UNSIGNED NOT NULL, '
	. ' `postdate` INT(10) UNSIGNED NOT NULL, '
	. ' `views` INT(10) UNSIGNED NOT NULL, '
	. ' `parent` INT(10) UNSIGNED NOT NULL, '
        . ' `posttype` TINYINT(1) UNSIGNED NOT NULL, '
	. ' `subject` VARCHAR(254) NOT NULL, '
	. ' `message` TEXT NOT NULL, '
	. ' `isanon` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `replyby` INT(10) UNSIGNED NULL,'
	. ' `points` DECIMAL( 5, 1 ) UNSIGNED NULL, '
        . ' INDEX (`forumid`), INDEX(`threadid`), INDEX(`userid`), INDEX(`postdate`) '
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Forum Postings\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());	
echo 'imas_forum_posts created<br/>';

$sql = 'CREATE TABLE `imas_forum_views` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
	. ' `threadid` INT(10) UNSIGNED NOT NULL, '
        . ' `lastview` INT(10) UNSIGNED NOT NULL,'
	. ' `tagged` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`userid`), INDEX(`threadid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Forum last viewings\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());	
echo 'imas_forum_views created<br/>';

$sql = 'CREATE TABLE `imas_groups` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `name` VARCHAR(255) NOT NULL'
        . ' )'
        . ' TYPE = innodb;';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());	
echo 'imas_groups created<br/>';

$sql = 'CREATE TABLE `imas_diags` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `ownerid` INT(10) UNSIGNED NOT NULL, '
        . ' `name` VARCHAR(254) NOT NULL, '
	. ' `term` VARCHAR(10) NOT NULL, '
        . ' `public` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
        . ' `cid` INT(10) UNSIGNED NOT NULL, '
        . ' `idprompt` VARCHAR(254) NOT NULL, '
        . ' `ips` TEXT NOT NULL, '
        . ' `pws` TEXT NOT NULL, '
        . ' `sel1name` VARCHAR(254) NOT NULL, '
        . ' `sel1list` TEXT NOT NULL, '
	. ' `aidlist` TEXT NOT NULL, '
        . ' `sel2name` VARCHAR(254) NOT NULL, '
        . ' `sel2list` TEXT NOT NULL, '
	. ' `entryformat` CHAR(3) NOT NULL DEFAULT \'C0\', '
	. ' `forceregen` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `reentrytime` SMALLINT(5) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`ownerid`), INDEX(`public`), INDEX(`cid`)'
        . ' )'
        . ' TYPE = innodb;';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_diags created<br/>';

$sql = 'CREATE TABLE `imas_diag_onetime` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `diag` INT(10) UNSIGNED NOT NULL, '
        . ' `time` INT(10) UNSIGNED NOT NULL, '
        . ' `code` VARCHAR(9) NOT NULL, '
	. ' `goodfor` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`diag`), INDEX(`time`), INDEX(`code`)'
        . ' )'
        . ' TYPE = innodb;';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_diag_onetime created<br/>';

$sql = 'CREATE TABLE `imas_msgs` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
	. ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `title` VARCHAR(254) NOT NULL, '
        . ' `message` TEXT NOT NULL, '
        . ' `msgto` INT(10) UNSIGNED NOT NULL, '
        . ' `msgfrom` INT(10) UNSIGNED NOT NULL, '
        . ' `senddate` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `isread` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' `replied` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `parent` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `baseid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' INDEX (`msgto`), INDEX (`isread`), INDEX(`msgfrom`), INDEX(`baseid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Internal messages\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_msgs created<br/>';

$sql = 'CREATE TABLE `imas_forum_subscriptions` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `forumid` INT(10) UNSIGNED NOT NULL, '
        . ' `userid` INT(10) UNSIGNED NOT NULL,'
        . ' INDEX (`forumid`), INDEX(`userid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Forum subscriptions\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_forum_subscriptions created<br/>';


$sql = 'CREATE TABLE `imas_gbscheme` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `useweights` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `orderby` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `defaultcat` VARCHAR(254) NOT NULL DEFAULT \'0,0,1,0,-1,0\', '
	. ' `defgbmode` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'21\','
	. ' `usersort` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
	. ' INDEX(`courseid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Gradebook scheme\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_gbscheme created<br/>';

$sql = 'CREATE TABLE `imas_gbitems` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `name` VARCHAR(50) NOT NULL, '
        . ' `points` SMALLINT(4) NOT NULL DEFAULT \'0\', '
        . ' `showdate` INT(10) UNSIGNED NOT NULL, '
        . ' `gbcategory` INT(10) UNSIGNED NOT NULL, '
	. ' `cntingb` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\', '
	. ' `tutoredit` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' INDEX (`courseid`), INDEX(`showdate`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Gradebook offline items\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_gbitems created<br/>';

$sql = 'CREATE TABLE `imas_grades` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `gbitemid` INT(10) UNSIGNED NOT NULL, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
        . ' `score` DECIMAL(6,1) UNSIGNED NULL DEFAULT \'0.0\', '
	. ' `feedback` TEXT NOT NULL, '
        . ' INDEX (`userid`), INDEX(`gbitemid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Offline grades\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_grades created<br/>';

$sql = 'CREATE TABLE `imas_gbcats` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `name` VARCHAR(50) NOT NULL, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `scale` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `scaletype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
        . ' `chop` DECIMAL(3, 2) UNSIGNED NOT NULL DEFAULT \'1\', '
        . ' `dropn` TINYINT(2) NOT NULL DEFAULT \'0\', '
        . ' `weight` SMALLINT(4) NOT NULL DEFAULT \'-1\','
	. ' `hidden` TINYINT(1) NOT NULL DEFAULT \'0\', '
        . ' INDEX (`courseid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Gradebook Categories\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_gbcats created<br/>';

$sql = 'CREATE TABLE `imas_calitems` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `date` INT(10) UNSIGNED NOT NULL, '
        . ' `title` VARCHAR(254) NOT NULL, '
        . ' `tag` CHAR(1) NOT NULL,'
        . ' INDEX (`courseid`), INDEX(`date`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Calendar Items\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_calitems created<br/>';

$sql = 'CREATE TABLE `imas_stugroupset` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `courseid` INT(10) UNSIGNED NOT NULL, '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' INDEX (`courseid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Student Group Sets\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_stugroupset created<br/>';

$sql = 'CREATE TABLE `imas_stugroups` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `groupsetid` INT(10) UNSIGNED NOT NULL, '
        . ' `name` VARCHAR(254) NOT NULL, '
        . ' INDEX (`groupsetid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Student Groups\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_stugroups created<br/>';

$sql = 'CREATE TABLE `imas_stugroupmembers` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `stugroupid` INT(10) UNSIGNED NOT NULL, '
        . ' `userid` INT(10) UNSIGNED NOT NULL, '
        . ' INDEX (`stugroupid`), INDEX (`userid`)'
        . ' )'
        . ' TYPE = innodb'
        . ' COMMENT = \'Student Group Members\';';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_stugroupmembers created<br/>';

$sql = 'CREATE TABLE `imas_ltiusers` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `org` VARCHAR(32) NOT NULL, '
        . ' `ltiuserid` VARCHAR(32) NOT NULL, '
        . ' `userid` INT(10) NOT NULL'
        . ' )'
        . ' TYPE = innodb;';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_ltiusers created<br/>';

$sql = 'CREATE TABLE `imas_ltinonces` ('
        . ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . ' `nonce` TEXT NOT NULL, '
        . ' `time` INT(10) UNSIGNED NOT NULL'
        . ' )'
        . ' TYPE = innodb;';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'imas_ltinonces created<br/>';

$sql = 'CREATE TABLE `mc_sessions` ('
        . ' `userid` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,'
        . ' `sessionid` VARCHAR( 32 ) NOT NULL ,'
        . ' `name` VARCHAR( 254 ) NOT NULL ,'
        . ' `room` INT( 10 ) NOT NULL ,'
        . ' `lastping` INT( 10 ) UNSIGNED NOT NULL,'
        . ' `mathdisp` TINYINT( 1 ) NOT NULL ,'
        . ' `graphdisp` TINYINT( 1 ) NOT NULL,'
        . ' INDEX ( `sessionid` ), INDEX( `room` ), INDEX( `lastping` )'
        . ' ) TYPE = innodb;';
	
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'mc_sessions created<br/>';

$sql = 'CREATE TABLE `mc_msgs` ('
        . ' `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,'
        . ' `userid` INT( 10 ) UNSIGNED NOT NULL ,'
        . ' `msg` TEXT NOT NULL ,'
        . ' `time` INT( 10 ) UNSIGNED NOT NULL ,'
        . ' INDEX ( `userid` ), INDEX ( `time` )'
        . ' ) TYPE = innodb;';
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo 'mc_msgs created<br/>';

$md5pw = md5($password);
$now = time();
$sql = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email) VALUES ('$username','$md5pw',100,'$firstname','$lastname','$email')";
mysql_query($sql) or die("Query failed : $sql " . mysql_error());
echo "user $username created<br/>";

//write upgradecounter
require("upgrade.php");

echo "<p><b>Database setup complete</b>.  <a href=\"index.php\">Go to IMathAS login page</a>, or <a href=\"installexamples.php\">install a library of example questions</a> (will ask you to log in)</p>";
?>
</body>
</html>