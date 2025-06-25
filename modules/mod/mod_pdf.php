<?php

    require_once '/home/hostsmar/connect.smarter.nl/requests.php';
    require_once '/home/hostsmar/connect.smarter.nl/scratch.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    // Maak een verbinding met de database
    $conn = mysqli_connect($_ENV['DATABASE_HOST'], $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASS'], $_ENV['DATABASE_NAME']);

?>


<div class="mid-right">
    <div class="wrapper-all">
        <h2>PDF Test</h2>

        <?php

            require_once('/home/hostsmar/connect.smarter.nl/vendor/tecnickcom/tcpdf/tcpdf.php');

            $pdfsticker = new TCPDF('P', 'mm', 'A5', true, 'UTF-8', false); // 'P' voor Portrait
            $pdfsticker->SetPrintHeader(false);
            $pdfsticker->SetPrintFooter(false);

            $pdfsticker->AddPage();
            $pdfsticker->SetFont('helvetica', '', 20);
            $pdfsticker->Cell(0, 10, 'Smarter Connect', 0, 1, 'C'); // Middelste uitlijning

            $tstamp = time(); // Zorg dat $tstamp is gedefinieerd
            $savePath = '/home/hostsmar/connect.smarter.nl/assets/pdf/';
            $fileName = "Verzamelpicklijst-$tstamp.pdf";

            $pdfsticker->Output($savePath . $fileName, 'F');

        ?>
    </div>
</div>
