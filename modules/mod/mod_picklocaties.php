<?php

// Database config
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();

function getPdoConnection() {
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
// var_dump($conn); // ✅ werkt nu correct

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action']) && $data['action'] === 'sendChangeToStorageDb') {
        sendChangeToStorageDb($data['data'], $conn);
        exit();
    }

    if (isset($data['action']) && $data['action'] === 'unhighlight') {
        try {
            $sql3 = 'UPDATE storage SET highlighted = 0';
            $stmt3 = $conn->prepare($sql3);
            $stmt3->execute();

            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            exit();
        } catch (PDOException $e) {
            exit();
        }
    }
    echo 'Geen geldige actie ontvangen.';
}

function sendChangeToStorageDb($fetchedChanges, $conn)
{
    $highlighted = (int) $fetchedChanges['checked'];
    $itemCode = ($fetchedChanges['itemCode']);

    try {
        $sql3 = 'UPDATE storage SET highlighted = :highlighted WHERE itemCode = :itemCode';
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bindValue(':highlighted', $highlighted, PDO::PARAM_INT);
        $stmt3->bindValue(':itemCode', $itemCode, PDO::PARAM_STR);
        $stmt3->execute();

        $sql4 = 'SELECT * FROM storage WHERE itemCode = :itemCode';
        $stmt4 = $conn->prepare($sql4);
        $stmt4->bindValue(':itemCode', $itemCode, PDO::PARAM_STR);
        $stmt4->execute();
        $result = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'message' => 'Update gelukt.', 'result' => $result]);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Databasefout: ' . $e->getMessage()]);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_picklocaties'])) {
    include_once($_SERVER['DOCUMENT_ROOT'] . "/requests.php");
    updatePicklocaties();

    echo "<script>window.location.href = window.location.href;</script>";
    exit;
}

$sqlCheck = "SELECT highlighted FROM storage WHERE highlighted = 1 AND (ItemEndDate IS NULL OR ItemEndDate >= NOW()) LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->execute();
$numRowsCheck = $stmtCheck->rowCount();

$sql = "SELECT * FROM storage WHERE (ItemEndDate IS NULL OR ItemEndDate >= NOW()) ORDER BY itemCode ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();

usort($result, function ($a, $b) {
    return strcmp(
        (string) ($a['DefaultStorageLocationCode'] ?? ''),
        (string) ($b['DefaultStorageLocationCode'] ?? '')
    );
});
?>

<style>

    .table-container {
        background: white;
        border-radius: 5px;
        border: 1px solid #ddd;
        width: 100%;
    }

    .table-container table {
        width: 100%;
        border-collapse: separate;
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

    .table-container th input,
    .table-container td input {
        cursor: pointer;
    }

    .picklocatiesRefresh-container {
        display: inline-block;
        width: 100%;
        text-align: right;
    }

    .picklocatiesRefresh-container i {
        font-size: 20px;
        color: #464c53 !important;
    }

    .picklocatiesRefresh-container button {
        margin-bottom: 15px;
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
        jQuery(".btn-refresh").click(function() {
            jQuery('.loader-wrapper').removeClass('inactive');
            jQuery('.loader-wrapper').addClass('active');
        });
    });
</script>
<div class="mid-right">
    <div class="wrapper-all">
        <h2>Picklocaties</h2>
        <div class="loader-wrapper inactive">
            <div class="loader-container">
                <div class="loader"></div>
            </div>
        </div>
        <style>
            .switch {
                position: relative;
                display: inline-block;
                width: 100px;
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
                margin: 6px 0px;
            }

            .switch.is-active input+.slider {
                background-color: #2196F3;
            }

            .switch.is-active input+.slider:before {
                transform: translateX(26px);
            }

            .picklocatiesRefresh-container button {
                margin-bottom: 0px !important;
            }
        </style>
        <script>
            jQuery(document).ready(function() {
                let rowsHidden = false;

                jQuery('#toggle_rows').on('click', function() {
                    if (!rowsHidden) {
                        // Verberg alle rijen waarvan de checkbox NIET is aangevinkt
                        jQuery('.highlight_text').each(function() {
                            var checkbox = jQuery(this);
                            var row = checkbox.closest('tr.row-highlight');

                            if (!checkbox.is(':checked')) {
                                row.hide();
                            } else {
                                row.show();
                            }
                        });
                        rowsHidden = true;
                    } else {
                        // Toon alle rijen opnieuw
                        jQuery('tr.row-highlight').show();
                        rowsHidden = false;
                    }
                });
            });
        </script>
        <div class="toggle-wrapper">
            <label class="switch">
                <input name="highlighted-toggle" type="checkbox" id="toggle_rows">
                <span class="slider round"></span>
            </label>
            <span class="toggle highlighted">Highlighted</span>
            <div class="picklocatiesRefresh-container">
                <form method="post">
                    <button class="btn-refresh" type="submit" name="refresh_picklocaties"
                        style="background:none;border:none;cursor:pointer;">
                        <i class="fa fa-refresh"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Opslaglocatie</th>
                        <th>Artikel</th>
                        <th>Omschrijving</th>
                        <?php
                        if ($numRowsCheck >= 1): ?>
                            <th style="text-align: right;">Highlighted <input type="checkbox" id="uncheck-all"
                                    name="uncheck-all" checked></th>
                        <?php
                        else: ?>
                            <th style="text-align: right;">Highlighted</th>
                        <?php
                        endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($result as $row): ?>
                        <?php
                        if (!empty($row['DefaultStorageLocationCode']) && (int) $row['DefaultStorageLocationCode'] !== 1): ?>
                            <tr class="row-highlight">
                                <?php
                                if ((int) $row['highlighted'] === 1): ?>
                                    <td><strong><?= htmlspecialchars($row['DefaultStorageLocationCode']) ?></strong>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['itemCode']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($row['itemDescription']) ?></strong></td>
                                    <td>
                                        <input type="checkbox"
                                            id="highlight_text_<?= htmlspecialchars($row['itemCode']) ?>"
                                            class="highlight_text"
                                            data-itemcode="<?= htmlspecialchars($row['itemCode']) ?>"
                                            <?= ((int) $row['highlighted'] === 1) ? 'checked' : '' ?>
                                            style="float: right">
                                        <label
                                            for="highlight_text_<?= htmlspecialchars($row['itemCode']) ?>"></label>

                                    </td>
                                <?php
                                else: ?>
                                    <td><?= htmlspecialchars($row['DefaultStorageLocationCode']) ?></td>
                                    <td><?= htmlspecialchars($row['itemCode']) ?></td>
                                    <td><?= htmlspecialchars($row['itemDescription']) ?></td>
                                    <td>
                                        <input type="checkbox"
                                            id="highlight_text_<?= htmlspecialchars($row['itemCode']) ?>"
                                            class="highlight_text"
                                            data-itemcode="<?= htmlspecialchars($row['itemCode']) ?>"
                                            <?= ((int) $row['highlighted'] === 1) ? 'checked' : '' ?>
                                            style="float: right">
                                        <label
                                            for="highlight_text_<?= htmlspecialchars($row['itemCode']) ?>"></label>
                                    </td>
                                <?php
                                endif; ?>
                            </tr>
                        <?php
                        endif; ?>
                    <?php
                    endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    jQuery(document).ready(function() {
        jQuery('#uncheck-all').change(function() {
            if (!jQuery(this).is(':checked')) {
                fetch('https://connect.smarter.nl/modules/mod/mod_picklocaties.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            action: 'unhighlight',
                        })
                    })
                    .then(response => {
                        window.location.reload();
                    })
                    .then(data => {
                        if (data.success) {
                            //console.log('Database update succesvol:', data.result);
                        } else {
                            //console.error('Fout bij database-update:', data.message);
                        }
                    })
            }
        });
    });
</script>
<script>
    document.querySelectorAll('.highlight_text').forEach(checkbox => {
        checkbox.addEventListener('change', handleCheckboxChange);
    });

    function handleCheckboxChange(e) {
        const checkboxValue = e.target.checked;
        const itemCode = e.target.dataset.itemcode;

        fetch('https://connect.smarter.nl/modules/mod/mod_picklocaties.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'sendChangeToStorageDb',
                    data: {
                        checked: checkboxValue,
                        itemCode: itemCode
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                    console.log('Database update succesvol:', data.result);
                    const checkbox = e.target;
                    const row = checkbox.closest('tr');

                    const tds = row.querySelectorAll('td');
                    // Kolom 0: opslaglocatie
                    tds[0].innerHTML = checkbox.checked ? '<strong>' + tds[0].innerText + '</strong>' : tds[0].innerText;
                    // Kolom 1: itemCode
                    tds[1].innerHTML = checkbox.checked ? '<strong>' + tds[1].innerText + '</strong>' : tds[1].innerText;
                    // Kolom 2: omschrijving + checkbox
                    tds[2].innerHTML = checkbox.checked ? '<strong>' + tds[2].innerText + '</strong>' : tds[2].innerText;
                } else {
                    console.error('Fout bij database-update:', data.message);
                }
            })
            .catch(error => console.error('Fetch-fout:', error));
    }
</script>