<?php

    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    function getDbConnection()
    {
        $DATABASE_HOST = $_ENV['DATABASE_HOST'];
        $DATABASE_USER = $_ENV['DATABASE_USER'];
        $DATABASE_PASS = $_ENV['DATABASE_PASS'];
        $DATABASE_NAME = $_ENV['DATABASE_NAME'];

        $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

        if (!$conn) {
            die("Database connection failed: " . mysqli_connect_error());
        }
        return $conn;
    }

    function fetchFirstToken($authorizationCode)
    {
        // require 'config.php';
        // // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
        $conn = getDbConnection();
//        $conn = getMysqliConnection();
        $url = $_ENV['BASE_URL'] . $_ENV['TOKEN_ENDPOINT'];
        $method = 'POST';
        $headers = setBasicHeaders();
        $body = [
            'code' => $authorizationCode,
            'redirect_uri' => $_ENV['REDIRECT_URI'],
            'client_id' => $_ENV['CLIENT_ID'],
            'client_secret' => $_ENV['CLIENT_SECRET'],
            'grant_type' => 'authorization_code'
        ];
        $response = requestHandler($url, $method, $headers, $body);

        $nieweAccessToken = $response['access_token'];

        // Controleer of de verbinding is geslaagd
        if ($conn->connect_error) {
            die("Verbinding mislukt: " . $conn->connect_error);
        }

        $rijId = 1;

        // Nieuwe waarden voor access_token en refresh_token
        $nieweAccessToken = $response['access_token'];
        $nieweRefreshToken = $response['refresh_token'];

        // var_dump($response);

        // var_dump($nieweAccessToken);
        // var_dump($nieweRefreshToken);
        // exit();

        // SQL-query voor het bijwerken van de tokens
        $sql = "UPDATE token SET access_token = ?, refresh_token = ? WHERE id = ?";

        // Voorbereide verklaring voor de query
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Voorbereide verklaring mislukt: " . $conn->error);
        }

        $stmt->bind_param("ssi", $nieweAccessToken, $nieweRefreshToken, $rijId);

        // Voer de voorbereide verklaring uit
        if ($stmt->execute()) {
            echo "Tokens zijn succesvol bijgewerkt in de database.<br>";
            header("Location: https://connect.smarter.nl/");
            exit();
        } else {
            echo "Fout bij het bijwerken van tokens: " . $stmt->error . "<br>";
        }

        // Sluit de voorbereide verklaring en de databaseverbinding
        $stmt->close();
        $conn->close();


        return $response;
    }

    ;

    function fetchNewToken()
    {
        // require 'config.php';
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        $conn = getDbConnection();
        $url = $_ENV['BASE_URL'] . $_ENV['TOKEN_ENDPOINT'];
        $method = 'POST';
        $headers = setBasicHeaders();

        if (!$conn) {
            error_log("[" . date('Y-m-d H:i:s') . "] Databaseverbinding mislukt: " . mysqli_connect_error());

            return [];
        }

        // Refresh token ophalen
        $sql = "SELECT refresh_token FROM token LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            error_log("[" . date('Y-m-d H:i:s') . "] Kan refresh_token niet ophalen uit database.");
            $conn->close();

            return [];
        }

        $row = $result->fetch_assoc();
        $refresh_token = $row["refresh_token"];

        $body = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $_ENV['CLIENT_ID'],
            'client_secret' => $_ENV['CLIENT_SECRET'],
        ];

        $response = requestHandler($url, $method, $headers, $body); // true = JSON POST

        if (!is_array($response) || !isset($response["access_token"], $response["refresh_token"])) {
            error_log(
                "[" . date('Y-m-d H:i:s') . "] Fout bij ophalen nieuwe tokens. Response: " . print_r($response, true)
            );
            $conn->close();

            return [];
        }

        $nieuwAccessToken = $response["access_token"];
        $nieuwRefreshToken = $response["refresh_token"];

        // Tokens updaten
        $sql = "UPDATE token SET access_token = ?, refresh_token = ? WHERE id = 1";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            error_log("[" . date('Y-m-d H:i:s') . "] Voorbereide verklaring mislukt: " . $conn->error);
            $conn->close();

            return [];
        }

        $id = 1;
        $stmt->bind_param("ss", $nieuwAccessToken, $nieuwRefreshToken);

        if (!$stmt->execute()) {
            error_log("[" . date('Y-m-d H:i:s') . "] Fout bij het bijwerken van tokens: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

        return setAuthHeaderstest(); // vernieuwde headers met nieuw token
    }


    function constructUrl($divisionCode, $endPoint, $queryParams = [])
    {
        // require 'config.php';

        $url = $_ENV['BASE_URL'] . "/api/v1/" . $divisionCode . $endPoint;
        if (!empty($queryParams)) {
            $url .= '?' . implode('&', array_map(function($key, $value) {
                    $value = rawurlencode($value);

                    return "$key=$value";
                }, array_keys($queryParams), $queryParams));
        }

        return $url;
    }

    ;

    function setBasicHeaders()
    {
        return [
            'Accept: application/json',
        ];
    }

    function fetchCurrentAccessToken()
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // Maak een verbinding met de database
        $conn = getDbConnection();

        // SQL-query om gegevens op te halen (aangezien er maar één rij is)
        $sql = "SELECT access_token, refresh_token FROM token LIMIT 1";

        $result = $conn->query($sql);

        // Haal de gegevens op en plaats ze in variabelen
        $row = $result->fetch_assoc();
        $access_token = $row["access_token"];

        // $access_token = $row["access_token"];
        return $access_token;
    }

    ;

    function setAuthHeaderstest()
    {
        return [
            'Accept: application/json',
            'Authorization: Bearer ' . fetchCurrentAccessToken(), #TODO: setup to get access token from database
        ];
    }

    function setAuthHeaders()
    {
        return [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . fetchCurrentAccessToken(), #TODO: setup to get access token from database
        ];
    }


    function fetch($dumpPath)
    {
        // $filePath = $dumpPath;
        $itemsFile = fopen($dumpPath, "r");
        $items = fread($itemsFile, filesize($dumpPath));
        $items = json_decode($items, true);

        return $items;
    }

    function requestHandler($url, $method, $headers, $body, $test = false)
    {
        $retryCount = 0;
        $maxRetries = 1;
        $response = null;

        $ch = curl_init();
        do {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if ($test == true) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            } elseif ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
            }
            $response = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // error_log("$responseCode");
            if ($responseCode == 401 && $retryCount < $maxRetries) {
                $retryCount++;
                $headers = fetchNewToken();
            } else {
                break;
            }
        } while (true);

        // error_log("$response");

        // var_dump($response);
        // exit();

        // return $response;
        if ($response === false) {
            return 'Error when making request to ' . $url . "\n Error: " . curl_error($ch);
        } else {
            $responseData = json_decode($response, true);
        }
        curl_close($ch);

        return $responseData;
    }

    ;

    function update($dumpPath, $divisionCode, $endPoint, $selectAttributes)
    {
        $queryParams = [
            '$select' => $selectAttributes,
        ];

        $url = constructUrl($divisionCode, $endPoint, $queryParams);

        $method = 'GET';
        $headers = setAuthHeaders();
        $finalResult = []; // Voor het geval je toch alles nog eens nodig hebt
        $itemsFile = fopen($dumpPath, "w");

        // Open het bestand voor schrijven en schrijf de opening van een JSON array
        fwrite($itemsFile, "[");

        do {
            // Voeg een retry-mechanisme toe voor de API-aanroepen
            $retryCount = 0;
            $maxRetries = 5;
            $success = false;

            while (!$success && $retryCount < $maxRetries) {
                $headers = setAuthHeaders();
                $result = requestHandler($url, $method, $headers, []);

                // var_dump($result);

                if ($result) {
                    $success = true;
                } else {
                    $retryCount++;
                    sleep(4); // Korte pauze voor de retry
                }
            }

            // Controleer of de API-aanroep succesvol was
            if (!$success) {
                // Error logging
                error_log("Failed to retrieve data after $maxRetries attempts.");
                break;
            }

            $data = $result["d"]["results"] ?? $result;

            // Schrijf de huidige batch naar het bestand
            foreach ($data as $index => $item) {
                if (!empty($finalResult) || $index > 0) { // Voeg een komma toe als het niet het eerste item is
                    fwrite($itemsFile, ",");
                }
                fwrite($itemsFile, json_encode($item));
            }

            // Voeg de resultaten toe aan de finalResult voor verdere verwerking indien nodig
            $finalResult = array_merge($finalResult, $data);

            // Controleer op de volgende pagina met "__next" en pas de URL aan voor de volgende iteratie
            $url = $result["d"]["__next"] ?? null;

            // Pauze tussen requests om rate limiting te vermijden
            sleep(1);
        } while (!empty($url));

        // Sluit de JSON array en het bestand af
        fwrite($itemsFile, "]");
        fclose($itemsFile);
    }


    function salesOrders()
    {
        // require 'config.php';
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/salesorder/SalesOrders";
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/assets/json/salesOrders.json";
        $selectAttributes = 'OrderID,Status,OrderNumber,DeliveryStatus,InvoiceStatus,OrderDate,OrderedByName';

        return update($divisionCode, $filePath, $endPoint, $selectAttributes);
    }

    function SalesOrderLines()
    {
        // require 'config.php';
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/bulk/salesorder/SalesOrderLines";
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/assets/json/salesOrders.json";
        $selectAttributes = 'ID,AmountDC,AmountFC,CostCenter,CostCenterDescription,CostPriceFC,CostUnit,CostUnitDescription,CustomerItemCode,CustomField,DeliveryDate,Description,Discount,Division,Item,ItemCode,ItemDescription,ItemVersion,ItemVersionDescription,LineNumber,NetPrice,Notes,OrderID,OrderNumber,Pricelist,PricelistDescription,Project,ProjectDescription,PurchaseOrder,PurchaseOrderLine,PurchaseOrderLineNumber,PurchaseOrderNumber,Quantity,ShopOrder,UnitCode,UnitDescription,UnitPrice,UseDropShipment,VATAmount,VATCode,VATCodeDescription,VATPercentage';

        return updateSalesOrderLines($divisionCode, $filePath, $endPoint, $selectAttributes);
    }

    function updateSalesOrderLines($dumpPath, $divisionCode, $endPoint, $selectAttributes)
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

        $conn = getDbConnection();


        $existingIDs = [];
        $resultExisting = $conn->query("SELECT exactID FROM SalesOrderLines");
        while ($row = $resultExisting->fetch_assoc()) {
            $existingIDs[$row['exactID']] = true;
        }

        $queryParams = [
            '$select' => $selectAttributes,
        ];

        $url = constructUrl($divisionCode, $endPoint, $queryParams);

        $method = 'GET';
        $headers = setAuthHeaders();
        $finalResult = []; // Voor het geval je toch alles nog eens nodig hebt
        // $itemsFile = fopen($dumpPath, "w");

        // Open het bestand voor schrijven en schrijf de opening van een JSON array
        // fwrite($itemsFile, "[");

        $countArray = 0;

        do {
            // Voeg een retry-mechanisme toe voor de API-aanroepen

            $retryCount = 0;
            $maxRetries = 5;
            $success = false;

            while (!$success && $retryCount < $maxRetries) {
                $headers = setAuthHeaders();
                $result = requestHandler($url, $method, $headers, []);

                if ($result) {
                    $success = true;
                } else {
                    $retryCount++;
                    sleep(4); // Korte pauze voor de retry
                }
            }

            // Controleer of de API-aanroep succesvol was
            if (!$success) {
                // Error logging
                error_log("Failed to retrieve data after $maxRetries attempts.");
                break;
            }

            //$data = $result["d"]["results"] ?? $result;

            // Schrijf de huidige batch naar het bestand
            // foreach ($data as $index => $item) {
            //     if (!empty($finalResult) || $index > 0) { // Voeg een komma toe als het niet het eerste item is
            //         fwrite($itemsFile, ",");
            //     }
            //     fwrite($itemsFile, json_encode($item));
            // }

            // Voeg de resultaten toe aan de finalResult voor verdere verwerking indien nodig
            //$finalResult = array_merge($finalResult, $data);

            // // echo ("<br><br>");
            // var_dump($result);
            // echo ("<br><br>");
            // var_dump($countArray);
            // var_dump($result["d"]["__next"]);
            // exit();

            // $jsonData = json_decode($result, true); // JSON van Exact API

            // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

            foreach ($result["d"]["results"] as $entry) {
                $columns = [
                    'exactID' => $entry['ID'] ?? null,
                    'AmountDC' => $entry['AmountDC'] ?? null,
                    'AmountFC' => $entry['AmountFC'] ?? null,
                    'CostCenter' => $entry['CostCenter'] ?? null,
                    'CostCenterDescription' => $entry['CostCenterDescription'] ?? null,
                    'CostPriceFC' => $entry['CostPriceFC'] ?? null,
                    'CostUnit' => $entry['CostUnit'] ?? null,
                    'CostUnitDescription' => $entry['CostUnitDescription'] ?? null,
                    'CustomerItemCode' => $entry['CustomerItemCode'] ?? null,
                    'CustomField' => is_array($entry['CustomField'] ?? null) ? json_encode(
                        $entry['CustomField']
                    ) : ($entry['CustomField'] ?? null),
                    'DeliveryDate' => isset($entry['DeliveryDate']) && preg_match(
                        '/\/Date\((\d+)\)\//',
                        $entry['DeliveryDate'],
                        $m
                    ) ? date('Y-m-d', $m[1] / 1000) : null,
                    'Description' => $entry['Description'] ?? null,
                    'Discount' => $entry['Discount'] ?? null,
                    'Division' => $entry['Division'] ?? null,
                    'Item' => $entry['Item'] ?? null,
                    'ItemCode' => $entry['ItemCode'] ?? null,
                    'ItemDescription' => $entry['ItemDescription'] ?? null,
                    'ItemVersion' => $entry['ItemVersion'] ?? null,
                    'ItemVersionDescription' => $entry['ItemVersionDescription'] ?? null,
                    'LineNumber' => $entry['LineNumber'] ?? null,
                    'NetPrice' => $entry['NetPrice'] ?? null,
                    'Notes' => $entry['Notes'] ?? null,
                    'OrderID' => $entry['OrderID'] ?? null,
                    'OrderNumber' => $entry['OrderNumber'] ?? null,
                    'Pricelist' => $entry['Pricelist'] ?? null,
                    'PricelistDescription' => $entry['PricelistDescription'] ?? null,
                    'Project' => $entry['Project'] ?? null,
                    'ProjectDescription' => $entry['ProjectDescription'] ?? null,
                    'PurchaseOrder' => $entry['PurchaseOrder'] ?? null,
                    'PurchaseOrderLine' => $entry['PurchaseOrderLine'] ?? null,
                    'PurchaseOrderLineNumber' => $entry['PurchaseOrderLineNumber'] ?? null,
                    'PurchaseOrderNumber' => $entry['PurchaseOrderNumber'] ?? null,
                    'Quantity' => $entry['Quantity'] ?? null,
                    'ShopOrder' => $entry['ShopOrder'] ?? null,
                    'UnitCode' => $entry['UnitCode'] ?? null,
                    'UnitDescription' => $entry['UnitDescription'] ?? null,
                    'UnitPrice' => $entry['UnitPrice'] ?? null,
                    'UseDropShipment' => $entry['UseDropShipment'] ?? null,
                    'VATAmount' => $entry['VATAmount'] ?? null,
                    'VATCode' => trim($entry['VATCode'] ?? ''),
                    'VATCodeDescription' => $entry['VATCodeDescription'] ?? null,
                    'VATPercentage' => $entry['VATPercentage'] ?? null,
                ];


                $exactID = $columns['exactID'];
                if (isset($existingIDs[$exactID])) {
                    continue; // overslaan, bestaat al
                }

                $dbCols = implode(",", array_keys($columns));
                $escapedValues = array_map(fn($v) => "'" . $conn->real_escape_string((string) ($v ?? '')) . "'",
                    array_values($columns));
                $values = implode(",", $escapedValues);
                $sql = "INSERT INTO SalesOrderLines ($dbCols) VALUES ($values)";
                $conn->query($sql);
            }

            $countArray++;

            error_log($countArray);

            // Controleer op de volgende pagina met "__next" en pas de URL aan voor de volgende iteratie
            $url = $result["d"]["__next"] ?? null;

            // Pauze tussen requests om rate limiting te vermijden

            unset($result);
            sleep(1);
        } while (!empty($url));

        // Sluit de JSON array en het bestand af
        // fwrite($itemsFile, "]");
        // fclose($itemsFile);
    }

    function SalesOrderLinesSyncAll()
    {
        SalesOrderLinesSync();

        sleep(1);

        SalesOrderLinesSyncDeleted();

        return true;
    }

    function SalesOrderLinesSync()
    {
        // require 'config.php';
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/sync/SalesOrder/SalesOrderLines";
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/assets/json/salesOrders.json";
        $selectAttributes = 'Timestamp,ID,AmountDC,AmountFC,CostCenter,CostCenterDescription,CostPriceFC,CostUnit,CostUnitDescription,CustomerItemCode,CustomField,DeliveryDate,Description,Discount,Division,Item,ItemCode,ItemDescription,ItemVersion,ItemVersionDescription,LineNumber,NetPrice,Notes,OrderID,OrderNumber,Pricelist,PricelistDescription,Project,ProjectDescription,PurchaseOrder,PurchaseOrderLine,PurchaseOrderLineNumber,PurchaseOrderNumber,Quantity,ShopOrder,UnitCode,UnitDescription,UnitPrice,UseDropShipment,VATAmount,VATCode,VATCodeDescription,VATPercentage';

        return updateSalesOrderLinesSync($divisionCode, $filePath, $endPoint, $selectAttributes);
    }

    function updateSalesOrderLinesSync($divisionCode, $filePath, $endPoint, $selectAttributes)
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

        $conn = getDbConnection();


        $existingIDs = [];
        $resultExisting = $conn->query("SELECT exactID FROM SalesOrderLines");
        while ($row = $resultExisting->fetch_assoc()) {
            $existingIDs[$row['exactID']] = true;
        }

        $lastSyncId = 0;

        $resultExisting = $conn->query("SELECT `Timestamp` FROM SalesOrderLines ORDER BY `Timestamp` DESC LIMIT 1");
        while ($row = $resultExisting->fetch_assoc()) {
            if ($row['Timestamp'] > 0) {
                $lastSyncId = $row['Timestamp'];
            }
        }

        if ($lastSyncId == 0) {
            $lastSyncId = 1;
        }

        $queryParams = [
            '$select' => $selectAttributes,
            '$filter' => "Timestamp gt " . $lastSyncId,
        ];

        $url = constructUrl($divisionCode, $endPoint, $queryParams);

        $method = 'GET';
        $headers = setAuthHeaders();

        $countArray = 0;

        do {
            $retryCount = 0;
            $maxRetries = 5;
            $success = false;

            while (!$success && $retryCount < $maxRetries) {
                $headers = setAuthHeaders();
                $result = requestHandler($url, $method, $headers, []);

                if ($result) {
                    $success = true;
                } else {
                    $retryCount++;
                    sleep(4); // Korte pauze voor de retry
                }
            }

            // Controleer of de API-aanroep succesvol was
            if (!$success) {
                // Error logging
                error_log("Failed to retrieve data after $maxRetries attempts.");
                break;
            }

            // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

            foreach ($result["d"]["results"] as $entry) {
                $columns = [
                    'Timestamp' => $entry['Timestamp'] ?? null,
                    'exactID' => $entry['ID'] ?? null,
                    'AmountDC' => $entry['AmountDC'] ?? null,
                    'AmountFC' => $entry['AmountFC'] ?? null,
                    'CostCenter' => $entry['CostCenter'] ?? null,
                    'CostCenterDescription' => $entry['CostCenterDescription'] ?? null,
                    'CostPriceFC' => $entry['CostPriceFC'] ?? null,
                    'CostUnit' => $entry['CostUnit'] ?? null,
                    'CostUnitDescription' => $entry['CostUnitDescription'] ?? null,
                    'CustomerItemCode' => $entry['CustomerItemCode'] ?? null,
                    'CustomField' => is_array($entry['CustomField'] ?? null) ? json_encode(
                        $entry['CustomField']
                    ) : ($entry['CustomField'] ?? null),
                    'DeliveryDate' => isset($entry['DeliveryDate']) && preg_match(
                        '/\/Date\((\d+)\)\//',
                        $entry['DeliveryDate'],
                        $m
                    ) ? date('Y-m-d', $m[1] / 1000) : null,
                    'Description' => $entry['Description'] ?? null,
                    'Discount' => $entry['Discount'] ?? null,
                    'Division' => $entry['Division'] ?? null,
                    'Item' => $entry['Item'] ?? null,
                    'ItemCode' => $entry['ItemCode'] ?? null,
                    'ItemDescription' => $entry['ItemDescription'] ?? null,
                    'ItemVersion' => $entry['ItemVersion'] ?? null,
                    'ItemVersionDescription' => $entry['ItemVersionDescription'] ?? null,
                    'LineNumber' => $entry['LineNumber'] ?? null,
                    'NetPrice' => $entry['NetPrice'] ?? null,
                    'Notes' => $entry['Notes'] ?? null,
                    'OrderID' => $entry['OrderID'] ?? null,
                    'OrderNumber' => $entry['OrderNumber'] ?? null,
                    'Pricelist' => $entry['Pricelist'] ?? null,
                    'PricelistDescription' => $entry['PricelistDescription'] ?? null,
                    'Project' => $entry['Project'] ?? null,
                    'ProjectDescription' => $entry['ProjectDescription'] ?? null,
                    'PurchaseOrder' => $entry['PurchaseOrder'] ?? null,
                    'PurchaseOrderLine' => $entry['PurchaseOrderLine'] ?? null,
                    'PurchaseOrderLineNumber' => $entry['PurchaseOrderLineNumber'] ?? null,
                    'PurchaseOrderNumber' => $entry['PurchaseOrderNumber'] ?? null,
                    'Quantity' => $entry['Quantity'] ?? null,
                    'ShopOrder' => $entry['ShopOrder'] ?? null,
                    'UnitCode' => $entry['UnitCode'] ?? null,
                    'UnitDescription' => $entry['UnitDescription'] ?? null,
                    'UnitPrice' => $entry['UnitPrice'] ?? null,
                    'UseDropShipment' => $entry['UseDropShipment'] ?? null,
                    'VATAmount' => $entry['VATAmount'] ?? null,
                    'VATCode' => trim($entry['VATCode'] ?? ''),
                    'VATCodeDescription' => $entry['VATCodeDescription'] ?? null,
                    'VATPercentage' => $entry['VATPercentage'] ?? null,
                ];

                $exactID = $columns['exactID'];

                if (isset($existingIDs[$exactID])) {
                    // Bijwerken als het exactID al bestaat
                    $updateCols = [];
                    foreach ($columns as $key => $value) {
                        if ($key !== 'exactID') {
                            $escapedValue = "'" . $conn->real_escape_string((string) ($value ?? '')) . "'";
                            $updateCols[] = "$key = $escapedValue";
                        }
                    }
                    $updateValues = implode(", ", $updateCols);
                    $sql = "UPDATE SalesOrderLines SET $updateValues WHERE exactID = '$exactID'";
                    $conn->query($sql);
                    // error_log("Update: " . $exactID);
                } else {
                    // Als exactID niet bestaat, dan invoegen (insert)
                    $dbCols = implode(",", array_keys($columns));
                    $escapedValues = array_map(fn($v) => "'" . $conn->real_escape_string((string) ($v ?? '')) . "'",
                        array_values($columns));
                    $values = implode(",", $escapedValues);
                    $sql = "INSERT INTO SalesOrderLines ($dbCols) VALUES ($values)";
                    $conn->query($sql);
                    // error_log("Insert: " . $exactID);
                }
            }

            $countArray++;

            // error_log($countArray);

            // Controleer op de volgende pagina met "__next" en pas de URL aan voor de volgende iteratie
            $url = $result["d"]["__next"] ?? null;

            // Pauze tussen requests om rate limiting te vermijden

            unset($result);
            sleep(1);
        } while (!empty($url));
    }

    function SalesOrderLinesSyncDeleted()
    {
        // require 'config.php';
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/sync/Deleted";
        $selectAttributes = 'Timestamp,DeletedBy,DeletedDate,Division,EntityKey,EntityType,ID';

        return updateSalesOrderLinesSyncDeleted($divisionCode, $endPoint, $selectAttributes);
    }

    function updateSalesOrderLinesSyncDeleted($divisionCode, $endPoint, $selectAttributes)
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

        $conn = getDbConnection();

        $existingIDs = [];
        $resultExisting = $conn->query("SELECT exactID FROM SalesOrderLines");
        while ($row = $resultExisting->fetch_assoc()) {
            $existingIDs[$row['exactID']] = true;
        }

        $lastSyncDeleteId = 0;

        $resultExistingTimestamp = $conn->query(
            "SELECT `TimestampDeleted` FROM SalesOrderLines ORDER BY `TimestampDeleted` DESC LIMIT 1"
        );
        while ($row = $resultExistingTimestamp->fetch_assoc()) {
            if ($row['TimestampDeleted'] > 0) {
                $lastSyncDeleteId = $row['TimestampDeleted'];
            }
        }

        if ($lastSyncDeleteId == 0) {
            $lastSyncDeleteId = 1;
        }

        $queryParams = [
            '$select' => $selectAttributes,
            '$filter' => "Timestamp gt " . $lastSyncDeleteId,
        ];

        $url = constructUrl($divisionCode, $endPoint, $queryParams);

        $method = 'GET';
        $headers = setAuthHeaders();

        do {
            $retryCount = 0;
            $maxRetries = 5;
            $success = false;

            while (!$success && $retryCount < $maxRetries) {
                $headers = setAuthHeaders();
                $result = requestHandler($url, $method, $headers, []);

                if ($result) {
                    $success = true;
                } else {
                    $retryCount++;
                    sleep(4); // Korte pauze voor de retry
                }
            }

            // Controleer of de API-aanroep succesvol was
            if (!$success) {
                // Error logging
                error_log("Failed to retrieve data after $maxRetries attempts.");
                break;
            }

            // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

            foreach ($result["d"]["results"] as $entry) {
                if ($entry['EntityType'] == 33) {
                    $exactID = $entry['EntityKey'] ?? null;
                    $Timestamp = $entry['Timestamp'] ?? null;

                    if (isset($existingIDs[$exactID])) {
                        $stmt = $conn->prepare(
                            "UPDATE SalesOrderLines SET active = 0, TimestampDeleted = ? WHERE exactID = ? LIMIT 1"
                        );
                        $stmt->bind_param("is", $Timestamp, $exactID);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            // Controleer op de volgende pagina met "__next" en pas de URL aan voor de volgende iteratie
            $url = $result["d"]["__next"] ?? null;

            // Pauze tussen requests om rate limiting te vermijden

            unset($result);
            sleep(1);
        } while (!empty($url));
    }


    function updateSuppliers()
    {
        // require 'config.php';
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        updateSuppliersClassification();

        $endPoint = "/crm/Accounts";
        $dumpPath = $_SERVER['DOCUMENT_ROOT'] . "/assets/json/suppliers.json";
        $selectAttributes = "ID,Name,Code,IsSupplier,ShippingMethod,StartDate,EndDate,Classification1";

        return update($dumpPath, $divisionCode, $endPoint, $selectAttributes);
    }

    function updateSuppliersClassification()
    {
        // require 'config.php';
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/crm/AccountClassifications";
        $dumpPath = $_SERVER['DOCUMENT_ROOT'] . "/assets/json/accountClassifications.json";
        $selectAttributes = "AccountClassificationName,AccountClassificationNameDescription,Code,Description";

        return update($dumpPath, $divisionCode, $endPoint, $selectAttributes);
    }

    function itemsToDatabaseOrangeTread()
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

        $conn = getDbConnection();

        // Controleer de verbinding
        if ($conn->connect_error) {
            die("Verbinding mislukt: " . $conn->connect_error);
        }

        // Locatie van het JSON-bestand
        $json_file = '/home/hostsmar/connect.smarter.nl/assets/json/suppliers.json';
        $json_file_classifications = '/home/hostsmar/connect.smarter.nl/assets/json/accountClassifications.json';


        // Lees het JSON-bestand
        $json_data = file_get_contents($json_file);
        $json_data_classifications = file_get_contents($json_file_classifications);
        $items_classifications = json_decode($json_data_classifications, true);
        $items = json_decode($json_data, true);

        // Loop door elk item in het JSON-bestand
        foreach ($items as $item) {
            $checkGuid = $item['Classification1'];
            $guidInUriFound = false;
            $CodeClassification = null;
            $CodeClassification = null;
            $DescriptionClassification = null;

            $foundItems = array_filter(
                $items_classifications,
                function($classification) use (
                    $checkGuid,
                    &$guidInUriFound,
                    &$CodeClassification,
                    &
                    $DescriptionClassification
                ) {
                    $uri = $classification["__metadata"]["uri"] ?? '';
                    if (preg_match("/guid'([a-f0-9-]+)'/i", $uri, $matches)) {
                        if ($matches[1] === $checkGuid) {
                            $guidInUriFound = true;
                            $CodeClassification = $classification["Code"] ?? '';
                            $DescriptionClassification = $classification["Description"] ?? '';

                            return true;
                        }
                    }

                    return false;
                }
            );

            // Haal de waarden op en verwijder overbodige spaties bij Code
            $Name = mysqli_real_escape_string($conn, $item['Name']); // Escapen voor SQL
            $Code = trim(mysqli_real_escape_string($conn, $item['Code'])); // Spaties weghalen en escapen
            $Guid = trim(mysqli_real_escape_string($conn, $item['ID'])); // Spaties weghalen en escapen
            $IsSupplier = $item['IsSupplier'] ? 1 : 0; // Zorg ervoor dat dit een integer is
            $startDate = (preg_match('/\/Date\((\d+)\)\//', $item['StartDate'], $m) && ($d = date(
                    'Y-m-d',
                    $m[1] / 1000
                )) !== '0000-00-00') ? $d : null;
            $endDate = (preg_match('/\/Date\((\d+)\)\//', $item['EndDate'], $m) && ($d = date(
                    'Y-m-d',
                    $m[1] / 1000
                )) !== '0000-00-00') ? $d : null;
            $ShippingMethod = isset($item['ShippingMethod']) && $item['ShippingMethod'] !== ''
                ? "'" . mysqli_real_escape_string($conn, $item['ShippingMethod']) . "'"
                : "NULL"; // Als leeg, sla ShippingMethod over

            // Controleer of het record al bestaat op basis van Code
            $sql_check = "SELECT Code FROM klanten WHERE Code = '$Code'";
            $result = $conn->query($sql_check);

            if ($endDate == null) {
                $endDate = date("Y-m-d", strtotime("+1 year"));
            }

            if ($result->num_rows == 0) {
                // Voeg een nieuw record toe als het niet bestaat
                $sql_insert = "INSERT INTO klanten (Name, Code, guid, StartDate, EndDate, Segment, IsSupplier" . ($ShippingMethod !== "NULL" ? ", ShippingMethod" : "") . ")
                   VALUES ('$Name', '$Code', '$Guid', '$startDate', '$endDate', '$DescriptionClassification', '$IsSupplier'" . ($ShippingMethod !== "NULL" ? ", $ShippingMethod" : "") . ")";
                $conn->query($sql_insert);
            } else {
                // Bouw de update-query dynamisch
                $sql_update = "UPDATE klanten SET Name = '$Name', guid = '$Guid', StartDate = '$startDate',  EndDate = '$endDate', Segment = '$DescriptionClassification', IsSupplier = '$IsSupplier'";
                if ($ShippingMethod !== "NULL") {
                    $sql_update .= ", ShippingMethod = $ShippingMethod";
                }
                $sql_update .= " WHERE Code = '$Code'";
                $conn->query($sql_update);
            }
        }

        // Sluit de verbinding
        $conn->close();

        // $actie = "Items naar Database (Orange Thread)";
        // logUpdate($actie);
    }

    ;

    function itemWarehousesDataAll()
    {
        // require 'config.php';
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/inventory/ItemWarehouses";
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/assets/json/ItemWarehouses.json";
        $selectAttributes = 'DefaultStorageLocationCode, ItemCode, ItemDescription, WarehouseCode, ItemStartDate, ItemEndDate';

        return update($filePath, $divisionCode, $endPoint, $selectAttributes);
    }


    function updatePicklocaties()
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

        $conn = getDbConnection();

        // include_once($_SERVER['DOCUMENT_ROOT'] . "/scratch.php");
        itemWarehousesDataAll();

        $json_file = '/home/hostsmar/connect.smarter.nl/assets/json/ItemWarehouses.json';

        // Lees het JSON-bestand
        $json_data_item = file_get_contents($json_file);
        $items = json_decode($json_data_item, true);

        $existingItems = $conn->query("SELECT itemCode FROM storage");
        $existingItemCodes = [];
        while ($row = $existingItems->fetch_assoc()) {
            $existingItemCodes[] = $row['itemCode'];
        }

        // Maak set voor snelle lookup
        $existingItemCodesSet = array_flip($existingItemCodes);
        foreach ($items as $itemWarehouse) {
            if ((string) $itemWarehouse['WarehouseCode'] !== "1") {
                continue;
            }

            $storageLocationCode = $itemWarehouse['DefaultStorageLocationCode'];
            $itemCode = $itemWarehouse['ItemCode'];
            $itemDescription = $itemWarehouse['ItemDescription'];
            $itemWarehouseCode = $itemWarehouse['WarehouseCode'];
            $ItemStartDate = preg_match('/\/Date\((\d+)\)\//', $itemWarehouse['ItemStartDate'], $m) ? date(
                'Y-m-d',
                $m[1] / 1000
            ) : null;
            $ItemEndDate = preg_match('/\/Date\((\d+)\)\//', $itemWarehouse['ItemEndDate'], $m) ? date(
                'Y-m-d',
                $m[1] / 1000
            ) : null;

            if (!isset($existingItemCodesSet[$itemCode])) {
                // INSERT
                $sql = "
                INSERT INTO storage
                (DefaultStorageLocationCode, itemCode, itemDescription, WarehouseCode, ItemStartDate, ItemEndDate, tstamp)
                VALUES
                (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssss",
                    $storageLocationCode,
                    $itemCode,
                    $itemDescription,
                    $itemWarehouseCode,
                    $ItemStartDate,
                    $ItemEndDate
                );
            } else {
                // UPDATE
                $sql = "
                UPDATE storage
                SET DefaultStorageLocationCode = ?,
                    itemDescription = ?,
                    WarehouseCode = ?,
                    ItemStartDate = ?,
                    ItemEndDate = ?,
                    tstamp = CURRENT_TIMESTAMP
                WHERE itemCode = ?
            ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssss",
                    $storageLocationCode,
                    $itemDescription,
                    $itemWarehouseCode,
                    $ItemStartDate,
                    $ItemEndDate,
                    $itemCode
                );
            }

            try {
                $stmt->execute();
            } catch(PDOException $e) {
                echo "Fout bij item $itemCode: " . $e->getMessage() . "<br>";
            }
        }
        //    echo "<strong>Insert/update uitgevoerd.</strong><hr>";
    }


    function SalesOrdersDatabase()
    {
        // require 'config.php';
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/SalesOrder/SalesOrders";
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/assets/json/salesOrders.json";
        $selectAttributes = 'OrderID,AmountDC,AmountDiscount,AmountDiscountExclVat,AmountFC,AmountFCExclVat,ApprovalStatus,ApprovalStatusDescription,Approved,Approver,ApproverFullName,Created,Creator,CreatorFullName,Currency,CustomField,DeliverTo,DeliverToContactPerson,DeliverToContactPersonFullName,DeliverToName,DeliveryAddress,DeliveryDate,DeliveryStatus,DeliveryStatusDescription,Description,Discount,Division,Document,DocumentNumber,DocumentSubject,IncotermAddress,IncotermCode,IncotermVersion,InvoiceStatus,InvoiceStatusDescription,InvoiceTo,InvoiceToContactPerson,InvoiceToContactPersonFullName,InvoiceToName,Modified,Modifier,ModifierFullName,OrderDate,OrderedBy,OrderedByContactPerson,OrderedByContactPersonFullName,OrderedByName,OrderNumber,PaymentCondition,PaymentConditionDescription,PaymentReference,Remarks,SalesChannel,SalesChannelCode,SalesChannelDescription,Salesperson,SalespersonFullName,SelectionCode,SelectionCodeCode,SelectionCodeDescription,ShippingMethod,ShippingMethodDescription,Status,StatusDescription,TaxSchedule,TaxScheduleCode,TaxScheduleDescription,WarehouseCode,WarehouseDescription,WarehouseID,YourRef';

        return updateSalesOrdersDatabase($divisionCode, $filePath, $endPoint, $selectAttributes);
    }

    function updateSalesOrdersDatabase($divisionCode, $filePath, $endPoint, $selectAttributes)
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

        $conn = getDbConnection();


        $existingIDs = [];
        $resultExisting = $conn->query("SELECT OrderID FROM SalesOrders");
        while ($row = $resultExisting->fetch_assoc()) {
            $existingIDs[$row['OrderID']] = true;
        }

        // $queryParams = array(
        //     '$select' => $selectAttributes
        // );


        $lastSyncDate = '2000-01-01T00:00:00'; // default

        $resultExisting = $conn->query("SELECT `Modified` FROM SalesOrders ORDER BY `Modified` DESC LIMIT 1");
        if ($row = $resultExisting->fetch_assoc()) {
            // $lastSyncDate = $row['Modified'];
            $lastSyncDate = $row['Modified']; // bv. "2025-05-02 10:23:24"
            $lastSyncDate = "datetime'" . str_replace(' ', 'T', $lastSyncDate) . "'";
        }

        // $queryParams = array(
        //     '$select' => $selectAttributes,
        //     '$filter' => "Modified gt datetime'" . $lastSyncDate . "'",
        // );

        $queryParams = [
            '$select' => $selectAttributes,
            '$filter' => "Modified gt " . $lastSyncDate,
        ];

        $url = constructUrl($divisionCode, $endPoint, $queryParams);

        // var_dump($lastSyncDate);
        // echo ("<br>");
        // var_dump($url);
        // exit();

        $method = 'GET';
        $headers = setAuthHeaders();

        $countArray = 0;

        do {
            $retryCount = 0;
            $maxRetries = 5;
            $success = false;

            while (!$success && $retryCount < $maxRetries) {
                $headers = setAuthHeaders();
                $result = requestHandler($url, $method, $headers, []);

                if ($result) {
                    $success = true;
                } else {
                    $retryCount++;
                    sleep(4); // Korte pauze voor de retry
                }
            }

            // Controleer of de API-aanroep succesvol was
            if (!$success) {
                // Error logging
                error_log("Failed to retrieve data after $maxRetries attempts.");
                break;
            }

            // $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

            foreach ($result["d"]["results"] as $entry) {
                $columns = [
                    'OrderID' => $entry['OrderID'] ?? null,
                    'AmountDC' => $entry['AmountDC'] ?? null,
                    'AmountDiscount' => $entry['AmountDiscount'] ?? null,
                    'AmountDiscountExclVat' => $entry['AmountDiscountExclVat'] ?? null,
                    'AmountFC' => $entry['AmountFC'] ?? null,
                    'AmountFCExclVat' => $entry['AmountFCExclVat'] ?? null,
                    'ApprovalStatus' => $entry['ApprovalStatus'] ?? null,
                    'ApprovalStatusDescription' => $entry['ApprovalStatusDescription'] ?? null,
                    'Approved' => isset($entry['Approved']) && preg_match(
                        '/\/Date\((\d+)\)\//',
                        $entry['Approved'],
                        $m
                    ) ? date('Y-m-d H:i:s', $m[1] / 1000) : null,
                    'Approver' => $entry['Approver'] ?? null,
                    'ApproverFullName' => $entry['ApproverFullName'] ?? null,
                    'Created' => isset($entry['Created']) && preg_match(
                        '/\/Date\((\d+)\)\//',
                        $entry['Created'],
                        $m
                    ) ? date('Y-m-d H:i:s', $m[1] / 1000) : null,
                    'Creator' => $entry['Creator'] ?? null,
                    'CreatorFullName' => $entry['CreatorFullName'] ?? null,
                    'Currency' => $entry['Currency'] ?? null,
                    'CustomField' => $entry['CustomField'] ?? null,
                    'DeliverTo' => $entry['DeliverTo'] ?? null,
                    'DeliverToContactPerson' => $entry['DeliverToContactPerson'] ?? null,
                    'DeliverToContactPersonFullName' => $entry['DeliverToContactPersonFullName'] ?? null,
                    'DeliverToName' => $entry['DeliverToName'] ?? null,
                    'DeliveryAddress' => $entry['DeliveryAddress'] ?? null,
                    'DeliveryDate' => isset($entry['DeliveryDate']) && preg_match(
                        '/\/Date\((\d+)\)\//',
                        $entry['DeliveryDate'],
                        $m
                    ) ? date('Y-m-d', $m[1] / 1000) : null,
                    'DeliveryStatus' => $entry['DeliveryStatus'] ?? null,
                    'DeliveryStatusDescription' => $entry['DeliveryStatusDescription'] ?? null,
                    'Description' => $entry['Description'] ?? null,
                    'Discount' => $entry['Discount'] ?? null,
                    'Division' => $entry['Division'] ?? null,
                    'Document' => $entry['Document'] ?? null,
                    'DocumentNumber' => $entry['DocumentNumber'] ?? null,
                    'DocumentSubject' => $entry['DocumentSubject'] ?? null,
                    'IncotermAddress' => $entry['IncotermAddress'] ?? null,
                    'IncotermCode' => $entry['IncotermCode'] ?? null,
                    'IncotermVersion' => $entry['IncotermVersion'] ?? null,
                    'InvoiceStatus' => $entry['InvoiceStatus'] ?? null,
                    'InvoiceStatusDescription' => $entry['InvoiceStatusDescription'] ?? null,
                    'InvoiceTo' => $entry['InvoiceTo'] ?? null,
                    'InvoiceToContactPerson' => $entry['InvoiceToContactPerson'] ?? null,
                    'InvoiceToContactPersonFullName' => $entry['InvoiceToContactPersonFullName'] ?? null,
                    'InvoiceToName' => $entry['InvoiceToName'] ?? null,
                    'Modified' => isset($entry['Modified']) && preg_match(
                        '/\/Date\((\d+)\)\//',
                        $entry['Modified'],
                        $m
                    ) ? date('Y-m-d H:i:s', $m[1] / 1000) : null,
                    'Modifier' => $entry['Modifier'] ?? null,
                    'ModifierFullName' => $entry['ModifierFullName'] ?? null,
                    'OrderDate' => isset($entry['OrderDate']) && preg_match(
                        '/\/Date\((\d+)\)\//',
                        $entry['OrderDate'],
                        $m
                    ) ? date('Y-m-d', $m[1] / 1000) : null,
                    'OrderedBy' => $entry['OrderedBy'] ?? null,
                    'OrderedByContactPerson' => $entry['OrderedByContactPerson'] ?? null,
                    'OrderedByContactPersonFullName' => $entry['OrderedByContactPersonFullName'] ?? null,
                    'OrderedByName' => $entry['OrderedByName'] ?? null,
                    'OrderNumber' => $entry['OrderNumber'] ?? null,
                    'PaymentCondition' => $entry['PaymentCondition'] ?? null,
                    'PaymentConditionDescription' => $entry['PaymentConditionDescription'] ?? null,
                    'PaymentReference' => $entry['PaymentReference'] ?? null,
                    'Remarks' => $entry['Remarks'] ?? null,
                    'SalesChannel' => $entry['SalesChannel'] ?? null,
                    'SalesChannelCode' => $entry['SalesChannelCode'] ?? null,
                    'SalesChannelDescription' => $entry['SalesChannelDescription'] ?? null,
                    'Salesperson' => $entry['Salesperson'] ?? null,
                    'SalespersonFullName' => $entry['SalespersonFullName'] ?? null,
                    'SelectionCode' => $entry['SelectionCode'] ?? null,
                    'SelectionCodeCode' => $entry['SelectionCodeCode'] ?? null,
                    'SelectionCodeDescription' => $entry['SelectionCodeDescription'] ?? null,
                    'ShippingMethod' => $entry['ShippingMethod'] ?? null,
                    'ShippingMethodDescription' => $entry['ShippingMethodDescription'] ?? null,
                    'Status' => $entry['Status'] ?? null,
                    'StatusDescription' => $entry['StatusDescription'] ?? null,
                    'TaxSchedule' => $entry['TaxSchedule'] ?? null,
                    'TaxScheduleCode' => $entry['TaxScheduleCode'] ?? null,
                    'TaxScheduleDescription' => $entry['TaxScheduleDescription'] ?? null,
                    'WarehouseCode' => $entry['WarehouseCode'] ?? null,
                    'WarehouseDescription' => $entry['WarehouseDescription'] ?? null,
                    'WarehouseID' => $entry['WarehouseID'] ?? null,
                    'YourRef' => $entry['YourRef'] ?? null,
                ];

                $exactID = $columns['OrderID'];

                if (isset($existingIDs[$exactID])) {
                    // Bijwerken als het exactID al bestaat
                    $updateCols = [];
                    foreach ($columns as $key => $value) {
                        if ($key !== 'OrderID') {
                            $escapedValue = "'" . $conn->real_escape_string((string) ($value ?? '')) . "'";
                            $updateCols[] = "$key = $escapedValue";
                        }
                    }
                    $updateValues = implode(", ", $updateCols);
                    $sql = "UPDATE SalesOrders SET $updateValues WHERE OrderID = '$exactID'";
                    $conn->query($sql);
                    // error_log("Update: " . $exactID);
                } else {
                    // Als exactID niet bestaat, dan invoegen (insert)
                    $dbCols = implode(",", array_keys($columns));
                    $escapedValues = array_map(fn($v) => "'" . $conn->real_escape_string((string) ($v ?? '')) . "'",
                        array_values($columns));
                    $values = implode(",", $escapedValues);
                    $sql = "INSERT INTO SalesOrders ($dbCols) VALUES ($values)";
                    $conn->query($sql);
                    // error_log("Insert: " . $exactID);
                }
            }

            $countArray++;

            // error_log($countArray);

            // Controleer op de volgende pagina met "__next" en pas de URL aan voor de volgende iteratie
            $url = $result["d"]["__next"] ?? null;

            // Pauze tussen requests om rate limiting te vermijden

            unset($result);
            sleep(1);
        } while (!empty($url));
    }


    function itemWarehousesData()
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // divisionCode = $divisionCodeDeltafil;
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/inventory/ItemWarehouses";
        $selectAttributes = 'DefaultStorageLocationCode, ItemCode, ItemDescription, WarehouseCode';
        $date = $_GET['datum'];
        $filterQuery = "";
        $sortQuery = "";
        $filePath = "";

        if (empty($date)) {
            $_SESSION['message'] = 'Geen datum geselecteerd';
        }

        $day = date("l", strtotime($date));


        return showOrderresults($filePath, $divisionCode, $endPoint, $selectAttributes, $filterQuery, $sortQuery);
    }


    function tepickenOrders() : array
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $divisionCode = $divisionCodeDeltafil;
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/bulk/SalesOrder/SalesOrders";
        $selectAttributes = 'OrderedBy,Status,DeliveryStatus,OrderNumber,DeliveryDate,Description,ShippingMethodDescription,OrderedByName,SalesOrderLines,ShippingMethod';
        $date = $_GET['datum'];
        $filterQuery = "(Status eq 12 and DeliveryStatus eq 12) and DeliveryDate eq datetime'$date'";
        $sortQuery = "ShippingMethodDescription,OrderedByName";
        $filePath = "";

        if (empty($date)) {
            $_SESSION['message'] = 'Geen datum geselecteerd';
        }

        $day = date("l", strtotime($date));


        return showOrderresults($filePath, $divisionCode, $endPoint, $selectAttributes, $filterQuery, $sortQuery);
    }

    function tepickenSalesOrderLines() : array
    {
        //        include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $divisionCode = $divisionCodeDeltafil;
        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/bulk/SalesOrder/SalesOrderLines";
        $selectAttributes = 'ID,ItemCode,DeliveryDate,OrderNumber,Description,Quantity,Notes,UnitCode,ItemDescription';
        $date = $_GET['datum'];
        $filterQuery = "DeliveryDate eq datetime'$date'";
        $sortQuery = "DeliveryDate";
        $filePath = "";

        return showOrderresults($filePath, $divisionCode, $endPoint, $selectAttributes, $filterQuery, $sortQuery);
    }

    function beschikbareDeliveryMethods() : array
    {
        // include $_SERVER['DOCUMENT_ROOT'] . "/config.php";
        // $divisionCode = $divisionCodeDeltafil;

        $divisionCode = $_ENV['DIVISIONCODE_VERKOOP'];
        $endPoint = "/sales/ShippingMethods";
        $selectAttributes = 'Code,Description,Active';
        $filterQuery = null;
        $sortQuery = "Code";
        $filePath = "";

        return showOrderresults($filePath, $divisionCode, $endPoint, $selectAttributes, $filterQuery, $sortQuery);
    }

    function showOrderresults($dumpPath, $divisionCode, $endPoint, $selectAttributes, $filterQuery, $sortQuery)
    {
        $maxRetries = 6; // Maximale aantal pogingen
        $retryCount = 0; // Huidige poging
        $finalResult = null;

        while ($retryCount < $maxRetries && $finalResult === null) {
            $retryCount++;
            $endPoint = $endPoint;
            $divisionCode = $divisionCode;

            $queryParams = [
                '$select' => $selectAttributes,
                '$filter' => $filterQuery,
                '$orderby' => $sortQuery,
            ];

            $url = constructUrl($divisionCode, $endPoint, $queryParams);
            //        var_dump($url);exit();

            $method = 'GET';
            $headers = setAuthHeaders();

            $result = requestHandler($url, $method, $headers, []);
            $skiptokenUrl = $result["d"]["__next"] ?? null;

            // Als ["d"]["results"] bestaat, voeg dit toe aan $finalResult, anders gebruik het volledige $result
            $finalResult = $result["d"]["results"] ?? $result;

            // Herhaal zolang $skiptokenUrl bestaat en niet leeg is
            while (!empty($skiptokenUrl)) {
                $headers = setAuthHeaders();
                $result = requestHandler($skiptokenUrl, $method, $headers, []);

                // Controleer of "d" en "results" aanwezig zijn in het resultaat
                if (isset($result["d"]["results"])) {
                    $finalResult = array_merge($finalResult, $result["d"]["results"]);
                } else {
                    // Voeg het volledige resultaat toe aan $finalResult als ["d"]["results"] niet bestaat
                    $finalResult = array_merge($finalResult, $result);
                }

                // Controleer of de __next key bestaat voor de volgende iteratie
                if (isset($result["d"]["__next"])) {
                    $skiptokenUrl = $result["d"]["__next"];
                } else {
                    break;
                }
            }

            if ($finalResult !== null) {
                break; // Stop de retry-loop als het resultaat succesvol is
            }

            // Optioneel: wacht even voordat je het opnieuw probeert
            sleep(2);
        }

        // Controleer of het resultaat nog steeds null is na retries
        if ($finalResult === null) {
            // throw new Exception("Failed to fetch results after $maxRetries retries.");
        }

        // Retourneer het resultaat
        return $finalResult;
    }

    function getOrderStatusName($status) : string
    {
        switch($status) {
            case 12:
                return "Open";
            case 20:
                return "Partial";
            case 21:
                return "Complete";
            case 45:
                return "Cancelled";
            default:
                return "Unknown";
        }
    }

    function routevolgordeOpslaan($content)
    {
        $dagen = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];

        global $conn;

        foreach ($content['order'] as $order) {
            foreach ($dagen as $dag) {
                if ($dag === $order['dag']) {
                    $kolom = $dag . '_sorting'; // Dynamische kolomnaam
                    $sql = "UPDATE klanten SET $kolom = :sorting WHERE id = :routeId"; // Directe string, geen placeholder voor kolom

                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':sorting', $order['sorting'], PDO::PARAM_INT);
                    $stmt->bindValue(':routeId', $order['id'], PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }

        header('Content-Type: application/json');
    }

    function routevolgordeOpslaanTest($content)
    {
        $dagen = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];

        global $conn;

        foreach ($content['order'] as $order) {
            foreach ($dagen as $dag) {
                if ($dag === $order['dag']) {
                    $kolom = $dag . '_sorting'; // Dynamische kolomnaam
                    $sql = "UPDATE klanten_Test SET $kolom = :sorting WHERE id = :routeId"; // Directe string, geen placeholder voor kolom

                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':sorting', $order['sorting'], PDO::PARAM_INT);
                    $stmt->bindValue(':routeId', $order['id'], PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }

        header('Content-Type: application/json');
    }
