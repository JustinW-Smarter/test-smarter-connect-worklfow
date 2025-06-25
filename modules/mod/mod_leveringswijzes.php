<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/requests.php");

$leveringswijzes = beschikbareDeliveryMethods();
?>

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
</style>

<div class="mid-right">
    <div class="wrapper-all">
        <h2>Leveringswijze</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Leveringswijze</th>
                        <th>Beschrijving</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $uitsluiten = ['Eieren', 'Enzo', 'Jacques', 'Jorg', 'Marc'];
                    foreach ($leveringswijzes as $leveringswijze):
                        $overslaan = false;
                        foreach ($uitsluiten as $woord) {
                            if (stripos($leveringswijze['Description'], $woord) !== false) {
                                $overslaan = true;
                                break;
                            }
                        }
                        if ($overslaan) continue;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($leveringswijze['Code']) ?></td>
                            <td><?= htmlspecialchars($leveringswijze['Description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
