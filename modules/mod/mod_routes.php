<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/requests.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/scratch.php");

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

$leveringswijzes = beschikbareDeliveryMethods();

$routes = [];
$dagen = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];

$sql = "SELECT * FROM klanten WHERE ShippingMethod IS NOT NULL AND EndDate > NOW()";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();

foreach ($result as &$route) {
    foreach (['id', 'sorting', 'IsSupplier', 'active'] as $key) {
        $route[$key] = (int) $route[$key];
    }

    foreach (['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'] as $dag) {
        $route[$dag] = (int) $route[$dag];
        $route[$dag . '_sorting'] = (int) $route[$dag . '_sorting'];
    }
}
unset($route); // Belangrijk bij pass-by-reference in foreach

$sqlInstellingen = "SELECT * FROM instellingen LIMIT 1";
$stmt2 = $conn->prepare($sqlInstellingen);
$stmt2->execute();
$resultInstellingen = $stmt2->fetchAll();

$numRows = count($result);

if ($numRows == 0) {
    $maandag = false;
    $dinsdag = false;
    $woensdag = false;
    $donderdag = false;
    $vrijdag = false;
    $zaterdag = false;
    $zondag = false;
} else {
    if ($resultInstellingen) {
        $firstRow = $resultInstellingen[0];
        $maandag = ($firstRow['maandag'] == 1) ? true : false;
        $dinsdag = ($firstRow['dinsdag'] == 1) ? true : false;
        $woensdag = ($firstRow['woensdag'] == 1) ? true : false;
        $donderdag = ($firstRow['donderdag'] == 1) ? true : false;
        $vrijdag = ($firstRow['vrijdag'] == 1) ? true : false;
        $zaterdag = ($firstRow['zaterdag'] == 1) ? true : false;
        $zondag = ($firstRow['zondag'] == 1) ? true : false;
    }
}


function sortRoutesPerDag(&$routes)
{
    foreach ($routes as &$leveringsdata) {
        foreach ($leveringsdata['dagen'] as $dag => &$dagData) {
            if (!isset($dagData['routes']['sorting'])) {
                continue; // Skip als er geen sorting data is
            }

            // Combineer de arrays om ze correct te sorteren
            $combined = array_map(
                null,
                $dagData['routes']['code'],
                $dagData['routes']['klant'],
                $dagData['routes']['id'],
                $dagData['routes']['sorting']
            );

            // Sorteer de gecombineerde arrays op basis van 'sorting'
            usort($combined, function ($a, $b) {
                return $b[3] <=> $a[3]; // Aflopend sorteren
            });

            // Splits de arrays weer op in aparte arrays
            $dagData['routes']['code'] = array_column($combined, 0);
            $dagData['routes']['klant'] = array_column($combined, 1);
            $dagData['routes']['id'] = array_column($combined, 2);
            $dagData['routes']['sorting'] = array_column($combined, 3);
        }
        unset($dagData); // Referentie opschonen
    }
    unset($leveringsdata); // Referentie opschonen
}

foreach ($result as $route) {
    $route_id = $route['id'];
    foreach ($leveringswijzes as $leveringsdata) {
        // Check voor elke dag of de leveringswijze overeenkomt en voeg toe aan de juiste dagen
        foreach ($dagen as $dag) {
            if ($route[$dag . '_leveringswijze'] === $leveringsdata['Code']) {
                // Als de leveringswijze al bestaat voor deze Code, voeg dan de dag toe
                if (!isset($routes[$leveringsdata['Code']])) {
                    $routes[$leveringsdata['Code']] = [
                        'id' => $route_id,
                        'actief' => $route[$dag],
                        'dagen' => []
                    ];
                }
                $routes[$leveringsdata['Code']]['dagen'][$dag]['routes']['code'][] = $route['Code'];
                $routes[$leveringsdata['Code']]['dagen'][$dag]['routes']['klant'][] = $route['Name'];
                $routes[$leveringsdata['Code']]['dagen'][$dag]['routes']['id'][] = $route_id;
                $routes[$leveringsdata['Code']]['dagen'][$dag]['routes']['sorting'][] = $route[$dag . '_sorting'];
            }
        }
    }
}

sortRoutesPerDag($routes); // sorteer de routes op sortering
ksort($routes); // sorteer de leveringswijzes op alfabetische volgorde

// $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

if (isset($_POST['opslaan_als_default'])) {
    $conn->query("TRUNCATE TABLE klanten_Default");

    // // Kopieer alles van items naar klanten_Default
    $result = $conn->query("INSERT INTO klanten_Default SELECT * FROM klanten");

    if ($result) {
        // echo "Data succesvol opgeslagen als default.";
    } else {
        echo "Fout bij kopiëren: " . $conn->error;
    }

    echo "<script>window.location.href = window.location.href;</script>";
    exit;
}

if (isset($_POST['herstel_default'])) {
    $checkDefaultQuery = "SELECT * FROM `klanten_Default`";
    $checkDefault = $conn->prepare($checkDefaultQuery);

    $checkDefault->execute();
    $resultDefault = $checkDefault->get_result();
    $resultDefaultrow = $resultDefault->fetch_all();

    if ($resultDefault->num_rows == 0) {
        echo "Er is nog geen default ingesteld om te herstellen.";

        return;
    }

    $sql = "UPDATE klanten
        JOIN klanten_Default ON klanten.guid = klanten_Default.guid
        SET
            klanten.maandag = klanten_Default.maandag,
            klanten.maandag_leveringswijze = klanten_Default.maandag_leveringswijze,
            klanten.maandag_sorting = klanten_Default.maandag_sorting,
            klanten.dinsdag = klanten_Default.dinsdag,
            klanten.dinsdag_leveringswijze = klanten_Default.dinsdag_leveringswijze,
            klanten.dinsdag_sorting = klanten_Default.dinsdag_sorting,
            klanten.woensdag = klanten_Default.woensdag,
            klanten.woensdag_leveringswijze = klanten_Default.woensdag_leveringswijze,
            klanten.woensdag_sorting = klanten_Default.woensdag_sorting,
            klanten.donderdag = klanten_Default.donderdag,
            klanten.donderdag_leveringswijze = klanten_Default.donderdag_leveringswijze,
            klanten.donderdag_sorting = klanten_Default.donderdag_sorting,
            klanten.vrijdag = klanten_Default.vrijdag,
            klanten.vrijdag_leveringswijze = klanten_Default.vrijdag_leveringswijze,
            klanten.vrijdag_sorting = klanten_Default.vrijdag_sorting,
            klanten.zaterdag = klanten_Default.zaterdag,
            klanten.zaterdag_leveringswijze = klanten_Default.zaterdag_leveringswijze,
            klanten.zaterdag_sorting = klanten_Default.zaterdag_sorting,
            klanten.zondag = klanten_Default.zondag,
            klanten.zondag_leveringswijze = klanten_Default.zondag_leveringswijze,
            klanten.zondag_sorting = klanten_Default.zondag_sorting
    ";
    $result = $conn->query($sql);

    if ($result) {
        //echo "Data succesvol opgeslagen als default.";
    } else {
        echo "Fout bij kopiëren: " . $conn->error;
    }

    echo "<script>window.location.href = window.location.href;</script>";
    exit;
}

?>
<style>
    /* body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #f8f8f8;
        margin: 20px;
    } */

    .wrapper-all {
        position: relative;
    }

    .table-container th {
        text-transform: capitalize;
    }

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
        table-layout: fixed;
    }

    .table-container thead th {
        text-align: left;
        padding: 10px 10px;
        border-bottom: 1px solid #ccc;
        color: #464c53 !important;
    }

    .table-container tbody td {
        padding: 10px 10px;
        vertical-align: top !important;
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

    .standard-td {
        padding: 0;
        vertical-align: top;
    }

    .route-name {
        font-weight: bold;
        margin-bottom: 4px;
        color: white;
    }

    .route-code {
        color: #6c757d;
        font-size: 0.9em;
        margin-bottom: 4px;
    }

    .route-container {
        padding: 0;
        vertical-align: top;
        min-height: 50px;
        /* Ensure container is always droppable */
    }

    .route-blok {
        background-color: #ff4f56;
        color: white;
        margin: 0;
        padding: 8px;
        cursor: grab;
        user-select: none;
        -webkit-user-select: none;
        border-radius: 4px;
        transition: transform 0.2s, box-shadow 0.2s;
        vertical-align: top;
        margin-bottom: 6px;
        /* Scheiding tussen de blokken */
        word-wrap: break-word;
        /* Lange woorden afbreken */
        max-width: 100%;
        /* Zorg ervoor dat de blokken niet breder worden dan de cel */
        box-sizing: border-box;
        /* Zorg ervoor dat de padding binnen de breedte van het blok valt */
    }

    .route-blok:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        background-color: #c7272e;
    }

    button.btn-default {
        padding: 10px;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }


    button.btn-default.opslaan {
        background-color: #8ed36e;
    }


    button.btn-default.herstellen {
        background-color: #c7272e;
    }

    form#default-opslaan {
        margin-right: 10px;
    }

    .wrapper-default {
        position: absolute;
        right: 0px;
        top: 0px;
    }

    .wrapper-default form {
        display: inline-block;
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
        var dagen = {
            maandag: <?php echo $maandag ? 'true' : 'false'; ?>,
            dinsdag: <?php echo $dinsdag ? 'true' : 'false'; ?>,
            woensdag: <?php echo $woensdag ? 'true' : 'false'; ?>,
            donderdag: <?php echo $donderdag ? 'true' : 'false'; ?>,
            vrijdag: <?php echo $vrijdag ? 'true' : 'false'; ?>,
            zaterdag: <?php echo $zaterdag ? 'true' : 'false'; ?>,
            zondag: <?php echo $zondag ? 'true' : 'false'; ?>
        };

        jQuery.each(dagen, function(dag, waarde) {
            if (!waarde) {
                jQuery('.' + dag).hide();
            }
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
        <h2>Routes</h2>
        <div class="wrapper-default">
            <form method="post" id="default-opslaan">
                <button class="btn-default opslaan" type="submit" name="opslaan_als_default" value="default-opslaan">
                    Opslaan als default
                </button>
            </form>
            <form method="post" id="default-herstellen">
                <button class="btn-default herstellen" type="submit" name="herstel_default" value="default-herstellen">
                    Herstellen naar default
                </button>
            </form>

            <script>
                jQuery(document).ready(function() {

                    document.getElementById('default-opslaan').addEventListener('submit', function(e) {
                        if (!confirm('Weet je zeker dat je de huidige routes als default wilt opslaan?')) {
                            e.preventDefault();
                        }

                        jQuery('.loader-wrapper').removeClass('inactive');
                        jQuery('.loader-wrapper').addClass('active');
                    });

                    document.getElementById('default-herstellen').addEventListener('submit', function(e) {
                        if (!confirm('Weet je zeker dat je de huidige routes naar default wilt zetten?')) {
                            e.preventDefault();
                        }

                        jQuery('.loader-wrapper').removeClass('inactive');
                        jQuery('.loader-wrapper').addClass('active');
                    });
                });
            </script>
        </div>
        <?php
        foreach ($routes as $leveringswijze => $data): ?>
            <h2 class="leverings-methode"><?= htmlspecialchars($leveringswijze) ?></h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <?php
                            foreach ($dagen as $dag): ?>
                                <th class="<?= htmlspecialchars($dag) ?>"><?= htmlspecialchars($dag) ?></th>
                            <?php
                            endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php
                            foreach ($dagen as $dag): ?>
                                <td class="<?= $dag ?> route-container" data-day="<?= $dag ?>">
                                    <?php
                                    if (isset($data['dagen'][$dag]['routes']['code']) && isset($data['dagen'][$dag]['routes']['klant'])):
                                        foreach ($data['dagen'][$dag]['routes']['code'] as $index => $code):
                                            if (empty($data['dagen'][$dag]['routes']['klant'][$index])) {
                                                continue;
                                            }
                                            $codeKlant = $data['dagen'][$dag]['routes']['code'][$index];
                                            $klantnaam = $data['dagen'][$dag]['routes']['klant'][$index];
                                            $routeId = $data['dagen'][$dag]['routes']['id'][$index] ?? null;
                                            $sortering = $data['dagen'][$dag]['routes']['sorting'][$index] ?? null;
                                    ?>
                                            <div class="route-blok" id="blok-id" draggable="true"
                                                data-day="<?= htmlspecialchars($dag) ?>"
                                                data-id="<?= htmlspecialchars($routeId) ?>"
                                                data-levering="<?= htmlspecialchars($data['id']) ?>"
                                                data-sorting="<?= htmlspecialchars($sortering) ?>">
                                                <div class="route-name"><?= htmlspecialchars($klantnaam) ?>
                                                    (<?= htmlspecialchars($codeKlant) ?>)
                                                </div>
                                            </div>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </td>
                            <?php
                            endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php
        endforeach; ?>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let draggedElement = null;
        let originalContainer = null;

        // Set up drag events for each route block
        document.querySelectorAll('.route-blok').forEach(routeBlock => {
            routeBlock.addEventListener('dragstart', () => {
                draggedElement = routeBlock;
                originalContainer = routeBlock.closest('td'); // ✅ Bewaar originele td
                routeBlock.classList.add('dragging');
                setTimeout(() => {
                    routeBlock.style.opacity = '0.5';
                }, 0);
            });

            routeBlock.addEventListener('dragend', () => {
                draggedElement = null;
                originalContainer = null;
                routeBlock.classList.remove('dragging');
                routeBlock.style.opacity = '1';
                document.querySelectorAll('.route-container').forEach(container => {
                    container.classList.remove('drag-over');
                });
            });
        });

        // Dragover en drop voor elke container
        document.querySelectorAll('.route-container').forEach(container => {
            container.addEventListener('dragenter', e => {
                e.preventDefault();
                if (draggedElement) {
                    container.classList.add('drag-over');
                }
            });

            container.addEventListener('dragleave', e => {
                e.preventDefault();
                container.classList.remove('drag-over');
            });

            container.addEventListener('drop', e => {
                e.preventDefault();
                container.classList.remove('drag-over');

                if (originalContainer !== container) {
                    alert('Je kunt een route niet verplaatsen naar een andere dag.');
                    location.reload(); // 👈 Pagina wordt herladen
                    return;
                }

                handleDrop(container);
            });

            container.addEventListener('dragover', e => {
                e.preventDefault();
                if (draggedElement) {
                    const afterElement = getDragAfterElement(container, e.clientY);
                    if (draggedElement !== afterElement) {
                        if (afterElement) {
                            container.insertBefore(draggedElement, afterElement);
                        } else {
                            container.appendChild(draggedElement);
                        }
                    }
                }
            });
        });

        // Drop verwerkende functie
        function handleDrop(container) {
            let leveringsmethodeElement = draggedElement.closest('.mid-right')?.querySelector('h2.leverings-methode');
            if (leveringsmethodeElement) {
                console.log('Dichtstbijzijnde Leveringsmethode:', leveringsmethodeElement.textContent.trim());
            }

            let parentTd = draggedElement.closest('td');
            if (parentTd) {
                let routeBlokken = [...parentTd.querySelectorAll('.route-blok')];
                let idsInVolgorde = routeBlokken.map((block, index) => {
                    block.dataset.sorting = index;
                    return block.dataset.id;
                });

                console.log('Id\'s in juiste volgorde:', idsInVolgorde);
                console.log('Sorting waarden:', routeBlokken.map((_, i) => i));

                let data = {
                    leveringsmethode: leveringsmethodeElement?.textContent.trim() || '',
                    order: routeBlokken.map((block, index, array) => ({
                        id: block.dataset.id,
                        dag: block.dataset.day,
                        sorting: array.length - 1 - index + 1
                    }))
                };

                console.log('Te verzenden data:', data);

                fetch('/scratch.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            action: 'routevolgordeOpslaan',
                            data: data
                        })
                    })
                    .then(response => response.text())
                    .catch(error => console.error('Error:', error));
            }
        }

        // Vind het juiste element om voor/in te droppen
        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.route-blok:not(.dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return {
                        offset,
                        element: child
                    };
                } else {
                    return closest;
                }
            }, {
                offset: Number.NEGATIVE_INFINITY
            }).element;
        }
    });
</script>