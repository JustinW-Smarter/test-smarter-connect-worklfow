<?php
require_once '/home/hostsmar/connect.smarter.nl/requests.php';
require_once '/home/hostsmar/connect.smarter.nl/scratch.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();

// Maak een verbinding met de database
$conn = mysqli_connect($_ENV['DATABASE_HOST'], $_ENV['$DATABASE_USER'], $_ENV['$DATABASE_PASS'], $_ENV['$DATABASE_NAME']);
?>


<div class="mid-right">
    <div class="wrapper-all">
        <h2>SalesOrderLines</h2>
        <div class="table-container">

            <?php


            // ini_set('memory_limit', '2048M');
            // set_time_limit(10800);
            // // echo 'Huidige memory_limit: ' . ini_get('memory_limit') . "<br>";

            // // exit();
            // SalesOrderLinesSync();
            // SalesOrderLinesSyncAll();
            // updatePicklocaties();
            ?>
        </div>
    </div>
</div>
