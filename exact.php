<!DOCTYPE html>
<html>

<head>
    <title>Smarter Connect | Exact Account Verbinden</title>
    <link href="/assets/css/form.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, minimum-scale=1.0, user-scalable=yes" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <!-- Javascript MooTools -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mootools/1.6.0/mootools-core.js"></script>
    <!-- jQuery -->

    <script src="https://code.jquery.com/jquery-3.6.1.js"></script>
</head>

<body>
    <div id="wrapper">
        <div class="logo">
            <a href="https://connect.smarter.nl/"><img src="/assets/img/logo.svg" alt=""></a>
        </div>
        <form method="post" action="">
            <input type="submit" name="send_request" value="Connect Exact Account">
        </form>
        <?php
        require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
        $dotenv->load();
        require 'requests.php';
        if (isset($_GET['code']) && !empty($_GET['code'])) {
            $authorizationCode = $_GET['code'];
            $responseData = fetchFirstToken($authorizationCode);
            echo $responseData;
        } else {
            if (isset($_POST['send_request'])) {
                $url = "{$_ENV['BASE_URL']}{$_ENV['AUTHORIZATION_ENDPOINT']}?client_id={$_ENV['CLIENT_ID']}&response_type=code&redirect_uri={$_ENV['REDIRECT_URI']}";
                header('Location: ' . $url);
                exit;
            }
        }
        ?>
    </div>
</body>

</html>
