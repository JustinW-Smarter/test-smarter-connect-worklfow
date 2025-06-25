<?php
//include_once($_SERVER['DOCUMENT_ROOT'] . "/config.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/requests.php");
// Starttijd vastleggen
$start_time = microtime(true);
session_start();

$te_picken_orders = tepickenOrders();
$salesOrderLines = tepickenSalesOrderLines();
$leveringswijzes = beschikbareDeliveryMethods();

$te_picken_order_data = [];
$salesOrderLine_data = [];


require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();
function getPdoConnection()
{
    $dsn = "mysql:host={$_ENV['DATABASE_HOST']};dbname={$_ENV['DATABASE_NAME']}";

    try {
        $conn = new PDO($dsn, $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASS']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $conn;
    } catch (PDOException $e) {
        throw new RuntimeException('Failed to connect to the database.', (int) $e->getCode(), $e);
    }
}

$conn = getPdoConnection();

foreach ($te_picken_orders as $te_picken_order) {
    $timestamp = $te_picken_order['DeliveryDate']; // Originele waarde uit API
    preg_match('/\/Date\((\d+)\)\//', $te_picken_order['DeliveryDate'], $matches);

    $te_picken_order_data[] = [
        'ordernummer' => $te_picken_order['OrderNumber'],
        'order_beschrijving' => $te_picken_order['Description'],
        'order_besteld_door' => $te_picken_order['OrderedByName'],
        'OrderedByGuid' => $te_picken_order['OrderedBy'],
        'orderStatus' => getOrderStatusName($te_picken_order['Status']),
        'order_shipping_method_description' => $te_picken_order['ShippingMethodDescription'],
        'formatted_date' => date("d-m-Y", ($matches[1] / 1000)),
        'salesOrderLinesUri' => $te_picken_order['SalesOrderLines']['__deferred']['uri']
    ];

    //    echo '<pre>';
    //    var_dump($te_picken_order['OrderNumber']);
    //    echo '<br>';
    //    var_dump($te_picken_order['Description']);
    //    echo '</pre>';
}




$dag = $_GET['datum'];
setlocale(LC_TIME, 'nl_NL.UTF-8');
$gekozenDag = (strftime('%A', strtotime($dag)));


// Voeg sortering toe aan de orderdata voor sortering
foreach ($salesOrderLines as $salesOrderLine) {
    foreach ($te_picken_order_data as $order_data) {
        if ($salesOrderLine['OrderNumber'] === $order_data['ordernummer']) {

            $dagen = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
            $gekozenRouteDag = $_GET['dag'] ?? '';

            switch ($gekozenRouteDag) {
                case 'maandag':
                    $gekozenDag = "maandag";
                    break;
                case 'dinsdag':
                    $gekozenDag = "dinsdag";
                    break;
                case 'woensdag':
                    $gekozenDag = "woensdag";
                    break;
                case 'donderdag':
                    $gekozenDag = "donderdag";
                    break;
                case 'vrijdag':
                    $gekozenDag = "vrijdag";
                    break;
                case 'zaterdag':
                    $gekozenDag = "zaterdag";
                    break;
                case 'zondag':
                    $gekozenDag = "zondag";
                    break;
                default:
                    $gekozenDag = $gekozenDag;
                    break;
            }


            if (isset($gekozenDag)) {

                $sql = "SELECT * FROM klanten
                WHERE TRIM(" . $gekozenDag . "_leveringswijze) = TRIM(:order_shipping_method_description)
                AND TRIM(Name) = TRIM(:order_besteld_door)
                AND (" . $gekozenDag . "_sorting IS NOT NULL OR " . $gekozenDag . "_sorting != 0 OR " . $gekozenDag . "_leveringswijze != 0)
                ORDER BY " . $gekozenDag . "_sorting DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':order_shipping_method_description', $order_data['order_shipping_method_description']);
                $stmt->bindParam(':order_besteld_door', $order_data['order_besteld_door']);
                $stmt->execute();
                $routes = $stmt->fetchAll();

                // Het aantal rijen ophalen
                $numRows = count($routes);
                //var_dump($numRows);

                if ($numRows > 0) {
                    foreach ($routes as $route) {
                        if (trim((string) $order_data['order_shipping_method_description']) === trim((string) $route[$gekozenDag . '_leveringswijze']) && trim((string) $order_data['order_besteld_door']) === trim((string) $route['Name'])) {
                            $sortering = $route[$gekozenDag . '_sorting'] ?? 0;

                            $storageCode = null;

                            $sql2 = "SELECT * FROM storage WHERE itemCode = :ItemCode";
                            $stmt2 = $conn->prepare($sql2);
                            $stmt2->bindParam(':ItemCode', trim($salesOrderLine['ItemCode']), PDO::PARAM_STR);
                            $stmt2->execute();
                            $results = $stmt2->fetchAll();

                            $numRows2 = count($results);

                            if ($numRows2 > 0) {
                                foreach ($results as $result) {
                                    $storageCode = $result['DefaultStorageLocationCode'];
                                    $storageCodeArray = $result['DefaultStorageLocationCode'];
                                    $highlighted = $result['highlighted'];
                                    break;
                                }
                            }

                            if (!empty($storageCodeArray)) {
                            } else {
                                $storageCodeArray = 'Onbekend';
                            }

                            $sql3 = "SELECT * FROM klanten WHERE guid = :guidKlant";
                            $stmt3 = $conn->prepare($sql3);
                            $stmt3->bindParam(':guidKlant', trim($order_data['OrderedByGuid']), PDO::PARAM_STR);
                            $stmt3->execute();
                            $results3 = $stmt3->fetchAll();

                            $numRows3 = count($results3);

                            if ($numRows3 > 0) {
                                foreach ($results3 as $result) {
                                    $code = $result['Code'];
                                    break;
                                }
                            } else {
                                $code = "";
                            }

                            if (!isset($salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']])) {
                                $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']] = [
                                    'bezorgDatum' => date("d-m-Y", ($matches[1] / 1000)),
                                    'dag' => '',
                                    'order_besteld_door' => $order_data['order_besteld_door'],
                                    'klantCode' => $code,
                                    'order_beschrijving' => $order_data['order_beschrijving'],
                                    'item_beschrijving' => [],
                                    'items' => [],
                                    'orderlineguid' => [],
                                    'opslag' => [],
                                    'aantal' => [],
                                    'route' => true,
                                    'notitie' => [],
                                    'highlighted' => [],
                                    'eenheid' => [],
                                ];
                            }

                            //                            $itemIndex = array_search($salesOrderLine['ItemCode'], $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['items']);

                            // if ($itemIndex !== false) {
                            // Item bestaat al → tel hoeveelheid erbij op
                            //                                $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['aantal'][$itemIndex] += $salesOrderLine['Quantity'];
                            // } else {
                            // Nieuw item → voeg toe
                            $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['opslag'][] = $storageCode ?? 'Onbekend';
                            $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['items'][] = $salesOrderLine['ItemCode'];
                            $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['orderlineguid'][] = $salesOrderLine['ID'];
                            $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['aantal'][] = $salesOrderLine['Quantity'];
                            $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['item_beschrijving'][] = $salesOrderLine['ItemDescription'];
                            $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['notitie'][] = $salesOrderLine['Notes'];
                            $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['highlighted'][] = $highlighted ?? '';
                            $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['eenheid'][] = $salesOrderLine['UnitCode'];
                            // }


                            //            setlocale(LC_TIME, 'nl_NL.UTF-8');
                            //            $salesOrderLine_data[$order_data['order_shipping_method_description']][$salesOrderLine['OrderNumber']]['dag'] = strftime('%A', strtotime(date("l", ($matches[1] / 1000))));


                            //ksort($salesOrderLine_data);
                        }
                    }
                } else {
                    $sortering = -1;

                    $storageCode = null;

                    $sql2 = "SELECT * FROM storage WHERE itemCode = :ItemCode";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bindParam(':ItemCode', trim($salesOrderLine['ItemCode']), PDO::PARAM_STR);
                    $stmt2->execute();
                    $results = $stmt2->fetchAll();

                    $numRows2 = count($results);

                    if ($numRows2 > 0) {
                        foreach ($results as $result) {
                            $storageCode = $result['DefaultStorageLocationCode'];
                            $storageCodeArray = $result['DefaultStorageLocationCode'];
                            $highlighted = $result['highlighted'];
                            break;
                        }
                    }

                    if (!empty($storageCodeArray)) {
                    } else {
                        $storageCodeArray = 'Onbekend';
                    }

                    $sql3 = "SELECT * FROM klanten WHERE guid = :guidKlant";
                    $stmt3 = $conn->prepare($sql3);
                    $stmt3->bindParam(':guidKlant', trim($order_data['OrderedByGuid']), PDO::PARAM_STR);
                    $stmt3->execute();
                    $results3 = $stmt3->fetchAll();

                    $numRows3 = count($results3);

                    if ($numRows3 > 0) {
                        foreach ($results3 as $result) {
                            $code = $result['Code'];
                            break;
                        }
                    } else {
                        $code = "";
                    }


                    if (!isset($salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']])) {
                        $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']] = [
                            'bezorgDatum' => date("d-m-Y", ($matches[1] / 1000)),
                            'dag' => '',
                            'order_besteld_door' => $order_data['order_besteld_door'],
                            'klantCode' => $code,
                            'order_beschrijving' => $order_data['order_beschrijving'],
                            'item_beschrijving' => [],
                            'items' => [],
                            'orderlineguid' => [],
                            'opslag' => [],
                            'aantal' => [],
                            'route' => false,
                            'notitie' => [],
                            'highlighted' => [],
                            'eenheid' => [],
                        ];
                    }

                    //                    $itemIndex = array_search($salesOrderLine['ItemCode'], $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['items']);

                    //  if ($itemIndex !== false) {
                    // Item bestaat al → tel hoeveelheid erbij op
                    //                        $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['aantal'][$itemIndex] += $salesOrderLine['Quantity'];
                    // } else {
                    // Nieuw item → voeg toe
                    $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['opslag'][] = $storageCode ?? 'Onbekend';
                    $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['items'][] = $salesOrderLine['ItemCode'];
                    $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['orderlineguid'][] = $salesOrderLine['ID'];
                    $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['aantal'][] = $salesOrderLine['Quantity'];
                    $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['item_beschrijving'][] = $salesOrderLine['ItemDescription'];
                    $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['notitie'][] = $salesOrderLine['Notes'];
                    $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['highlighted'][] = $highlighted ?? '';
                    $salesOrderLine_data[$order_data['order_shipping_method_description']][$sortering][$order_data['order_besteld_door']][$storageCodeArray][$salesOrderLine['OrderNumber']]['eenheid'][] = $salesOrderLine['UnitCode'];
                    //  }
                }
            }
        }
    }
}
$afleverdatumFilter = $_GET['datum'];
$picklijstRegels = [];

if (strtotime($afleverdatumFilter)) {
    $sqlPicklijst = "SELECT id, orderlineGuid FROM picklijstRegels WHERE afleverdatum = :afleverdatumFilter";
    $stmtPicklijst = $conn->prepare($sqlPicklijst);

    // Bind de parameter veilig
    $stmtPicklijst->bindParam(':afleverdatumFilter', $afleverdatumFilter, PDO::PARAM_STR);

    // Voer de query uit
    $stmtPicklijst->execute();

    $resultPicklijst = $stmtPicklijst->fetchAll();
    foreach ($resultPicklijst as $row) {
        $picklijstRegels[$row['orderlineGuid']] = $row;
    }
}


foreach ($salesOrderLine_data as &$byShipping) {
    foreach ($byShipping as &$bySortering) {
        foreach ($bySortering as &$byBesteldDoor) {
            foreach ($byBesteldDoor as &$byStorage) {
                foreach ($byStorage as &$orderData) {
                    // Check of alles bestaat
                    if (isset($orderData['items'])) {
                        $count = count($orderData['items']);
                        $combined = [];

                        for ($i = 0; $i < $count; $i++) {
                            $combined[] = [
                                'item'         => $orderData['items'][$i]         ?? '',
                                'opslag'       => $orderData['opslag'][$i]        ?? '',
                                'orderlineid'  => $orderData['orderlineguid'][$i] ?? '',
                                'aantal'       => $orderData['aantal'][$i]        ?? '',
                                'beschrijving' => $orderData['item_beschrijving'][$i] ?? '',
                                'notitie'      => $orderData['notitie'][$i]       ?? '',
                                'highlighted'  => $orderData['highlighted'][$i]   ?? '',
                                'eenheid'      => $orderData['eenheid'][$i]       ?? '',
                            ];
                        }

                        // Sorteren op itemcode (numeriek)
                        usort($combined, fn($a, $b) => $a['item'] <=> $b['item']);

                        // Terugzetten in arrays
                        $orderData['items']             = array_column($combined, 'item');
                        $orderData['opslag']            = array_column($combined, 'opslag');
                        $orderData['orderlineguid']     = array_column($combined, 'orderlineid');
                        $orderData['aantal']            = array_column($combined, 'aantal');
                        $orderData['item_beschrijving'] = array_column($combined, 'beschrijving');
                        $orderData['notitie']           = array_column($combined, 'notitie');
                        $orderData['highlighted']       = array_column($combined, 'highlighted');
                        $orderData['eenheid']           = array_column($combined, 'eenheid');
                    }
                }
            }
        }
    }
}
unset($byShipping, $bySortering, $byBesteldDoor, $byStorage, $orderData); // referentie opschonen

?>
<link href="/assets/css/form_des.css" rel="stylesheet">
<style>
    .wrapper-btn-pdf {
        text-align: right;
        display: inline-block;
        width: 100%;
    }

    button.btn-pdf {
        background: unset;
        border: unset;
        font-size: 34px;
        color: #d50b11;
        border-radius: 10px;
        cursor: pointer;
    }

    td.icon-printer.printed {
        opacity: 1 !important;
    }

    td.icon-printer {
        opacity: 0.5;
        text-align: center;
        width: 20px;
    }

    .table-container th input,
    .table-container td input {
        cursor: pointer;
    }

    .loader-wrapper.inactive {
        display: none !important;
        visibility: hidden !important;
    }

    .loader-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        background: rgba(255, 255, 255, 0.8) !important;
        border: unset !important;
        z-index: 9999;
    }

    .loader-container {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .loader-cntent i {
        font-size: 48px;
        color: #333;
        animation: spinloading 2s linear infinite;
    }

    @keyframes spinloading {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .loader {
        border: 20px solid #f3f3f3;
        border-top: 20px solid #d50b11;
        border-radius: 100%;
        width: 100px;
        height: 100px;
        animation: spin 1.5s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
<script>
    jQuery(document).ready(function() {
        jQuery(".btn-pdf").click(function() {
            jQuery('.loader-wrapper').removeClass('inactive');
            jQuery('.loader-wrapper').addClass('active');
        });
    });
</script>
<div class="mid-right">
    <div class="wrapper-all">
        <div class="loader-wrapper inactive">
            <div class="loader-container">
                <div class="loader"></div>
            </div>
        </div>
        <div class="date-container">
            <form action="" method="get">
                <?php
                $datum = $_GET['datum'];
                $timestamp = strtotime($datum);
                $dag = date('l', $timestamp);

                // Vertalen van de Engelse dagnaam naar het Nederlands
                $dagen = [
                    'Monday' => 'Maandag',
                    'Tuesday' => 'Dinsdag',
                    'Wednesday' => 'Woensdag',
                    'Thursday' => 'Donderdag',
                    'Friday' => 'Vrijdag',
                    'Saturday' => 'Zaterdag',
                    'Sunday' => 'Zondag'
                ];

                $leveringswijzesCodes = array_map('trim', array_column($leveringswijzes, 'Code'));
                ?>
                <label for="datepicker">
                    <?php
                    if (!empty($datum)): ?>
                        <h2>Afleverdatum (<?= $dagen[$dag] ?>)</h2>
                    <?php
                    else: ?>
                        <h2>Afleverdatum</h2>
                    <?php
                    endif; ?>
                </label>
                <input type="date" name="datum" id="datepicker" onchange="removeDagAndSubmit()"
                    value="<?= htmlspecialchars($datum) ?>">
                <script>
                    function removeDagAndSubmit() {
                        const url = new URL(window.location);
                        url.searchParams.delete('dag');
                        window.history.replaceState(null, '', url); // Verwijdert 'dag' uit de URL zonder te refreshen
                        jQuery('#dag').val('');
                        document.getElementById('datepicker').form.submit(); // Form submitten
                    }
                </script>
                <?php
                if (isset($datum) && !empty($datum)): ?>
                    <label for="leveringswijze"></label>
                    <select name="leveringswijze" id="leveringswijze" onchange="this.form.submit()">
                        <option value="">Geen leveringswijze</option>
                        <?php
                        foreach ($leveringswijzes as $leveringswijze): ?>
                            <?php
                            $code = htmlspecialchars($leveringswijze['Code']);
                            if (in_array(
                                htmlspecialchars(trim($_GET['leveringswijze'])),
                                $leveringswijzesCodes,
                                true
                            )) {
                                $selected = (isset($_GET['leveringswijze']) && trim(
                                    $_GET['leveringswijze']
                                ) === trim($leveringswijze['Code'])) ? 'selected' : '';
                            }
                            ?>
                            <option value="<?= trim($code) ?>" <?= $selected ?? '' ?>><?= $code ?></option>
                        <?php
                        endforeach; ?>
                    </select>
                    <?php
                    $geselecteerdeDag = isset($_GET['dag']) && $_GET['dag'] !== '' ? $_GET['dag'] : $gekozenDag;

                    $dagen = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
                    ?>

                    <select name="dag" id="dag">
                        <?php foreach ($dagen as $dag): ?>
                            <option value="<?= $dag ?>" <?= ($geselecteerdeDag == $dag) ? 'selected' : '' ?>>
                                <?= ucfirst($dag) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a class="clear-filters" style="color: #222930;" href="/verzamelpicklijsten/?datum=<?= $_GET['datum'] ?>"><i class="fa-solid fa-arrows-rotate"></i></a>
                <?php
                endif; ?>
            </form>



            <!-- JavaScript om de leveringswijze-parameter te verwijderen als deze leeg is -->
            <script>
                document.getElementById('leveringswijze').addEventListener('change', function() {
                    const form = this.form;
                    const url = new URL(window.location.href);

                    if (!this.value) {
                        url.searchParams.delete('leveringswijze'); // Verwijder de parameter
                        window.location.href = url.toString(); // Redirect naar de schone URL
                    } else {
                        form.submit(); // Submit het formulier normaal
                    }
                });

                document.getElementById('dag').addEventListener('change', function() {
                    const form = this.form;
                    const url = new URL(window.location.href);

                    if (!this.value) {
                        url.searchParams.delete('dag'); // Verwijder de parameter
                        window.location.href = url.toString(); // Redirect naar de schone URL
                    } else {
                        form.submit(); // Submit het formulier normaal
                    }
                });
            </script>
        </div>

        <?php if (!$_SESSION['message']): ?>
            <form id="genererenPDF" action="/modules/createPDF.php" method="POST">
                <div class="wrapper-btn-pdf">
                    <button type="submit" class="btn-pdf">
                        <i class="fa fa-file-pdf"></i>
                    </button>
                </div>
                <?php
                $datatoPDF = json_encode($salesOrderLine_data);
                $countMethod = 0;
                ?>
                <script>
                    jQuery('button.btn-pdf').on('click', function(event) {
                        if (jQuery('input[name="orderlineguid[]"]:checked').length === 0) {
                            // Geen checkbox is geselecteerd
                            alert('Je hebt niks geselecteerd');
                            event.preventDefault(); // Voorkom dat de knop de formulier verzendt
                        } else {
                            var form = jQuery("#genererenPDF");

                            // Verzend het formulier via AJAX
                            jQuery.ajax({
                                type: 'POST',
                                url: form.attr('action'),
                                data: form.serialize(),
                                success: function(response) {
                                    setTimeout(function() {
                                        location.reload();
                                    }, 1000);
                                },
                                error: function(xhr, status, error) {
                                    // Foutafhandeling indien nodig
                                    //console.log('Er is een fout opgetreden: ' + error);
                                }
                            });
                        }

                    });

                    function getCookie(name) {
                        var nameEQ = name + "=";
                        var ca = document.cookie.split(';');
                        for (var i = 0; i < ca.length; i++) {
                            var c = ca[i];
                            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                        }
                        return null;
                    }

                    // Functie om een cookie in te stellen
                    function setCookie(name, value, days) {
                        var expires = "";
                        if (days) {
                            var date = new Date();
                            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                            expires = "; expires=" + date.toUTCString();
                        }
                        document.cookie = name + "=" + (value || "") + expires + "; path=/";
                    }
                </script>
                <input type="hidden" name="orderExact" value="<?= htmlspecialchars(json_encode($salesOrderLine_data), ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($salesOrderLine_data as $deliveryMethod => $sortedOrders): ?>
                    <?php if (htmlspecialchars($_GET['leveringswijze']) === '' || trim((string) $deliveryMethod) === htmlspecialchars($_GET['leveringswijze'])): ?>
                        <?php $countMethod++ ?>
                        <h2><?= $deliveryMethod ?></h2>
                        <table class="table-container" id="<?= $deliveryMethod ?>">
                            <thead>
                                <tr>
                                    <th style="width: 20px;"><input type="checkbox" id="select-all-<?= $countMethod ?>" name="" value="<?= $deliveryMethod ?>" checked></th>
                                    <th></th>
                                    <!-- <th>Pickregel</th> -->
                                    <th>Artikel</th>
                                    <th>Opslaglocatie</th>
                                    <th>Omschrijving</th>
                                    <th>Notitie</th>
                                    <th>Leveringswijze</th>
                                    <th>Besteld door</th>
                                    <th>Afleverdatum</th>
                                    <th>Ordernummer</th>
                                    <th>Aantal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Eerst de orders sorteren op 'sortering' in oplopende volgorde
                                //krsort($sortedOrders, SORT_NUMERIC);
                                ksort($sortedOrders, SORT_NUMERIC);

                                $count = 1;

                                // Loop door de gesorteerde orders
                                foreach ($sortedOrders as $sorting => $orders):
                                    foreach ($orders as $klant => $klantOrder):
                                        //usort($klantOrder, 'strnatcmp');
                                        ksort($klantOrder);
                                        foreach ($klantOrder as $storage => $storageOrder):

                                            foreach ($storageOrder as $orderNumber => $order):
                                                $items = $order['items'] ?? [];
                                                $opslagLocaties = $order['opslag'] ?? [];
                                                $amounts = $order['aantal'] ?? [];
                                                $descriptions = $order['item_beschrijving'] ?? [];
                                                $notes = $order['notitie'] ?? '';
                                                $orderlineGuid = $order['orderlineguid'] ?? '';
                                                $orderedBy = $order['order_besteld_door'] ?? '';
                                                $deliveryDate = $order['bezorgDatum'] ?? '';
                                                $hasRoute = $order['route'] ?? false;
                                                $highlighted = $order['highlighted'] ?? '';

                                                if (!$hasRoute) {
                                                    $count = 1;
                                                }

                                ?>
                                                <?php if (!empty($items) && is_array($items)): ?>
                                                    <?php foreach ($items as $index => $itemCode): ?>
                                                        <?php
                                                        $picklijstRegelsRows = 0;
                                                        $verzamelpicklijstId = $orderNumber . "." . $itemCode;
                                                        $guidOrdeline = $orderlineGuid[$index];
                                                        // $sql = "SELECT * FROM picklijstRegels WHERE picklijstId = '$verzamelpicklijstId' AND orderlineGuid = '$guidOrdeline' LIMIT 1";
                                                        // $stmt = $conn->prepare($sql);
                                                        // $stmt->execute();
                                                        // $result = $stmt->fetchAll();
                                                        // $picklijstRegelsRows = count($result);
                                                        ?>
                                                        <?php if ($highlighted[$index] === "1") : ?>
                                                            <tr class="row-highlight <?= !$hasRoute ? 'row-fout' : '' ?>">
                                                                <td><input type="checkbox" name="orderlineguid[]" value="<?= $orderlineGuid[$index] ?>" checked></td>
                                                                <?php if (isset($picklijstRegels[$guidOrdeline])) : ?>
                                                                    <td class="icon-printer printed"><i class="fa-solid fa-print"></i></td>
                                                                <?php else: ?>
                                                                    <td class="icon-printer"></td>
                                                                <?php endif; ?>
                                                                <!-- <td><?= $count ?></td> -->
                                                                <td><strong><?= htmlspecialchars($itemCode) ?></strong></td>
                                                                <td><strong><?= htmlspecialchars($opslagLocaties[$index]) ?></strong></td>
                                                                <td><strong><?= htmlspecialchars($descriptions[$index] ?? '') ?></strong></td>
                                                                <td><strong><?= htmlspecialchars($notes[$index] ?? '') ?></strong></td>
                                                                <td><strong><?= htmlspecialchars($deliveryMethod) ?></strong></td>
                                                                <td><strong><?= htmlspecialchars($orderedBy) ?></strong></td>
                                                                <td><strong><?= htmlspecialchars($deliveryDate) ?></strong></td>
                                                                <td><strong><?= htmlspecialchars($orderNumber) ?></strong></td>
                                                                <td><strong><?= htmlspecialchars($amounts[$index] ?? 0) ?></strong></td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <tr class="row-highlight <?= !$hasRoute ? 'row-fout' : '' ?>">
                                                                <td><input type="checkbox" name="orderlineguid[]" value="<?= $orderlineGuid[$index] ?>" checked></td>
                                                                <?php if (isset($picklijstRegels[$guidOrdeline])) : ?>
                                                                    <td class="icon-printer printed"><i class="fa-solid fa-print"></i></td>
                                                                <?php else: ?>
                                                                    <td class="icon-printer"></td>
                                                                <?php endif; ?>
                                                                <!-- <td><?= $count ?></td> -->
                                                                <td><?= htmlspecialchars($itemCode) ?></td>
                                                                <td><?= htmlspecialchars($opslagLocaties[$index]) ?></td>
                                                                <td><?= htmlspecialchars($descriptions[$index] ?? '') ?></td>
                                                                <td><?= htmlspecialchars($notes[$index] ?? '') ?></td>
                                                                <td><?= htmlspecialchars($deliveryMethod) ?></td>
                                                                <td><?= htmlspecialchars($orderedBy) ?></td>
                                                                <td><?= htmlspecialchars($deliveryDate) ?></td>
                                                                <td><?= htmlspecialchars($orderNumber) ?></td>
                                                                <td><?= htmlspecialchars($amounts[$index] ?? 0) ?></td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        <?php $count++; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <script>
                            jQuery(document).ready(function() {
                                // Wanneer de select-all checkbox wordt aangeklikt
                                jQuery('#select-all-<?= $countMethod ?>').on('change', function() {
                                    // Vind de bijbehorende tbody van de tabel
                                    var tbody = jQuery(this).closest('table').find('tbody');

                                    // Selecteer alle checkboxes in de tbody
                                    tbody.find('input[type="checkbox"]').prop('checked', this.checked);
                                });

                                // Event handler voor individuele checkboxes in de tbody
                                jQuery('table tbody input[type="checkbox"]').on('change', function() {
                                    var tbody = jQuery(this).closest('tbody');
                                    var allChecked = tbody.find('input[type="checkbox"]:checked').length === tbody.find('input[type="checkbox"]').length;

                                    // Update de select-all checkbox status
                                    tbody.closest('table').find('#select-all-<?= $countMethod ?>').prop('checked', allChecked);
                                });
                            });
                        </script>
                    <?php endif; ?>
                <?php endforeach; ?>
            </form>
        <?php endif; ?>

        <?php unset($_SESSION['message']) ?>
    </div>

</div>
