<?php
//    var_dump($_SERVER);exit();
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
//session_start();
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
session_regenerate_id(true);
include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/functions.php");

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();

//    var_dump(trim($_ENV['DATABASE_HOST']));
//    exit;
// $conn = mysqli_connect('localhost', 'hostsmar_connectUser', 'c%6z7tJZThw1h3Ukv#jJ', 'hostsmar_connect');
$conn = mysqli_connect($_ENV['DATABASE_HOST'], $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASS'], $_ENV['DATABASE_NAME']);

$_SESSION['member']['username'] = $_POST['username'];

$username = $_SESSION['member']['username'];

$query = "SELECT id, username, password AS passwordHash FROM members WHERE username='$username' AND active = 1";
$result = $conn->query($query);
$num_rows = mysqli_num_rows($result);

$row = mysqli_fetch_assoc($result);
$_SESSION["member"] = $row;

if (is_null($_SESSION["member"])) {
    $_SESSION['error'] = 'De combinatie van het e-mailadres en het wachtwoord is niet bij ons bekend.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

$memberId = $_SESSION['member']['id'];
$query2 = "SELECT COUNT(*) AS incorrectLoginCount, MAX(tstamp) + 300 AS nextAvailableLogin
FROM `loginLog`
WHERE opmerking = 'IncorrectLogin'
AND doormemberid = $memberId
AND tstamp > unix_timestamp() - 300";

$result2 = $conn->query($query2);
$row2 = mysqli_fetch_assoc($result2);

if ($row2['incorrectLoginCount'] < 4 || $row2['nextAvailableLogin'] < time()) {
    $_SESSION['member']['password'] = $_POST['password'];
    if (password_verify($_SESSION['member']['password'], $_SESSION['member']['passwordHash'])) {
        $username = $_SESSION['member']['username'];
        $_SESSION["isLoggedIn"] = true;
        $_SESSION['member'] = $_SESSION['member']['id'];
        ini_set('session.gc_maxlifetime', 28800); // 8 hours in seconds
        setcookie(session_name(), session_id(), time() + 28800, "/");
        $_SESSION['fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
//        header('Location: /verzamelpicklijsten/');
        header('Location: /');
        exit();
    }
    $merged = [
        "opmerking" => "IncorrectLogin"
    ];
    require_once($_SERVER['DOCUMENT_ROOT'] . "/modules/logupdate.php");
    $_SESSION['error'] = 'De combinatie van het e-mailadres en het wachtwoord is niet bij ons bekend.';
} else {
    $_SESSION['error'] = 'Teveel verkeerde aanmeldingen. Probeer het over 5 minuten nog een keer.';
}
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
