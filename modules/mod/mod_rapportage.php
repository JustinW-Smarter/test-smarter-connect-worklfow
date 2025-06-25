<style>
    .exact-wrapper {
        display: block;
    }

    .exact-container img {
        max-width: 30px;
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>



<?php
// Zet hier je instellingen
// $portalId = '911';
// $secretKey = 'dQ1*yT4-gR0$tV3*rQ3@nU6^jH6^uJ6_rG0-nY6*bK1$wD6@';
// $userName = urlencode('rob@smarter.nl'); // e-mailadres van gebruiker
// $replyUrl = 'https://connect.smarter.nl'; // je domein

// // Embed instellingen
// $workspaceId = '2629';
// $reportId = '7329';
// $dashboardDomain = 'power.smarter.nl';
// $devApiDomain = 'devapi.connect.smarter.nl';


$portalId = '911';
$secretKey = 'dQ1*yT4-gR0$tV3*rQ3@nU6^jH6^uJ6_rG0-nY6*bK1$wD6@';
$userName = urlencode('rob@smarter.nl'); // e-mailadres van gebruiker
$replyUrl = 'https://connect.smarter.nl'; // je domein

$workspaceId = '2629';
$reportId = '7329';
$dashboardDomain = 'power.smarter.nl';
$devApiDomain = 'devapi.connect.smarter.nl';


?>

<div class="mid-right">
    <div class="wrapper-all">

        <h2>Rapportage</h2>


        <iframe id="container" src=""></iframe>

        <!-- Foutmelding die standaard verborgen is -->
        <div id="errorMsg" style="display:none; padding:2em; color:red; text-align:center;">
            Er ging iets mis met het laden van het rapport. Controleer je instellingen.
        </div>

        <script>
            const portalId = "<?= $portalId ?>";
            const secretKey = "<?= $secretKey ?>";
            const userName = "<?= urldecode($userName) ?>";
            const replyUrl = "<?= $replyUrl ?>";
            const workspaceId = "<?= $workspaceId ?>";
            const reportId = "<?= $reportId ?>";
            const dashboardDomain = "<?= $dashboardDomain ?>";
            const devApiDomain = "<?= $devApiDomain ?>";

            // Stap 1: authenticatiecookie zetten via JS
            const authUrl = `https://${devApiDomain}/api/Authentication/SetSSOCookie?PortalId=${portalId}&SecretKey=${secretKey}&UserName=${encodeURIComponent(userName)}&ReplyUrl=${encodeURIComponent(replyUrl)}`;

            fetch(authUrl, {
                    credentials: 'include'
                })
                .then(response => {
                    if (!response.ok) throw new Error("Authenticatie mislukt");

                    // Stap 2: iframe vullen met embed link
                    const iframe = document.getElementById("container");
                    const embedUrl = `https://${dashboardDomain}/en/embed?workspaceId=${workspaceId}&reportId=${reportId}&portalId=${portalId}`;
                    iframe.src = embedUrl;
                })
                .catch(error => {
                    console.error("Fout bij authenticatie of laden:", error);
                    document.getElementById("errorMsg").style.display = "block";
                });
        </script>


    </div>
</div>