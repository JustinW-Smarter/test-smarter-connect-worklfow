<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/functions.php");
require_once('modules/isloggedinCheck.php'); 
?>

<?php if (IntestingHelper::InTesting()): ?>

    <!DOCTYPE html>
    <html>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/head.php"); ?>
    <body>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/top.php"); ?>
        <div class="mid-contianer">
            <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/left.php"); ?>
            <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/mod/mod_pdf.php"); ?>
        </div>
    </body>
    </html>

<?php else : ?>
<?php endif; ?>