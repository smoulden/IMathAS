<?php  

require("validate.php");
if ($myrights<100) {
	echo "No rights, aborting";
	exit;
}

$query = "ALTER TABLE `imas_questionset` ADD `videoid` VARCHAR(254) NOT NULL DEFAULT '';"; 
$res = mysql_query($query);
if ($res===false) {
 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
}

$query = "ALTER TABLE `imas_questionset` ADD `bookref` VARCHAR(254) NOT NULL DEFAULT '';"; 
$res = mysql_query($query);
if ($res===false) {
 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
}
echo "Done";


?>
