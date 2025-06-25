<?php
//include_once($_SERVER['DOCUMENT_ROOT'] . "/config.php");
// include_once($_SERVER['DOCUMENT_ROOT'] . "/requests.php");
// include_once($_SERVER['DOCUMENT_ROOT'] . "/scratch.php");

// Maak een verbinding met de database

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();

function getPdoConnection()
{
    $host = $_ENV['DATABASE_HOST'];
    $database = $_ENV['DATABASE_NAME'];
    $user = $_ENV['DATABASE_USER'];
    $password = $_ENV['DATABASE_PASS'];
    $port = '3306';

    $dsn = "mysql:host={$host};port={$port};dbname={$database}";

    try {
        $conn = new PDO($dsn, $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $conn;
    } catch (PDOException $e) {
        throw new RuntimeException('Failed to connect to the database.', (int) $e->getCode(), $e);
    }
}

$conn = getPdoConnection();

if (isset($_POST['instelling-toggle']) && isset($_POST['instelling-status'])) {

    switch ($_POST['instelling-toggle']) {
        case 'maandag':
            if ($_POST['instelling-status'] == 'on') {
                $sql = "UPDATE instellingen SET maandag = 0";
            } else {
                $sql = "UPDATE instellingen SET maandag = 1";
            }
            break;
        case 'dinsdag':
            if ($_POST['instelling-status'] == 'on') {
                $sql = "UPDATE instellingen SET dinsdag = 0";
            } else {
                $sql = "UPDATE instellingen SET dinsdag = 1";
            }
            break;
        case 'woensdag':
            if ($_POST['instelling-status'] == 'on') {
                $sql = "UPDATE instellingen SET woensdag = 0";
            } else {
                $sql = "UPDATE instellingen SET woensdag = 1";
            }
            break;
        case 'donderdag':
            if ($_POST['instelling-status'] == 'on') {
                $sql = "UPDATE instellingen SET donderdag = 0";
            } else {
                $sql = "UPDATE instellingen SET donderdag = 1";
            }
            break;
        case 'vrijdag':
            if ($_POST['instelling-status'] == 'on') {
                $sql = "UPDATE instellingen SET vrijdag = 0";
            } else {
                $sql = "UPDATE instellingen SET vrijdag = 1";
            }
            break;
        case 'zaterdag':
            if ($_POST['instelling-status'] == 'on') {
                $sql = "UPDATE instellingen SET zaterdag = 0";
            } else {
                $sql = "UPDATE instellingen SET zaterdag = 1";
            }
            break;
        case 'zondag':
            if ($_POST['instelling-status'] == 'on') {
                $sql = "UPDATE instellingen SET zondag = 0";
            } else {
                $sql = "UPDATE instellingen SET zondag = 1";
            }
            break;
        default:
            echo "Er is een fout opgetreden.";
    }

    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute();
            echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        } catch (PDOException $e) {
            echo "Er is een fout opgetreden: " . $e->getMessage() . "<br>";
        }
    }
}

$sql = "SELECT * FROM instellingen LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();

$numRows = count($result);

if ($numRows == 0) {

    $time = time();
    $maandag = false;
    $dinsdag = false;
    $woensdag = false;
    $donderdag = false;
    $vrijdag = false;
    $zaterdag = false;
    $zondag = false;

    // Insert default values
    $sql = "INSERT INTO instellingen (tstamp, maandag, dinsdag, woensdag, donderdag, vrijdag, zaterdag, zondag) VALUES ($time, 1, 1, 1, 1, 1, 1, 1)";
    $stmt = $conn->prepare($sql);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        echo "Er is een fout opgetreden: " . $e->getMessage() . "<br>";
    }
} else {
    if ($result) {
        $firstRow = $result[0];
        $maandag = ($firstRow['maandag'] == 1) ? true : false;
        $dinsdag = ($firstRow['dinsdag'] == 1) ? true : false;
        $woensdag = ($firstRow['woensdag'] == 1) ? true : false;
        $donderdag = ($firstRow['donderdag'] == 1) ? true : false;
        $vrijdag = ($firstRow['vrijdag'] == 1) ? true : false;
        $zaterdag = ($firstRow['zaterdag'] == 1) ? true : false;
        $zondag = ($firstRow['zondag'] == 1) ? true : false;
    }
}

?>
<link href="/assets/css/form_des.css" rel="stylesheet">
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 55px;
        height: 29px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 21px;
        width: 21px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked+.slider {
        background-color: #2196F3;
    }

    input:focus+.slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    .switch {
        float: left;
    }

    span.toggle {
        height: 34px;
        display: inline-block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        width: 80%;
        margin-left: 1%;
        line-height: 35px;
    }

    .toggle-wrapper {
        display: flex;
        align-items: center;
        width: 100%;
        height: 34px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin: 10px 0px;
    }

    .switch.is-active input+.slider {
        background-color: #2196F3;
    }

    .switch.is-active input+.slider:before {
        transform: translateX(26px);
    }
</style>

<div class="mid-right">
    <div class="wrapper-all">
        <h2>Zichtbare routedagen</h2>
        <div class="instelling-container">
            <form id="toevoegen-instelling-maandag" method="post">
                <input type="hidden" name="instelling-status" value="<?php echo ($maandag == true) ? 'on' : 'off'; ?>">
                <div class="toggle-wrapper">
                    <label class="switch<?php echo ($maandag == true) ? ' is-active' : ''; ?>">
                        <input name="instelling-toggle" value="maandag" type="checkbox" onclick="submitForm('maandag')">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle maandag">Maandag</span>
                </div>
            </form>
            <form id="toevoegen-instelling-dinsdag" method="post">
                <input type="hidden" name="instelling-status" value="<?php echo ($dinsdag == true) ? 'on' : 'off'; ?>">
                <div class="toggle-wrapper">
                    <label class="switch<?php echo ($dinsdag == true) ? ' is-active' : ''; ?>">
                        <input name="instelling-toggle" value="dinsdag" type="checkbox" onclick="submitForm('dinsdag')">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle dinsdag">Dinsdag</span>
                </div>
            </form>
            <form id="toevoegen-instelling-woensdag" method="post">
                <input type="hidden" name="instelling-status" value="<?php echo ($woensdag == true) ? 'on' : 'off'; ?>">
                <div class="toggle-wrapper">
                    <label class="switch<?php echo ($woensdag == true) ? ' is-active' : ''; ?>">
                        <input name="instelling-toggle" value="woensdag" type="checkbox" onclick="submitForm('woensdag')">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle woensdag">Woensdag</span>
                </div>
            </form>
            <form id="toevoegen-instelling-donderdag" method="post">
                <input type="hidden" name="instelling-status" value="<?php echo ($donderdag == true) ? 'on' : 'off'; ?>">
                <div class="toggle-wrapper">
                    <label class="switch<?php echo ($donderdag == true) ? ' is-active' : ''; ?>">
                        <input name="instelling-toggle" value="donderdag" type="checkbox" onclick="submitForm('donderdag')">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle donderdag">Donderdag</span>
                </div>
            </form>
            <form id="toevoegen-instelling-vrijdag" method="post">
                <input type="hidden" name="instelling-status" value="<?php echo ($vrijdag == true) ? 'on' : 'off'; ?>">
                <div class="toggle-wrapper">
                    <label class="switch<?php echo ($vrijdag == true) ? ' is-active' : ''; ?>">
                        <input name="instelling-toggle" value="vrijdag" type="checkbox" onclick="submitForm('vrijdag')">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle vrijdag">Vrijdag</span>
                </div>
            </form>
            <form id="toevoegen-instelling-zaterdag" method="post">
                <input type="hidden" name="instelling-status" value="<?php echo ($zaterdag == true) ? 'on' : 'off'; ?>">
                <div class="toggle-wrapper">
                    <label class="switch<?php echo ($zaterdag == true) ? ' is-active' : ''; ?>">
                        <input name="instelling-toggle" value="zaterdag" type="checkbox" onclick="submitForm('zaterdag')">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle zaterdag">Zaterdag</span>
                </div>
            </form>
            <form id="toevoegen-instelling-zondag" method="post">
                <input type="hidden" name="instelling-status" value="<?php echo ($zondag == true) ? 'on' : 'off'; ?>">
                <div class="toggle-wrapper">
                    <label class="switch<?php echo ($zondag == true) ? ' is-active' : ''; ?>">
                        <input name="instelling-toggle" value="zondag" type="checkbox" onclick="submitForm('zondag')">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle zondag">Zondag</span>
                </div>
            </form>
            <script>
                function submitForm(instellingen) {
                    const form = document.getElementById("toevoegen-instelling-" + instellingen);
                    if (form && !form.dataset.submitted) {
                        form.dataset.submitted = true;
                        form.submit();
                    }
                }
            </script>
        </div>
    </div>
</div>
