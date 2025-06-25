<?php

    ob_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();
    $conn = mysqli_connect(
        $_ENV['DATABASE_HOST'],
        $_ENV['DATABASE_USER'],
        $_ENV['DATABASE_PASS'],
        $_ENV['DATABASE_NAME']
    );

    $salesOrderLine_data = json_decode($_POST['orderExact'], true);
    $orderlineguid = $_POST['orderlineguid'];

    date_default_timezone_set('Europe/Amsterdam');


    $emptyOrder = [];

    if ($_SERVER["REMOTE_ADDR"] == '217.100.38.106') {
    } else {
        //PDF data naar database
        foreach ($salesOrderLine_data as $deliveryMethod => $sortedOrders) {
            $hasValidOrder = false;

            foreach ($sortedOrders as $sorting => $orders) {
                foreach ($orders as $klant => $klantOrder) {
                    foreach ($klantOrder as $storage => $storageOrder) {
                        foreach ($storageOrder as $orderNumber => $order) {
                            $guidOrderline = $order['orderlineguid'] ?? [];

                            foreach ($guidOrderline as $guid) {
                                if (in_array($guid, $orderlineguid)) {
                                    $hasValidOrder = true;
                                    break 4;
                                }
                            }
                        }
                    }
                }
            }


            if ($hasValidOrder) {
                ksort($sortedOrders, SORT_NUMERIC);

                foreach ($sortedOrders as $sorting => $orders) {
                    foreach ($orders as $klant => $klantOrder) {
                        ksort($klantOrder);

                        foreach ($klantOrder as $storage => $storageOrder) {
                            foreach ($storageOrder as $orderNumber => $order) {
                                $guidOrderline = $order['orderlineguid'] ?? [];

                                $found = false;
                                foreach ($guidOrderline as $index => $guid) {
                                    if (in_array($guid, $orderlineguid)) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $emptyOrder[] = $klant;
                                }
                            }
                        }

                        if (!in_array($klant, $emptyOrder)) {
                            foreach ($klantOrder as $storage => $storageOrder) {
                                foreach ($storageOrder as $orderNumber => $order) {
                                    $items = $order['items'] ?? [];
                                    $opslagLocaties = $order['opslag'] ?? [];
                                    $amounts = $order['aantal'] ?? [];
                                    $descriptions = $order['item_beschrijving'] ?? [];
                                    $notes = $order['notitie'] ?? '';
                                    $orderedBy = $order['order_besteld_door'] ?? '';
                                    $deliveryDate = $order['bezorgDatum'] ?? '';
                                    $hasRoute = $order['route'] ?? false;
                                    $guidOrderline = $order['orderlineguid'] ?? [];
                                    $highlighted = $order['highlighted'] ?? '';
                                    $eenheid = $order['eenheid'] ?? '';

                                    if (!empty($items) && is_array($items)) {
                                        foreach ($items as $index => $itemCode) {
                                            if (in_array($guidOrderline[$index], $orderlineguid)) {
                                                $descriptionsText = $descriptions[$index] ?? '';
                                                $amountsNumber = $amounts[$index] ?? '0';
                                                $eenheidText = $eenheid[$index] ?? '';
                                                $orderlineGuid = $guidOrderline[$index] ?? '';

                                                // Inhoud van picklijst naar database schrijven
                                                $verzamelpicklijstId = $orderNumber . "." . $itemCode;
                                                $sql = "SELECT * FROM picklijstRegels WHERE picklijstId = '$verzamelpicklijstId' AND orderlineGuid = '$orderlineGuid' LIMIT 1";
                                                $stmt = $conn->prepare($sql);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $num_rows = $result->num_rows;

                                                if ($num_rows > 0) {
                                                    //UPDATE: picklijstRegel staat al in database

                                                    $sql = "UPDATE picklijstRegels
                                            SET
                                                ordernummer = ?,
                                                orderlineGuid = ?,
                                                itemId = ?,
                                                itemOmschrijving = ?,
                                                leveringswijze = ?,
                                                besteldDoor = ?,
                                                afleverdatum = ?,
                                                aantal = ?,
                                                eenheid = ?
                                            WHERE picklijstId = ? AND orderlineGuid = ? LIMIT 1";

                                                    $stmt = $conn->prepare($sql);

                                                    if ($stmt === false) {
                                                        // die("Fout bij voorbereiden van statement: " . $conn->error);
                                                    }

                                                    $tstamp = time();

                                                    $stmt->bind_param(
                                                        'isissssisss',
                                                        $orderNumber,
                                                        $orderlineGuid,
                                                        $itemCode,
                                                        $descriptionsText,
                                                        $deliveryMethod,
                                                        $orderedBy,
                                                        date('Y-m-d', strtotime($deliveryDate)),
                                                        $amountsNumber,
                                                        $eenheidText,
                                                        $verzamelpicklijstId,
                                                        $orderlineGuid,
                                                    );

                                                    if (!$stmt->execute()) {
                                                        // echo "Fout bij bijwerken: " . $stmt->error;
                                                    } else {
                                                        // echo "Record succesvol bijgewerkt.";
                                                    }

                                                    $stmt->close();
                                                } else {
                                                    //INSERT: picklijstRegel staat nog niet in database

                                                    $sql = "INSERT INTO picklijstRegels (tstamp, picklijstId, ordernummer, orderlineGuid, itemId, itemOmschrijving, leveringswijze, besteldDoor, afleverdatum, aantal, eenheid)
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                                    $stmt = $conn->prepare($sql);

                                                    $tstamp = time();
                                                    $stmt->bind_param(
                                                        'isisissssis',
                                                        $tstamp,
                                                        $verzamelpicklijstId,
                                                        $orderNumber,
                                                        $orderlineGuid,
                                                        $itemCode,
                                                        $descriptionsText,
                                                        $deliveryMethod,
                                                        $orderedBy,
                                                        date('Y-m-d', strtotime($deliveryDate)),
                                                        $amountsNumber,
                                                        $eenheidText
                                                    );

                                                    if (!$stmt->execute()) {
                                                        // echo "Fout bij invoegen: " . $stmt->error;
                                                    } else {
                                                        // echo "Record succesvol toegevoegd.";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

//PDF genereren
    ob_end_clean();
    require_once('/home/hostsmar/connect.smarter.nl/vendor/tecnickcom/tcpdf/tcpdf.php');

    $pdf = new TCPDF();
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $page = 0;
    $emptyOrder = [];
    $allCheckedOrders = [];

    foreach ($salesOrderLine_data as $deliveryMethod => $sortedOrders) {
        $hasValidOrder = false;

        foreach ($sortedOrders as $sorting => $orders) {
            foreach ($orders as $klant => $klantOrder) {
                foreach ($klantOrder as $storage => $storageOrder) {
                    foreach ($storageOrder as $orderNumber => $order) {
                        $guidOrderline = $order['orderlineguid'] ?? [];

                        foreach ($guidOrderline as $guid) {
                            if (in_array($guid, $orderlineguid)) {
                                $hasValidOrder = true;
                                break 4;
                            }
                        }
                    }
                }
            }
        }


        if ($hasValidOrder) {
            $pdf->AddPage();

            $datumNu = date('d-m-y', time());

            // Breedte en hoogte van de pagina ophalen
            $pageWidth = $pdf->getPageWidth();
            $pageHeight = $pdf->getPageHeight();

            $afleverdatum = "";
            $time = time();


            foreach ($sortedOrders as $sorting => $orders) {
                foreach ($orders as $klant => $klantOrder) {
                    ksort($klantOrder);
                    foreach ($klantOrder as $storage => $storageOrder) {
                        foreach ($storageOrder as $orderNumber => $order) {
                            $afleverdatum = $order['bezorgDatum'] ?? '';
                        }
                    }
                }
            }

            // HTML-inhoud
            $html = '
        <div style="text-align: center;">
            <img src=$_SERVER['DOCUMENT_ROOT'] . "/assets/img/logo.png" width="300" alt="Smarter Connect"><br><br>
            <h1 style="font-size: 38pt; margin: 0;">' . $deliveryMethod . '</h1>
            <h1 style="font-size: 20pt; margin: 0;">Afleverdatum: ' . $afleverdatum . '</h1>
            <p style="margin: 0px; color: rgb(150, 150, 150); font-size: 12pt; font-style: italic;">Geprint op: ' . date(
                    'd-m-Y H:i:s'
                ) . '</p>
        </div>';

            // Breedte en hoogte van de content (ongeveer)
            $contentWidth = 180; // Pas eventueel aan
            $contentHeight = 100; // Pas eventueel aan

            // X en Y positie berekenen voor centrering
            $x = ($pageWidth - $contentWidth) / 2;
            $y = ($pageHeight - $contentHeight) / 2;

            // HTML weergeven op de juiste positie
            $pdf->writeHTMLCell($contentWidth, $contentHeight, $x, $y, $html, 0, 1, 0, true, 'C', true);

            ksort($sortedOrders, SORT_NUMERIC);

            foreach ($sortedOrders as $sorting => $orders) {
                foreach ($orders as $klant => $klantOrder) {
                    ksort($klantOrder);

                    foreach ($klantOrder as $storage => $storageOrder) {
                        foreach ($storageOrder as $orderNumber => $order) {
                            $guidOrderline = $order['orderlineguid'] ?? [];

                            $found = false;
                            foreach ($guidOrderline as $index => $guid) {
                                if (in_array($guid, $orderlineguid)) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $emptyOrder[] = $klant;
                            }
                        }
                    }

                    if (!in_array($klant, $emptyOrder)) {
                        $pdf->AddPage();

                        $countItem = 0;
                        $countPage = 1;
                        $countTotal = 1;
                        $afleverdatum = "";

                        foreach ($klantOrder as $storage => $storageOrder) {
                            foreach ($storageOrder as $orderNumber => $order) {
                                $afleverdatum = $order['bezorgDatum'] ?? '';
                                $items = $order['items'] ?? [];

                                if (!empty($items) && is_array($items)) {
                                    foreach ($items as $index => $itemCode) {
                                        $countTotal++;
                                    }
                                }
                                $OrderedByGuid = $order['klantCode'] ?? '';
                            }
                        }

                        $totalPage = ($countTotal <= 36) ? 1 : 2;


                        $pluspercent = 0;
                        $pluspercent = strlen($klant);
                        $width = 18 + ($pluspercent * 2);

                        $html = '<table style="width: 100%; font-size: 10pt;">
                        <tr>
                        <td style="width: 50%; vertical-align: top; font-size: 9pt;">
                            <table cellpadding="5" style="width: 100%; border: 1px solid #000;">
                            <tr><td style="width: ' . $width . '%;">Code: <strong>' . $OrderedByGuid . '</strong><br>Naam: <strong>' . $klant . '</strong></td></tr>
                            </table>
                        </td>
                        <td style="width: 50%; text-align: right; vertical-align: top;">
                            <span style="font-size: 25pt; font-weight: bold;">Picklijst</span>
                        </td>
                        </tr>
                    </table>';

                        $html .= '
                    <br><br>
                    <table cellpadding="3" style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                    <tr>
                            <th style="border-bottom: 1px solid #000; width: 6%;"><strong>Order</strong></th>
                            <th style="border-bottom: 1px solid #000; width: 11%;"><strong>Afleverdatum</strong></th>
                            <th style="border-bottom: 1px solid #000; width: 35%;"><strong>Leveringswijze</strong></th>
                            <th style="border-bottom: 1px solid #000; width: 48%;"><strong>Opmerking</strong></th>
                        </tr>
                        ';

                        $uniqueOrders = [];
                        foreach ($klantOrder as $storage => $storageOrder) {
                            foreach ($storageOrder as $orderNumber => $order) {
                                if (!isset($uniqueOrders[$orderNumber])) {
                                    $uniqueOrders[$orderNumber] = $order;
                                    $orderDescriptions = $order['order_beschrijving'] ?? [];
                                    $OrderedByGuid = $order['OrderedByGuid'] ?? '';
                                    $orderedBy = $order['order_besteld_door'] ?? '';
                                    $deliveryDate = $order['bezorgDatum'] ?? '';
                                    $lastOrderId = $orderNumber;
                                    $html .= '<tr>
                        <td><strong>' . $orderNumber . '</strong></td>
                        <td>' . $deliveryDate . '</td>
                        <td>' . $deliveryMethod . '</td>
                        <td>' . $orderDescriptions . '</td>
                    </tr>';
                                }
                            }
                        }

                        $html .= ' </table>
                    <br><br>
                    <table cellpadding="3" style="width: 100%; border-collapse: collapse;">
                        <tr style="background-color: #f0f0f0; font-size: 8pt;">
                            <th style="border-bottom: 1px solid #000; width: 6%;"><strong>Order</strong></th>
                            <th style="border-bottom: 1px solid #000; width: 11%;"><strong>Artikelcode</strong></th>
                            <th style="border-bottom: 1px solid #000; width: 35%;"><strong>Artikelomschrijving</strong></th>
                            <th style="border-bottom: 1px solid #000; width: 31%;"><strong>Opmerking</strong></th>
                            <th align="right" style="border-bottom: 1px solid #000; width: 8%;"><strong>Aantal</strong></th>
                            <th style="border-bottom: 1px solid #000; width: 1%;"></th>
                            <th align="left" style="border-bottom: 1px solid #000; width: 7%;"><strong>Gepickt</strong></th>
                            <th style="border-bottom: 1px solid #000; width: 1%;"></th>
                        </tr>
                    ';
                        $extraWitregelLast = false;
                        $extraWitregelSkip = true;

                        foreach ($klantOrder as $storage => $storageOrder) {
                            foreach ($storageOrder as $orderNumber => $order) {
                                $items = $order['items'] ?? [];
                                $opslagLocaties = $order['opslag'] ?? [];
                                $amounts = $order['aantal'] ?? [];
                                $descriptions = $order['item_beschrijving'] ?? [];
                                $notes = $order['notitie'] ?? '';
                                $orderedBy = $order['order_besteld_door'] ?? '';
                                $deliveryDate = $order['bezorgDatum'] ?? '';
                                $hasRoute = $order['route'] ?? false;
                                $guidOrderline = $order['orderlineguid'] ?? [];
                                $highlighted = $order['highlighted'] ?? '';
                                $eenheid = $order['eenheid'] ?? '';

                                if (!empty($items) && is_array($items)) {
                                    foreach ($items as $index => $itemCode) {
                                        if (in_array($guidOrderline[$index], $orderlineguid)) {
                                            $noteText = '';
                                            $descriptionsText = $descriptions[$index] ?? '';
                                            $amountsNumber = $amounts[$index] ?? '0';
                                            $eenheidText = $eenheid[$index] ?? '';
                                            $orderlineGuid = $guidOrderline[$index] ?? '';

                                            $extraWitregel = substr($opslagLocaties[$index], 0, 1);

                                            if ($extraWitregelLast !== $extraWitregel && !$extraWitregelSkip) {
                                                $extraWitregelLast = $extraWitregel;
                                                $html .= '
                                            <tr style="font-size: 8pt;">
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>';

                                                $countItem++;
                                            } elseif (!$extraWitregelLast) {
                                                $extraWitregelLast = $extraWitregel;
                                                $extraWitregelSkip = false;
                                            }


                                            if (isset($notes[$index])) {
                                                if (is_array($notes[$index])) {
                                                    $noteText = implode(
                                                        " | ",
                                                        array_filter(
                                                            $notes[$index],
                                                            fn($note) => !is_null($note) && $note !== ''
                                                        )
                                                    );
                                                } else {
                                                    $noteText = $notes[$index];
                                                }
                                            }

                                            $countItem++;

                                            if ($countItem >= 36) {
                                                $countPage++;
                                                $countItem = 0;

                                                $html .= '</table>';
                                                $pdf->writeHTML($html, true, false, true, false, '');
                                                $pdf->SetY(0);
                                                $pdf->SetFont('helvetica', 'I', 8);
                                                $pdf->SetTextColor(150, 150, 150);
                                                $time = time();
                                                $footerText = $klant . " - " . date(
                                                        'd-m-y',
                                                        $time
                                                    ) . " - 1/" . $totalPage;
                                                $pdf->Cell(0, 10, $footerText, 0, false, 'C');
                                                $pdf->SetFont('helvetica', '', 10);
                                                $pdf->SetTextColor(0, 0, 0);

                                                $html = '<table cellpadding="3" style="width: 100%; border-collapse: collapse;">
                                                <tr style="background-color: #f0f0f0; font-size: 8pt;">
                                                        <th style="border-bottom: 1px solid #000; width: 6%;"><strong>Order</strong></th>
                                                        <th style="border-bottom: 1px solid #000; width: 11%;"><strong>Artikelcode</strong></th>
                                                        <th style="border-bottom: 1px solid #000; width: 35%;"><strong>Artikelomschrijving</strong></th>
                                                        <th style="border-bottom: 1px solid #000; width: 31%;"><strong>Opmerking</strong></th>
                                                        <th align="right" style="border-bottom: 1px solid #000; width: 8%;"><strong>Aantal</strong></th>
                                                        <th style="border-bottom: 1px solid #000; width: 1%;"></th>
                                                        <th align="left" style="border-bottom: 1px solid #000; width: 7%;"><strong>Gepickt</strong></th>
                                                        <th style="border-bottom: 1px solid #000; width: 1%;"></th>
                                                </tr>
                                            ';
                                                $pdf->AddPage();
                                            }

                                            if ($highlighted[$index] === "1") {
                                                $html .= '
                                            <tr style="font-size: 8pt;">
                                                <td>' . $orderNumber . '</td>
                                                <td><strong>' . $itemCode . '</strong></td>
                                                <td><strong>' . $descriptionsText . '</strong></td>
                                                <td>' . $noteText . '</td>
                                                <td align="right"><strong>' . $amountsNumber . '</strong></td>
                                                <td></td>
                                                <td style="border-bottom: 1px solid black; height: 1px; line-height: 0.5;"></td>
                                                <td></td>
                                            </tr>';
                                            } else {
                                                $html .= '
                                            <tr style="font-size: 8pt;">
                                                <td>' . $orderNumber . '</td>
                                                <td>' . $itemCode . '</td>
                                                <td>' . $descriptionsText . '</td>
                                                <td>' . $noteText . '</td>
                                                <td align="right">' . $amountsNumber . '</td>
                                                <td></td>
                                                <td style="border-bottom: 1px solid black; height: 1px; line-height: 0.5;"></td>
                                                <td></td>
                                            </tr>';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $html .= '</table>';
                        $pdf->writeHTML($html, true, false, true, false, '');

                        $pdf->SetY(0);
                        $pdf->SetFont('helvetica', 'I', 8);
                        $pdf->SetTextColor(150, 150, 150);
                        $time = time();
                        $footerText = $klant . " - " . date('d-m-y', $time) . " - " . $countPage . "/" . $totalPage;
                        $pdf->Cell(0, 10, $footerText, 0, false, 'C');
                        $pdf->SetFont('helvetica', '', 10);
                        $pdf->SetTextColor(0, 0, 0);
                    }
                }
            }
        }
    }

    $pdfNaam = "picklijst-" . $afleverdatum . ".pdf";
    $pdf->Output($pdfNaam, 'D');
    exit;
