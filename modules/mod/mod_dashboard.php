<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

    // Maak een verbinding met de database
    $conn = getMysqliConnection();

    // SQL-query
    $sql = "SELECT * FROM members WHERE id = ? LIMIT 1";

    // Bereid de query voor
    $stmt = $conn->prepare($sql);

    // Bind de parameter
    $id = $_SESSION["member"];
    $stmt->bind_param("i", $id); // "i" staat voor integer

    // Voer de query uit
    $stmt->execute();

    // Verkrijg het resultaat
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Haal de eerste rij op
        $row = $result->fetch_assoc();

        // Haal de id op
        $id = $row['id'];
        $username = $row['username'];
        $firstname = $row['firstname'];
        $lastname = $row['lastname'];
    }
    // Sluit de verbinding
    $stmt->close();
    $conn->close();
?>
<style>
    body {
        margin: 0;
        font-family: sans-serif;
        background-color: #f5f5f5;
    }

    .dashboard-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }

    .dashboard-buttons button {
        padding: 10px 20px;
        font-size: 16px;
        background-color: #c7272e;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .dashboard-buttons button:hover {
        background-color: rgb(155, 30, 36);
    }

    .dashboard-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        background-color: #c7272e;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: background-color 0.3s, transform 0.2s;
    }

    .dashboard-link:hover {
        background-color: rgb(155, 30, 36);
        transform: translateY(-2px);
    }

    .dashboard-link .icon {
        font-size: 18px;
    }

    /* Bovenste sectie */
    .dashboard-top {
        background-color: #ffffff;
        color: #000;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    /* Rij met linker en rechter sectie */
    .dashboard-row {
        display: flex;
        /* padding: 20px; */
        gap: 20px;
    }

    /* Linker (grote) kolom */
    .dashboard-left {
        flex: 4;
        background-color: #ffffff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    }

    /* Rechter (kleine) kolom */
    .dashboard-right {
        flex: 1;
        background-color: #ffffff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    }

    .dashboard-right h3,
    .dashboard-right h4 {
        margin-top: 0;
        color: #c7272e;
    }

    .dashboard-right ul li a:hover {
        text-decoration: underline;
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<div class="mid-right">
    <div class="wrapper-all">

        <div class="dashboard-top">
            <h1>
                <?php
                    date_default_timezone_set('Europe/Amsterdam'); // Pas aan indien nodig
                    $hour = date('H');
                    if ($hour >= 6 && $hour < 12) {
                        echo 'Goedemorgen, ' . $firstname;
                    } elseif ($hour >= 12 && $hour < 18) {
                        echo 'Goedemiddag, ' . $firstname;
                    } else {
                        echo 'Goedeavond, ' . $firstname;
                    }
                ?>
            </h1>
        </div>

        <div class="dashboard-row">
            <div class="dashboard-left">
                <div class="dashboard-buttons">
                    <a href="" class="dashboard-link">
                        <span class="icon"><i class="fa-solid fa-clipboard-list"></i></span>
                        <span>verzamelpicklijsten</span>
                    </a>
                    <a href="" class="dashboard-link">
                        <span class="icon"><i class="fa-solid fa-route"></i></span>
                        <span>Klanten</span>
                    </a>
                    <a href="" class="dashboard-link">
                        <span class="icon"><i class="fa-solid fa-route"></i></span>
                        <span>Routes</span>
                    </a>
                    <a href="" class="dashboard-link">
                        <span class="icon"><i class="fa-solid fa-location-dot"></i></span>
                        <span>Picklocaties</span>
                    </a>
                </div>
            </div>
            <div class="dashboard-right">
                <h3>Mijn Profiel</h3>
                <p><strong>Gebruikersnaam:</strong> <?php
                        echo $username ?></p>
                <p><strong>Naam:</strong> <?php
                        echo $firstname . " " . $lastname ?></p>
            </div>
        </div>

    </div>
</div>
