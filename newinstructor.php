<?php
	
	require("config.php");
	$pagetitle = "New instructor account request";
	$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages-xyz.css\" type=\"text/css\">\n";
	$nologo = true;
	require("header.php");
	$pagetitle = "Instructor Account Request";
	require("infoheader-xyz.php");

	if (isset($_POST['firstname'])) {
		if (!isset($_POST['agree'])) {
			echo "<p>You must agree to the Terms and Conditions to set up an account</p>";
		} else if ($_POST['firstname']=='' || $_POST['lastname']=='' || $_POST['email']=='' || $_POST['school']=='' || $_POST['phone']=='' || $_POST['username']=='' || $_POST['password']=='') {
			echo "<p>Please provide all requested information</p>";

		} else if ($_POST['password']!=$_POST['password2']) {
			echo "<p>Passwords entered do not match.</p>";
		} else {
			$query = "SELECT id FROM imas_users WHERE SID='{$_POST['username']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				echo "<p>Username <b>{$_POST['username']}</b> is already in use.  Please try another</p>\n";
			} else {
				if (isset($CFG['GEN']['homelayout'])) {
					$homelayout = $CFG['GEN']['homelayout'];
				} else {
					$homelayout = '|0,2,3||0,1';
				}
				$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, homelayout) ";
				$md5pw = md5($_POST['password']);
				$query .= "VALUES ('{$_POST['username']}','$md5pw',0,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}','$homelayout');";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$newuserid = mysql_insert_id();
	//LOOK HERE: --->
				//if we want to enroll the instructor in a support course, then we'd do it here
				
				//$query = "INSERT INTO imas_students (userid,courseid) VALUES ('$newuserid',1)";
				//mysql_query($query) or die("Query failed : " . mysql_error());
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $installname <$sendfrom>\r\n";
				$subject = "New Instructor Account Request";
				//message sent to account approvers
				$message = "Name: {$_POST['firstname']} {$_POST['lastname']} <br/>\n";
				$message .= "Email: {$_POST['email']} <br/>\n";
				$message .= "School: {$_POST['school']} <br/>\n";
				$message .= "Phone: {$_POST['phone']} <br/>\n";
				$message .= "Username: {$_POST['username']} <br/>\n";
				mail($newinstuctoremail,$subject,$message,$headers);
	
	//EDIT THIS MESSAGE: -->
				//message sent to instuctors
				$message = "<p>Your new account request has been sent.</p>  ";
				$message .= "<p>This request is processed by hand, so please be patient.</p>";
				mail($_POST['email'],$subject,$message,$headers);
				
				echo $message;
				require("footer.php");
				exit;
			}
		}
	}
	if (isset($_POST['firstname'])) {$firstname=$_POST['firstname'];} else {$firstname='';}
	if (isset($_POST['lastname'])) {$lasname=$_POST['lastname'];} else {$lastname='';}
	if (isset($_POST['email'])) {$email=$_POST['email'];} else {$email='';}
	if (isset($_POST['phone'])) {$phone=$_POST['phone'];} else {$phone='';}
	if (isset($_POST['school'])) {$school=$_POST['school'];} else {$school='';}
	if (isset($_POST['username'])) {$username=$_POST['username'];} else {$username='';}
	
	echo "<h3>New Instructor Account Request</h3>\n";
	echo "<form method=post action=\"newinstructor.php\">\n";
	echo "<span class=form>First Name</span><span class=formright><input type=text name=firstname value=\"$firstname\" size=40></span><br class=form />\n";
	echo "<span class=form>Last Name</span><span class=formright><input type=text name=lastname value=\"$lastname\" size=40></span><br class=form />\n";
	echo "<span class=form>Email Address</span><span class=formright><input type=text name=email value=\"$email\" size=40></span><br class=form />\n";
	echo "<span class=form>Phone Number</span><span class=formright><input type=text name=phone value=\"$phone\" size=40></span><br class=form />\n";
	echo "<span class=form>School/College</span><span class=formright><input type=text name=school value=\"$school\" size=40></span><br class=form />\n";
	echo "<span class=form>Requested Username</span><span class=formright><input type=text name=username value=\"$username\" size=40></span><br class=form />\n";
	echo "<span class=form>Requested Password</span><span class=formright><input type=password name=password size=40></span><br class=form />\n";
	echo "<span class=form>Retype Password</span><span class=formright><input type=password name=password2 size=40></span><br class=form />\n";
	echo "<span class=form>I have read and agree to the Terms of Use (below)</span><span class=formright><input type=checkbox name=agree></span><br class=form />\n";
	echo "<div class=submit><input type=submit value=\"Request Account\"></div>\n";
	echo "</form>\n";
	echo "<h4>Terms of Use</h4>\n";
	echo "<p><em>This software is made available with <strong>no warranty</strong> and <strong>no guarantees</strong>.  The ";
	echo "server or software might crash or mysteriously lose all your data.  Your account or this service may be terminated without warning.  ";
	echo "No official support is provided. </em></p>\n";
	require("footer.php");
?>
