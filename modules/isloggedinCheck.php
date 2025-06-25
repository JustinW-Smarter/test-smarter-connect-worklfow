<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();

include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/functions.php");

$fingerprintNow = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);

if (
    !isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true ||
    !isset($_SESSION['fingerprint']) || $_SESSION['fingerprint'] !== $fingerprintNow
) {
    session_unset();
    session_destroy();
    header('Location: /inloggen/');
    exit;
}