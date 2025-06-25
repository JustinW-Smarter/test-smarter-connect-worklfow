<style>
    .exact-wrapper {
        display: block;
    }

    .exact-container img {
        max-width: 30px;
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<div class="mid-right">
    <div class="wrapper-all">

        <div class="loading-wrapper inactive">
            <div class="loading-container">
                <div class="loading-cntent">
                    <i class="fa-solid fa-spinner"></i>
                </div>
            </div>
        </div>

        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
            $dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
            $dotenv->load();
            $conn = mysqli_connect(
                $_ENV['DATABASE_HOST'],
                $_ENV['DATABASE_USER'],
                $_ENV['DATABASE_PASS'],
                $_ENV['DATABASE_NAME']
            );
            $sql = "SELECT access_token, refresh_token FROM token LIMIT 1";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $access_token = $row["access_token"];
            $refresh_token = $row["refresh_token"];
        ?>
        <div class="exact-wrapper">
            <div class="exact-container">
                <?php
                    if (!empty($access_token) && !empty($refresh_token)): ?>
                        <?php
                        echo "<div class='exact-status status-online'>Status koppeling Exact Online: <span><i class='fa-solid fa-check'></i></span></div>"; ?>
                    <?php
                    else: ?>
                        <?php
                        echo "<div class='exact-status status-offline'>Status koppeling Exact Online: <span><i class='fa-solid fa-xmark'></i></span></div>"; ?>
                    <?php
                    endif; ?>
                <a href="/exact.php"><img src="/assets/img/exact.png" alt=""></a>
            </div>
        </div>

    </div>
</div>
