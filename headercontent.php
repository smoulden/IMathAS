<?php 
if (!isset($flexwidth)) {
?>
<div id="headercontent">
<div id="headerrightlinks">
<?php 
echo "<a href=\"$imasroot/index.php\">Home</a> | ";
echo "<a href=\"#\" onclick=\"GB_show('Account Settings','$imasroot/forms.php?action=chguserinfo&greybox=true',800,500)\">Account Settings</a> | ";
if (isset($teacherid)) {
	echo "<a href=\"$imasroot/help.php?section=coursemanagement\">Help</a> ";
} else {
	echo "<a href=\"$imasroot/help.php?section=usingimas\">Help</a> ";
}
echo "| <a href=\"$imasroot/actions.php?action=logout\">Log Out</a>";

?>
</div>
<div id="headerbarlogo"></div>
</div>
<?php
}
?>
