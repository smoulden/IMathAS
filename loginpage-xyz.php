<?php
if (!isset($imasroot)) { //don't allow direct access to loginpage.php
	header("Location: index.php");
	exit;
}
//any extra CSS, javascript, etc needed for login page
	$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages-xyz.css\" type=\"text/css\" />\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/md5.js?v=2\" ></script>";
	//$placeinhead .= '<style type="text/css">html,body { margin: 0; body: 0;}</style>';
	$nologo = true;
	unset($coursetheme);
	require("header.php");
	if (isset($_SERVER['QUERY_STRING'])) {
		 $querys = '?'.$_SERVER['QUERY_STRING'];
	 } else {
		 $querys = '';
	 }
	 if (!empty($_SESSION['challenge'])) {
		 $challenge = $_SESSION['challenge'];
	 } else {
		 $challenge = base64_encode(microtime() . rand(0,9999));
		 $_SESSION['challenge'] = $challenge;
	 }
	 $pagetitle = "Home";
	 include("infoheader-xyz.php");
	 
	 $pref = 0;
	 if (isset($_COOKIE['mathgraphprefs'])) {
		 $prefparts = explode('-',$_COOKIE['mathgraphprefs']);
		 if ($prefparts[0]==2 && $prefparts[1]==2) { //img all
			$pref = 3;	 
		 } else if ($prefparts[0]==2) { //img math
			 $pref = 4;
		 } else if ($prefparts[1]==2) { //img graph
			 $pref = 2;
		 }
			 
	 }
?>
	


<div id="loginbox">
<form method="post" action="<?php echo $_SERVER['PHP_SELF'].$querys;?>" onsubmit="hashpw()">

<?php
	if ($haslogin) {
		if ($badsession) {
			echo '<p>Unable to establish a session.  Check that your browser is set to allow session cookies</p>';
		} else {
			echo "<p>Login Error.  Try Again</p>\n";
		}
	}
?>
<b>Login</b>
<table>
<tr><td><?php echo $loginprompt;?>:</td><td><input type="text" size="15" id="username" name="username" /></td></tr>
<tr><td>Password:</td><td><input type="password" size="15" id="passwordentry" /></td></tr>
</table>
<div id="settings">JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  Please enable JavaScript and reload this page</div>
<div class="textright"><a href="<?php echo $imasroot; ?>/forms.php?action=newuser">Register as a new student</a></div>
<div class="textright"><a href="<?php echo $imasroot; ?>/forms.php?action=lookupusername">Forgot Username</a><br/>
<a href="<?php echo $imasroot; ?>/forms.php?action=resetpw">Forgot Password</a></div>
<div class="textright"><a href="<?php echo $imasroot; ?>/checkbrowser.php">Browser check</a></div>
<input type="hidden" id="tzoffset" name="tzoffset" value="" />
<input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>" />
<input type="hidden" id="password" name="password" value="" />
<script type="text/javascript">        
        var thedate = new Date();  
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();  
</script> 


<script type="text/javascript"> 
	 function updateloginarea() {
		setnode = document.getElementById("settings"); 
		var html = ""; 
		html += 'Accessibility: ';
		html += "<a href='#' onClick=\"window.open('<?php echo $imasroot;?>/help.php?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\">Help<\/a>";
		html += '<br/><input type="radio" name="access" value="0" <?php if ($pref==0) {echo "checked=1";} ?> />Use visual display<br/>';
		html += '<input type="radio" name="access" value="2" <?php if ($pref==2) {echo "checked=1";} ?> />Force image-based graphs<br/>';
		html += '<input type="radio" name="access" value="4" <?php if ($pref==4) {echo "checked=1";} ?> />Force image-based math<br/>';
		html += '<input type="radio" name="access" value="3" <?php if ($pref==3) {echo "checked=1";} ?> />Force image based display<br/>';
		html += '<input type="radio" name="access" value="1">Use text-based display';
		
		if (AMnoMathML) {
			html += '<input type="hidden" name="mathdisp" value="0" />';
		} else {
			html += '<input type="hidden" name="mathdisp" value="1" />';
		}
		if (ASnoSVG) {
			html += '<input type="hidden" name="graphdisp" value="2" />';
		} else {
			html += '<input type="hidden" name="graphdisp" value="1" />';
		}
		if (!AMnoMathML && !ASnoSVG) {
			html += '<input type="hidden" name="isok" value="1" />';
		} 
		html += '<div class="textright"><input type="submit" value="Login" /><\/div>';
		//document.cookie = "test=test";
		//if (document.cookie.indexOf('test')!=-1) {
			setnode.innerHTML = html; 
			document.getElementById("username").focus();
		//} else {
		//	setnode.innerHTML = 'Cookies are not enabled.  Session cookies are needed to track your session.';
		//}
	}
	var existingonload = window.onload;
	if (existingonload) {
		window.onload = function() {existingonload(); updateloginarea();}
	} else {
		window.onload = updateloginarea;
	}
</script>
</form>
</div>
<img src="xyz/feature_home.gif"/>
<div class="text">
<br class="clear" />
<p class="copyright">&copy; XYZ Homework 2009 | <a href="privacy_policy.html">Privacy Policy</a> | <a href="mailto:contact@xyztextbooks.com">Contact Us</a>
<br/>xyzHomework is powered by <a href="http://www.imathas.com">IMathAS</a> &copy; 2006-2009 David Lippman</p>
</div>
<?php 
	require("footer.php");
?>
