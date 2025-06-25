<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/functions.php");
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
session_regenerate_id(true);

session_unset();
session_destroy();
header('Location: /inloggen/');
exit;

?>

<?php if (IntestingHelper::InTesting()): ?>

    <!DOCTYPE html>
    <html>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/head.php"); ?>

    <body>
        <div class="mid-contianer">
            <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/mod/mod_inloggen.php"); ?>
        </div>
    </body>

    </html>

<?php else : ?>
<?php endif; ?>