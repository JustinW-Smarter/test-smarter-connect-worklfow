<?php //if (IntestingHelper::InTesting()): ?>
<?php
    if (!ob_get_level()) {
        ob_start();
    }
    $pageTitle = "Registreren | ";
    include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/head.php");
?>
	<!DOCTYPE html>
	<html>
	<body>
	<div class="mid-contianer">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/mod/mod_registreren.php"); ?>
	</div>
	</body>
	</html>
<?php
    if (ob_get_level()) {
        ob_end_flush();
    }
?>