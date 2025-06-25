<?php

// $documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);

// var_dump($documentRoot);

// exit();

    include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/functions.php");
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    if (!headers_sent()) {
        if (!ob_get_level()) {
            ob_start();
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        } else {
            session_regenerate_id(true);
        }
    }
//	session_start();
    session_regenerate_id(true);

    $fingerprintNow = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);

    if (
        !isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true ||
        !isset($_SESSION['fingerprint']) || $_SESSION['fingerprint'] !== $fingerprintNow
    ) {
        session_unset();
        session_destroy();
    } else {
        header('Location: /');
        exit;
    }
?>

<?php if (IntestingHelper::InTesting()): ?>

    <!DOCTYPE html>
    <html>
    <?php $pageTitle = "Inloggen | "?>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/head.php"); ?>

		<body>
        <style>
            .success-message {
                margin-top: 20px;
                padding: 15px;
                background-color: #ddffdd;
                color: #2e7d32;
                border-left: 5px solid #4CAF50;
                border-radius: 5px;
                font-weight: bold;
                text-align: center;
                position: absolute;
                right: 15px;
                bottom: 30px;
            }
        </style>
        <?php if (isset($_GET['success']) && $_GET['success'] === 'registry-success'): ?>
			<div class="success-message">Registratie was succesvol! U kunt nu inloggen.</div>
        <?php endif; ?>
		<div class="mid-contianer">
            <?php
                include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/mod/mod_inloggen.php"); ?>
		</div>
		</body>

		</html>

    <?php
    else : ?>
    <?php
    endif; ?>