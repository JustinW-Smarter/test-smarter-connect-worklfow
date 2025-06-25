<?php
    require_once '/home/hostsmar/connect.smarter.nl/requests.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    // Maak een verbinding met de database
    $conn = mysqli_connect(
        $_ENV['DATABASE_HOST'],
        $_ENV['DATABASE_USER'],
        $_ENV['DATABASE_PASS'],
        $_ENV['DATABASE_NAME']
    );

    $sqlInstellingen = "SELECT * FROM instellingen LIMIT 1";
    $resultInstellingen = $conn->query($sqlInstellingen);

    if ($resultInstellingen->num_rows == 0) {
        $maandag = false;
        $dinsdag = false;
        $woensdag = false;
        $donderdag = false;
        $vrijdag = false;
        $zaterdag = false;
        $zondag = false;
    } else {
        $firstRow = $resultInstellingen->fetch_assoc();
        $maandag = (bool) $firstRow['maandag'];
        $dinsdag = (bool) $firstRow['dinsdag'];
        $woensdag = (bool) $firstRow['woensdag'];
        $donderdag = (bool) $firstRow['donderdag'];
        $vrijdag = (bool) $firstRow['vrijdag'];
        $zaterdag = (bool) $firstRow['zaterdag'];
        $zondag = (bool) $firstRow['zondag'];
    }
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
    jQuery(document).ready(function () {
        var dagen = {
            maandag: <?php echo $maandag ? 'true' : 'false'; ?>,
            dinsdag: <?php echo $dinsdag ? 'true' : 'false'; ?>,
            woensdag: <?php echo $woensdag ? 'true' : 'false'; ?>,
            donderdag: <?php echo $donderdag ? 'true' : 'false'; ?>,
            vrijdag: <?php echo $vrijdag ? 'true' : 'false'; ?>,
            zaterdag: <?php echo $zaterdag ? 'true' : 'false'; ?>,
            zondag: <?php echo $zondag ? 'true' : 'false'; ?>
        };

        jQuery.each(dagen, function (dag, waarde) {
            if (!waarde) {
                jQuery('.' + dag).hide();
            }
        });
    });
</script>
<!-- HTML + Styling -->
<!-- <link href="/assets/css/design_form.css" rel="stylesheet"> -->
<!-- <style>
    h2 {
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background: #d20911 !important;
        color: white;
        text-transform: uppercase;
    }

    tbody tr:hover {
        background: #f1f1f1;
    }
</style> -->

<style>
    /* body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8f8f8;
        margin: 20px;
    } */

    .table-container {
        background: white;
        border-radius: 5px;
        border: 1px solid #ddd;
        width: 100%;
    }

    .table-container table {
        width: 100%;
        border-collapse: separate;
        /* table-layout: fixed; */
        border-spacing: 0;
        font-size: 14px;
    }

    .table-container thead th {
        text-align: left;
        padding: 10px 10px;
        border-bottom: 1px solid #ccc;
        color: #464c53 !important;
    }

    .table-container tbody td {
        padding: 10px 10px;
        vertical-align: middle !important;
        border-bottom: 1px solid #eee;
        color: #464c53;
        display: table-cell;
    }

    .table-container tbody td div {
        display: flex;
        width: 100%;
    }

    .table-container tbody td div input {
        width: 30%;
        margin-right: 3%;
    }

    .table-container tbody td div select {
        width: 100%;
        margin-left: 0%;
        padding: 8px 6px;
        font-size: 12px;
        font-family: sans-serif;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #f9f9f9;
        color: #333;
        transition: all 0.3s ease;
    }

    .factuurnummer-link {
        color: #449ce0;
        text-decoration: none;
    }

    .subtext {
        display: block;
        color: #999;
        font-size: 12px;
        margin-top: 2px;
    }

    .status-column-text {
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        display: flex;
        flex-direction: row;
        align-items: center;
        flex-wrap: nowrap;
    }

    .status {
        padding: 0 !important;
    }

    .status p {
        margin-right: 5px;
    }

    .status-warning {
        color: #f7931e !important;
    }

    .status-danger {
        color: #d9534f !important;
    }

    .status-success {
        color: #28a745 !important;
    }

    .dropdown-acties {
        background: #fff;
        background-image: url(img/select_arrowdown.d1b2c2a4.png), linear-gradient(-180deg, transparent, rgba(0, 0, 0, .1));
        background-position: right 10px center, 50%;
        background-repeat: no-repeat;
        background-size: 6px 12px, auto;
        border: 1px solid rgba(0, 0, 0, .1);
        border-radius: 4px;
        color: #464c53;
        cursor: pointer;
        font-size: 14px;
        height: 34px;
        line-height: 16px;
        padding: 0px 20px 0px 10px;
        width: 200px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }

    .checkbox-style {
        margin: 0 10px 0 0;
        appearance: none;
        width: 16px;
        height: 16px;
        border: 1px solid #ccc;
        background-image: linear-gradient(-180deg, transparent, rgba(0, 0, 0, .1));
        border-radius: 3px;
        cursor: pointer;
        position: relative;
    }

    .checkbox-style:checked {
        background-color: #449ce0;
        border-radius: 3px;
    }

    .checkbox-style:checked::after {
        content: "✔";
        color: white;
        font-size: 10px;
        position: absolute;
        top: 0;
        left: 3px;
    }

    .row-highlight:hover {
        background-color: #f5f5f5;
    }

    .actions-row td {
        padding: 12px 10px;
    }

    .arrow-corner {
        border-bottom: 2px solid #e9eaec;
        border-left: 2px solid #e9eaec;
        height: 14px;
        width: 14px;
        margin-left: 6px;
    }

    .amount {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-variant-numeric: tabular-nums;
    }

    .amount .currency {
        margin-right: 4px;
        color: #555;
    }

    .amount .value {
        text-align: right;
        font-weight: 500;
    }

    select.editable-select.leeg {
        opacity: 0.4;
    }

    select.editable-select {
        cursor: pointer;
    }

    select#leveringswijze,
    select#segment {
        padding: 10px 14px;
        margin: 0 14px 0 0;
        font-size: 16px;
        font-family: sans-serif;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #f9f9f9;
        color: #333;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .filter-container {
        margin-bottom: 15px;
    }

    .no-results {
        padding: 20px 10px 10px 10px;
    }

    .no-results p {
        padding: 0px;
        margin: 0px;
    }
</style>


<div class="mid-right">
    <div class="wrapper-all">
        <h2>Klanten</h2>
        <?php
            // Fetch alle leveringswijzes
            $leveringswijzes = beschikbareDeliveryMethods();

            $json_file_classifications = '/home/hostsmar/connect.smarter.nl/assets/json/accountClassifications.json';

            $json_data_classifications = file_get_contents($json_file_classifications);
            $items_classifications = json_decode($json_data_classifications, true);
        ?>
        <div class="filter-container">
            <form action="" method="get">
                <?php
                    $leveringswijzesCodes = array_map('trim', array_column($leveringswijzes, 'Code'));
                ?>
                <label for="leveringswijze"></label>
                <select name="leveringswijze" id="leveringswijze" onchange="this.form.submit()">
                    <option value="">Geen leveringswijze</option>
                    <?php
                        foreach ($leveringswijzes as $leveringswijze): ?>
                            <?php
                            $uitsluiten = ['Eieren', 'Enzo', 'Jacques', 'Jorg', 'Marc'];

                            $overslaan = false;
                            foreach ($uitsluiten as $woord) {
                                if (stripos(trim($leveringswijze['Code']), $woord) !== false) {
                                    $overslaan = true;
                                    break;
                                }
                            }
                            if ($overslaan) {
                                continue;
                            }

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
                <label for="segment"></label>
                <select name="segment" id="segment" onchange="this.form.submit()">
                    <option value="">Geen segment</option>
                    <?php
                        foreach ($items_classifications as $segment): ?>
                            <?php
                            if (htmlspecialchars($segment['AccountClassificationNameDescription'] === 'Segment')) : ?>
                                <?php
                                $segment = htmlspecialchars($segment['Description']);

                                if (isset($_GET['segment'])) {
                                    $selected = $_GET['segment'] === $segment ? 'selected' : '';
                                } elseif ($segment === 'Retail') {
                                    $selected = 'selected';
                                } else {
                                    $selected = '';
                                }
                                ?>
                                <option value="<?= trim($segment) ?>" <?= $selected ?? '' ?>><?= $segment ?></option>
                            <?php
                            else: ?>
                            <?php
                            endif; ?>
                        <?php
                        endforeach; ?>
                </select>

            </form>
        </div>
        <div class="table-container">

            <?php

                // Fetch alle leveringswijzes
                //$leveringswijzes = beschikbareDeliveryMethods();

                // **Verwerk AJAX-aanvragen direct op deze pagina**
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'], $_POST['column'], $_POST['columnDag'], $_POST['value'])) {
                    $id = $_POST['id']; // Unieke klant ID
                    $column = $_POST['column']; // Welke dag (maandag t/m vrijdag)
                    $columnDag = $_POST['columnDag']; // Welke dag (maandag t/m vrijdag)
                    $value = $_POST['value']; // Nieuwe waarde

                    // if (isset($_POST['action']) && $_POST['action'] === 'resetSortering') {
                    //     resetSortering($conn, $id, $column, $value);
                    // }

                    if (isset($_POST['action']) && $_POST['action'] === 'setActive') {
                        //setActive($conn, $id, $column, $value);
                        $active = 1;
                    } elseif (isset($_POST['action']) && $_POST['action'] === 'setInActive') {
                        //setActive($conn, $id, $column, $value);
                        $active = 0;
                    } else {
                        $active = 0;
                    }

                    // Beveiligde query uitvoeren (prepared statement)
                    $sql = "UPDATE klanten SET $column=?, $columnDag=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sii", $value, $active, $id);

                    if ($stmt->execute()) {
                        echo "Succesvol bijgewerkt: $column voor ID $id";
                    } else {
                        echo "Fout bij bijwerken: " . $stmt->error;
                    }

                    $stmt->close();
                    $conn->close();
                    exit(); // Stop hier om te voorkomen dat de hele pagina wordt herladen
                }

                function resetSortering($conn, $id, $column, $value)
                {
                    $id = (int) $id;
                    $value = (int) $value;
                    $column = (string) $column;

                    $sorting = $column . '_sorting';
                    $sql = "UPDATE klanten SET $sorting = null WHERE $column = 1 AND id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);

                    if ($stmt->execute()) {
                        //echo "SQL Query: " . $sql . ': ' . $column . ' & ' . $id . ', value -> ' . $value . ' , ' . $sorting . "\n";
                    } else {
                        //echo "Fout bij bijwerken: " . $stmt->error;
                    }

                    $stmt->close();
                    $conn->close();
                    exit(); // Stop hier om te voorkomen dat de hele pagina wordt herladen
                }

                function setActive($conn, $id, $column, $value)
                {
                    $id = (int) $id;
                    $leveringswijze = (string) $value;
                    $input = "1";
                    $dag = (string) str_replace('_leveringswijze', '', $column);

                    $sql = "UPDATE klanten SET $dag = 1 WHERE $column = ? AND id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $leveringswijze, $id);

                    if ($stmt->execute()) {
                        //echo "SQL Query: " . $sql . ': ' . $id . ' & ' . $leveringswijze . ', value -> ' . $input . ' , ' . $dag_leveringswijze . "\n";
                    } else {
                        //echo "Fout bij bijwerken: " . $stmt->error;
                    }
                    $stmt->close();
                    $conn->close();
                    exit(); // Stop hier om te voorkomen dat de hele pagina wordt herladen
                }

                if (isset($_GET['leveringswijze'])) {
                    // Waarde ophalen en saniteren
                    $leveringswijzeFilter = filter_input(INPUT_GET, 'leveringswijze', FILTER_SANITIZE_STRING);
                } else {
                    $leveringswijzeFilter = null;
                }

                if (isset($_GET['segment']) && $_GET['segment'] !== "") {
                    // Waarde ophalen en saniteren
                    $segmentFilterText = filter_input(INPUT_GET, 'segment', FILTER_SANITIZE_STRING);
                    $segmentFilter = true;
                } else {
                    $segmentFilter = false;
                }

                if ($leveringswijzeFilter && $segmentFilter) {
                    if ($segmentFilterText === "") {
                        $sql = "SELECT *
                    FROM klanten
                    WHERE ShippingMethod IS NOT NULL
                    AND EndDate > NOW()
                    AND (
                        TRIM(maandag_leveringswijze) = ? OR
                        TRIM(dinsdag_leveringswijze) = ? OR
                        TRIM(woensdag_leveringswijze) = ? OR
                        TRIM(donderdag_leveringswijze) = ? OR
                        TRIM(vrijdag_leveringswijze) = ? OR
                        TRIM(zaterdag_leveringswijze) = ? OR
                        TRIM(zondag_leveringswijze) = ?
                    ) ORDER BY CAST(Code AS UNSIGNED) ASC";

                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param(
                            "sssssss",
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter
                        );
                    } else {
                        $sql = "SELECT *
                    FROM klanten
                    WHERE ShippingMethod IS NOT NULL
                    AND EndDate > NOW()
                    AND Segment = ?
                    AND (
                        TRIM(maandag_leveringswijze) = ? OR
                        TRIM(dinsdag_leveringswijze) = ? OR
                        TRIM(woensdag_leveringswijze) = ? OR
                        TRIM(donderdag_leveringswijze) = ? OR
                        TRIM(vrijdag_leveringswijze) = ? OR
                        TRIM(zaterdag_leveringswijze) = ? OR
                        TRIM(zondag_leveringswijze) = ?
                    ) ORDER BY CAST(Code AS UNSIGNED) ASC";

                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param(
                            "ssssssss",
                            $segmentFilterText,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter,
                            $leveringswijzeFilter
                        );
                    }
                } elseif ($leveringswijzeFilter) {
                    $sql = "SELECT *
                FROM klanten
                WHERE ShippingMethod IS NOT NULL
                  AND Segment = 'Retail'
                  AND EndDate > NOW()
                  AND (
                    TRIM(maandag_leveringswijze) = ? OR
                    TRIM(dinsdag_leveringswijze) = ? OR
                    TRIM(woensdag_leveringswijze) = ? OR
                    TRIM(donderdag_leveringswijze) = ? OR
                    TRIM(vrijdag_leveringswijze) = ? OR
                    TRIM(zaterdag_leveringswijze) = ? OR
                    TRIM(zondag_leveringswijze) = ?
                  )
                ORDER BY CAST(Code AS UNSIGNED) ASC";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "sssssss",
                        $leveringswijzeFilter,
                        $leveringswijzeFilter,
                        $leveringswijzeFilter,
                        $leveringswijzeFilter,
                        $leveringswijzeFilter,
                        $leveringswijzeFilter,
                        $leveringswijzeFilter
                    );
                } elseif ($segmentFilter) {
                    $sql = "SELECT *
                    FROM klanten
                    WHERE ShippingMethod IS NOT NULL
                    AND Segment = ?
                    AND EndDate > NOW()
                    ORDER BY CAST(Code AS UNSIGNED) ASC";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $segmentFilterText);
                } else {
                    if (isset($_GET['segment'])) {
                        $sql = "SELECT *
                    FROM klanten
                    WHERE ShippingMethod IS NOT NULL
                    AND EndDate > NOW()
                    ORDER BY CAST(Code AS UNSIGNED) ASC";

                        $stmt = $conn->prepare($sql);
                    } else {
                        $sql = "SELECT *
                        FROM klanten
                        WHERE ShippingMethod IS NOT NULL
                        AND Segment = 'Retail'
                        AND EndDate > NOW()
                        ORDER BY CAST(Code AS UNSIGNED) ASC";

                        $stmt = $conn->prepare($sql);
                    }
                }

                // Query uitvoeren
                $stmt->execute();
                $result = $stmt->get_result()

                // Query uitvoeren
                // $result = $conn->query($sql);

            ?>
            <?php

                // **HTML Weergave**
                if ($result->num_rows > 0) {
                    echo "<table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th class='maandag'>Maandag</th>
                        <th class='dinsdag'>Dinsdag</th>
                        <th class='woensdag'>Woensdag</th>
                        <th class='donderdag'>Donderdag</th>
                        <th class='vrijdag'>Vrijdag</th>
                        <th class='zaterdag'>Zaterdag</th>
                        <th class='zondag'>Zondag</th>
                    </tr>
                </thead><tbody>";
                    // Gegevens afdrukken
                    while ($row = $result->fetch_assoc()) {
                        $id = $row["id"];
                        $Code = $row["Code"];
                        $Name = $row["Name"];
                        $maandag = $row["maandag"];
                        $dinsdag = $row["dinsdag"];
                        $woensdag = $row["woensdag"];
                        $donderdag = $row["donderdag"];
                        $vrijdag = $row["vrijdag"];
                        $zaterdag = $row["zaterdag"];
                        $zondag = $row["zondag"];

                        $leveringswijze_maandag = $row["maandag_leveringswijze"];
                        $leveringswijze_dinsdag = $row["dinsdag_leveringswijze"];
                        $leveringswijze_woensdag = $row["woensdag_leveringswijze"];
                        $leveringswijze_donderdag = $row["donderdag_leveringswijze"];
                        $leveringswijze_vrijdag = $row["vrijdag_leveringswijze"];
                        $leveringswijze_zaterdag = $row["zaterdag_leveringswijze"];
                        $leveringswijze_zondag = $row["zondag_leveringswijze"];

                        $uitsluiten = ['Eieren', 'Enzo', 'Jacques', 'Jorg', 'Marc'];

                        echo "
                <tr id='row_$id'>
                    <td>" . $Code . "</td>
                    <td>" . $Name . "</td>
                    <td class='maandag'>
                        <div>
                            <input style='display: none;' type='text' class='editable' data-id='$id' data-columnDag='maandag' value='" . $maandag . "'>
                            <select class='editable-select " . ($maandag == 0 ? 'leeg' : trim(
                                $leveringswijze_maandag
                            )) . "' data-id='$id' data-column='maandag_leveringswijze' name='leveringswijze'>";
                        echo "<option value=''>Geen leveringswijze</option>";
                        foreach ($leveringswijzes as $leveringswijze) {
                            $overslaan = false;
                            foreach ($uitsluiten as $woord) {
                                if (stripos(trim($leveringswijze['Code']), $woord) !== false) {
                                    $overslaan = true;
                                    break;
                                }
                            }
                            if ($overslaan) {
                                continue;
                            }

                            if ($leveringswijze_maandag === $leveringswijze['Code']) {
                                echo "<option value='" . $leveringswijze['Code'] . "' selected>" . $leveringswijze['Code'] . "</option>";
                            } else {
                                echo "<option value='" . $leveringswijze['Code'] . "'>" . htmlspecialchars(
                                        $leveringswijze['Code']
                                    ) . "</option>";
                            }
                        }
                        echo "</select>
                        </div>
                    </td>
                    <td class='dinsdag'>
                        <div>
                            <input style='display: none;' type='text' class='editable' data-id='$id' data-columnDag='dinsdag' value='" . $dinsdag . "'>
                            <select class='editable-select " . ($dinsdag == 0 ? 'leeg' : trim(
                                $leveringswijze_dinsdag
                            )) . "' data-id='$id' data-column='dinsdag_leveringswijze' name='leveringswijze'>";
                        echo "<option value=''>Geen leveringswijze</option>";
                        foreach ($leveringswijzes as $leveringswijze) {
                            $overslaan = false;
                            foreach ($uitsluiten as $woord) {
                                if (stripos(trim($leveringswijze['Code']), $woord) !== false) {
                                    $overslaan = true;
                                    break;
                                }
                            }
                            if ($overslaan) {
                                continue;
                            }

                            if ($leveringswijze_dinsdag === $leveringswijze['Code']) {
                                echo "<option value='" . $leveringswijze['Code'] . "' selected>" . $leveringswijze['Code'] . "</option>";
                            } else {
                                echo "<option value='" . $leveringswijze['Code'] . "'>" . htmlspecialchars(
                                        $leveringswijze['Code']
                                    ) . "</option>";
                            }
                        }
                        echo "</select>
                        </div>
                    </td>
                    <td class='woensdag'>
                        <div>
                            <input style='display: none;' type='text' class='editable' data-id='$id' data-columnDag='woensdag' value='" . $woensdag . "'>
                            <select class='editable-select " . ($woensdag == 0 ? 'leeg' : trim(
                                $leveringswijze_woensdag
                            )) . "' data-id='$id' data-column='woensdag_leveringswijze' name='leveringswijze'>";
                        echo "<option value=''>Geen leveringswijze</option>";
                        foreach ($leveringswijzes as $leveringswijze) {
                            $overslaan = false;
                            foreach ($uitsluiten as $woord) {
                                if (stripos(trim($leveringswijze['Code']), $woord) !== false) {
                                    $overslaan = true;
                                    break;
                                }
                            }
                            if ($overslaan) {
                                continue;
                            }

                            if ($leveringswijze_woensdag === $leveringswijze['Code']) {
                                echo "<option value='" . $leveringswijze['Code'] . "' selected>" . $leveringswijze['Code'] . "</option>";
                            } else {
                                echo "<option value='" . $leveringswijze['Code'] . "'>" . htmlspecialchars(
                                        $leveringswijze['Code']
                                    ) . "</option>";
                            }
                        }
                        echo "</select>
                        </div>
                    </td>
                    <td class='donderdag'>
                        <div>
                            <input style='display: none;' type='text' class='editable' data-id='$id' data-columnDag='donderdag' value='" . $donderdag . "'>
                            <select class='editable-select " . ($donderdag == 0 ? 'leeg' : trim(
                                $leveringswijze_donderdag
                            )) . "' data-id='$id' data-column='donderdag_leveringswijze' name='leveringswijze'>";
                        echo "<option value=''>Geen leveringswijze</option>";
                        foreach ($leveringswijzes as $leveringswijze) {
                            $overslaan = false;
                            foreach ($uitsluiten as $woord) {
                                if (stripos(trim($leveringswijze['Code']), $woord) !== false) {
                                    $overslaan = true;
                                    break;
                                }
                            }
                            if ($overslaan) {
                                continue;
                            }

                            if ($leveringswijze_donderdag === $leveringswijze['Code']) {
                                echo "<option value='" . $leveringswijze['Code'] . "' selected>" . $leveringswijze['Code'] . "</option>";
                            } else {
                                echo "<option value='" . $leveringswijze['Code'] . "'>" . htmlspecialchars(
                                        $leveringswijze['Code']
                                    ) . "</option>";
                            }
                        }
                        echo "</select>
                        </div>
                    </td>
                    <td class='vrijdag'>
                        <div>
                            <input style='display: none;' type='text' class='editable' data-id='$id' data-columnDag='vrijdag' value='" . $vrijdag . "'>
                            <select class='editable-select " . ($vrijdag == 0 ? 'leeg' : trim(
                                $leveringswijze_vrijdag
                            )) . "' data-id='$id' data-column='vrijdag_leveringswijze' name='leveringswijze'>";
                        echo "<option value=''>Geen leveringswijze</option>";
                        foreach ($leveringswijzes as $leveringswijze) {
                            $overslaan = false;
                            foreach ($uitsluiten as $woord) {
                                if (stripos(trim($leveringswijze['Code']), $woord) !== false) {
                                    $overslaan = true;
                                    break;
                                }
                            }
                            if ($overslaan) {
                                continue;
                            }

                            if ($leveringswijze_vrijdag === $leveringswijze['Code']) {
                                echo "<option value='" . $leveringswijze['Code'] . "' selected>" . $leveringswijze['Code'] . "</option>";
                            } else {
                                echo "<option value='" . $leveringswijze['Code'] . "'>" . htmlspecialchars(
                                        $leveringswijze['Code']
                                    ) . "</option>";
                            }
                        }
                        echo "</select>
                        </div>
                    </td>
                    <td class='zaterdag'>
                        <div>
                            <input style='display: none;' type='text' class='editable' data-id='$id' data-columnDag='zaterdag' value='" . $zaterdag . "'>
                            <select class='editable-select " . ($zaterdag == 0 ? 'leeg' : trim(
                                $leveringswijze_zaterdag
                            )) . "' data-id='$id' data-column='zaterdag_leveringswijze' name='leveringswijze'>";
                        echo "<option value=''>Geen leveringswijze</option>";
                        foreach ($leveringswijzes as $leveringswijze) {
                            $overslaan = false;
                            foreach ($uitsluiten as $woord) {
                                if (stripos(trim($leveringswijze['Code']), $woord) !== false) {
                                    $overslaan = true;
                                    break;
                                }
                            }
                            if ($overslaan) {
                                continue;
                            }

                            if ($leveringswijze_zaterdag === $leveringswijze['Code']) {
                                echo "<option value='" . $leveringswijze['Code'] . "' selected>" . $leveringswijze['Code'] . "</option>";
                            } else {
                                echo "<option value='" . $leveringswijze['Code'] . "'>" . htmlspecialchars(
                                        $leveringswijze['Code']
                                    ) . "</option>";
                            }
                        }
                        echo "</select>
                        </div>
                    </td>
                    <td class='zondag'>
                        <div>
                            <input style='display: none;' type='text' class='editable' data-id='$id' data-columnDag='zondag' value='" . $zondag . "'>
                            <select class='editable-select " . ($zondag == 0 ? 'leeg' : trim(
                                $leveringswijze_zondag
                            )) . "' data-id='$id' data-column='zondag_leveringswijze' name='leveringswijze'>";
                        echo "<option value=''>Geen leveringswijze</option>";
                        foreach ($leveringswijzes as $leveringswijze) {
                            $overslaan = false;
                            foreach ($uitsluiten as $woord) {
                                if (stripos(trim($leveringswijze['Code']), $woord) !== false) {
                                    $overslaan = true;
                                    break;
                                }
                            }
                            if ($overslaan) {
                                continue;
                            }

                            if ($leveringswijze_zondag === $leveringswijze['Code']) {
                                echo "<option value='" . $leveringswijze['Code'] . "' selected>" . $leveringswijze['Code'] . "</option>";
                            } else {
                                echo "<option value='" . $leveringswijze['Code'] . "'>" . htmlspecialchars(
                                        $leveringswijze['Code']
                                    ) . "</option>";
                            }
                        }
                        echo "</select>
                        </div>
                    </td>
                </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<div class='no-results'><p>Geen resultaten gevonden</div></p>";
                }

                // Verbinding sluiten
                $conn->close();
            ?>

            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
                $(document).ready(function () {
                    // $(".editable").on("change", function() {
                    //     var id = $(this).data("id"); // Haalt de rij-ID op
                    //     var column = $(this).data("column"); // Haalt de kolomnaam op (maandag t/m vrijdag)
                    //     var value = $(this).val(); // Haalt de nieuwe waarde op
                    //     var rowId = "#row_" + id; // ID van de rij

                    //     // Zet de rij oplichten (visuele feedback)
                    //     $(rowId).css("background-color", "#dfdfdf");

                    //     if (value === "0") {
                    //         $.ajax({
                    //             url: "", // Zelfde pagina (deze code verwerkt de update)
                    //             type: "POST",
                    //             data: {
                    //                 action: "resetSortering",
                    //                 id: id,
                    //                 column: column,
                    //                 value: value
                    //             },
                    //             success: function(response) {
                    //                 //console.log("Sortering gereset:", response);
                    //             },
                    //             error: function(xhr, status, error) {
                    //                 //console.error("Fout bij reset:", error);
                    //             }
                    //         });
                    //     }

                    //     $.ajax({
                    //         url: "", // Zelfde pagina (deze code verwerkt de update)
                    //         type: "POST",
                    //         data: {
                    //             id: id,
                    //             column: column,
                    //             value: value
                    //         },
                    //         success: function(response) {
                    //             //console.log(response);
                    //             // Verander de kleur tijdelijk, daarna terug naar normaal
                    //             setTimeout(function() {
                    //                 $(rowId).css("background-color", "");
                    //             }, 500);
                    //         }
                    //     });
                    // });
                    $(".editable-select").on("change", function () {
                        var id = $(this).data("id"); // Haalt de rij-ID op
                        var column = $(this).data("column"); // Haalt de kolomnaam op (maandag_leveringswijze, etc.)
                        var columnDag = $(this).prev().data("columndag"); // Haalt de dag van de kolom op (maandag, dinsdag, etc.)
                        var value = $(this).val(); // Haalt de geselecteerde waarde op

                        var rowId = "#row_" + id; // ID van de rij

                        // Zet de rij oplichten (visuele feedback)
                        $(rowId).css("background-color", "#dfdfdf");

                        if (value !== "") {
                            $.ajax({
                                url: "", // Zelfde pagina (deze code verwerkt de update)
                                type: "POST",
                                data: {
                                    action: "setActive",
                                    id: id,
                                    column: column,
                                    columnDag: columnDag,
                                    value: value
                                },
                                success: function (response) {
                                    //console.log("input is op actief gezet:", response);
                                },
                                error: function (xhr, status, error) {
                                    //console.error("Fout bij actief zetten:", error);
                                }
                            });
                        } else {
                            $.ajax({
                                url: "", // Zelfde pagina (deze code verwerkt de update)
                                type: "POST",
                                data: {
                                    action: "setInActive",
                                    id: id,
                                    column: column,
                                    columnDag: columnDag,
                                    value: value
                                },
                                success: function (response) {
                                    //console.log("input is op actief gezet:", response);
                                },
                                error: function (xhr, status, error) {
                                    //console.error("Fout bij actief zetten:", error);
                                }
                            });
                        }

                        // $.ajax({
                        //     url: "", // Zelfde pagina (deze code verwerkt de update)
                        //     type: "POST",
                        //     data: {
                        //         id: id,
                        //         column: column,
                        //         value: value
                        //     },
                        //     success: function(response) {
                        //         //console.log(response);
                        //         // Verander de kleur tijdelijk, daarna terug naar normaal
                        //         setTimeout(function() {
                        //             $(rowId).css("background-color", "");
                        //         }, 500);
                        //     }
                        // });
                    });
                });
            </script>


            <!-- <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naam</th>
                        <th>Sleep</th>
                    </tr>
                </thead>
                <tbody id="sortable">
                    <tr data-id="1">
                        <td>1</td>
                        <td>Johan</td>
                        <td class="drag-handle">☰</td>
                    </tr>
                    <tr data-id="2">
                        <td>2</td>
                        <td>Lisa</td>
                        <td class="drag-handle">☰</td>
                    </tr>
                    <tr data-id="3">
                        <td>3</td>
                        <td>Pieter</td>
                        <td class="drag-handle">☰</td>
                    </tr>
                </tbody>
            </table> -->
        </div>

        <script>
            new Sortable(document.getElementById("sortable"), {
                animation: 150,
                handle: ".drag-handle",
                onEnd: function (evt) {
                    let order = [];
                    document.querySelectorAll("#sortable tr").forEach((row, index) => {
                        let id = row.getAttribute("data-id");
                        order.push({
                            id: id,
                            position: index + 1
                        });
                    });

                    fetch("update_order.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify(order)
                    })
                        .then(response => response.text())
                        .then(data => console.log(data));
                }
            });
        </script>
    </div>
</div>
