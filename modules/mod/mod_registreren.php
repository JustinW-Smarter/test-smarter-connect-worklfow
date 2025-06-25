<?php
//    ini_set('session.cookie_httponly', 1);
//    ini_set('session.cookie_secure', 1);
    if (!headers_sent()) {
        if (!ob_get_level()) {
            ob_start();
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        } else {
            session_regenerate_id(true);
        }
    }
//    session_regenerate_id(true);
    include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/functions.php");
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli(
            $_ENV['DATABASE_HOST'],
            $_ENV['DATABASE_USER'],
            $_ENV['DATABASE_PASS'],
            $_ENV['DATABASE_NAME']
        );
        $conn->set_charset('utf8mb4');
    } catch(mysqli_sql_exception $e) {
        error_log($e->getMessage());
        echo "Databaseverbinding mislukt.";
        exit;
    }

	function fetchLastRegistryInput() {

        $firstname = $_POST['first-name'] ?? null;
        $lastname = $_POST['last-name'] ?? null;
        $username = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;
        $company = $_POST['company'] ?? null;
        $password = $_POST['password'] ?? null;
        $confirmPassword = $_POST['confirm-password'] ?? null;

		return [
            'first-name' => $firstname ?? '',
            'last-name' => $lastname ?? '',
            'username' => $username ?? '',
            'email' => $email ?? '',
            'company' => $company ?? '',
			'password' => $password ?? '',
            'confirm-password' => $confirmPassword ?? ''
        ];
	}

    function validateEmail($email) : void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'invalid-email';
            $_SESSION['old_input'] = fetchLastRegistryInput();
            header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
            exit();
        }
    }

	function validatePassword($password, $confirmPassword): void {
        $forbiddenChars = ["'", '"', '#', '%', '_', '-'];
        $foundChars = [];

        foreach ($forbiddenChars as $char) {
            if (strpos($password, $char) !== false) {
                $foundChars[] = $char; // Sla alleen de tekens op, zonder <code> tags
            }
        }

		if (strlen($password) < 12) {
            $error = 'password-too-short';
			$_SESSION['old_input'] = fetchLastRegistryInput();
			header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
			exit();
		}

		if (strlen($password) > 50) {
            $error = 'password-too-long';
			$_SESSION['old_input'] = fetchLastRegistryInput();
			header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
			exit();
		}

        if (!empty($foundChars)) {
            // Maak een veilige HTML-string voor weergave, met <code> tags
            $usedHtml = implode(', ', array_map(fn($c) => "<code>" . htmlspecialchars($c) . "</code>", $foundChars));
            $_SESSION['password_error_chars'] = $usedHtml;

            $error = 'invalid-password';
            $_SESSION['old_input'] = fetchLastRegistryInput();
            header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
            exit();
        }

        if ($password !== $confirmPassword) {
			$error = 'password-mismatch';
            $_SESSION['old_input'] = fetchLastRegistryInput();
			header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
			exit();
        }
	}

    function validateTableExist($conn): void {
        $query = "SHOW TABLES LIKE 'members'";
        $result = $conn->query($query);
        if ($result->num_rows === 0) {
            $error = 'empty-database';
            $_SESSION['old_input'] = fetchLastRegistryInput();
            header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
            exit();
        }
    }

    function isRegistered($conn, $email, $username) : void
    {
        $query = "SELECT `password` FROM `members` WHERE email = ? AND username = ?";
		$stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_assoc();

        if (!empty($rows) && isset($rows['password'])) {
            $error = "registered-user";
            $_SESSION['old_input'] = fetchLastRegistryInput();
            header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
            exit();
        }
    }

    function registerInputAlreadyExists($conn, $email, $username, $password) : void
    {
        $query = "SELECT `email`, `username`, `password` FROM `members`";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Als wachtwoord al bestaat
            if (password_verify($password, $row['password'])) {
                $error = "password-taken";
                $_SESSION['old_input'] = fetchLastRegistryInput();
                header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
                exit();
            }
            // Combinatie bestaat al (ideale match)
            if ($row['email'] === $email && $row['username'] === $username) {
				$error = "user-already-exists";
                $_SESSION['old_input'] = fetchLastRegistryInput();
                header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
                exit();
            }
            // Email of username zijn al individueel in gebruik
            if ($row['email'] === $email) {
				$error = "email-taken-email";
                $_SESSION['old_input'] = fetchLastRegistryInput();
				header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
                exit();
            }
            if ($row['username'] === $username) {
				$error = "username-taken-username";
                $_SESSION['old_input'] = fetchLastRegistryInput();
				header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
                exit();
            }
            // Kruisvergelijking voorkomen: email is username van ander
            if ($row['username'] === $email) {
				$error = "username-taken-email";
                $_SESSION['old_input'] = fetchLastRegistryInput();
				header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
                exit();
            }
            // Kruisvergelijking voorkomen: username is email van ander
            if ($row['email'] === $username) {
				$error = "email-taken-username";
                $_SESSION['old_input'] = fetchLastRegistryInput();
				header("Location: " . rtrim($_SERVER['PHP_SELF'], '.php') . "?error=$error");
                exit();
            }
        }
    }

    function resgisterUser($conn, $firstname, $lastname, $username, $email, $password, $tstmap) : void
    {
        $password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO `members` (`firstname`, `lastname`, `username`, `email`, `password`, `tstamp`) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $firstname, $lastname, $username, $email, $password, $tstmap);
        $stmt->execute();
    }

	$firstname = fetchLastRegistryInput()['firstname'] ?? null;
	$lastname = fetchLastRegistryInput()['lastname'] ?? null;
	$username = fetchLastRegistryInput()['username'] ?? null;
	$email = fetchLastRegistryInput()['email'] ?? null;
    $company = fetchLastRegistryInput()['company'] ?? null;
    $password = fetchLastRegistryInput()['password'] ?? null;
	$confirmPassword = fetchLastRegistryInput()['confirm-password'] ?? null;
    $tstmap = time();

    if (!empty($email) && !empty($username) && !empty($password)) {
        $username = trim($username, '');
        $email = strtolower(trim($email, ''));

        validateTableExist($conn);
        validateEmail($email);
        validatePassword($password, $confirmPassword);
        isRegistered($conn, $email, $username);
        registerInputAlreadyExists($conn, $email, $username, $password);
        resgisterUser($conn, $firstname, $lastname, $username, $email, $password, $tstmap);

        $succes = 'registry-success';
        header("Location: /inloggen?success=$succes");
    }
?>
	<style>
        body {
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .login-wrapper {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 350px;
        }

        .login-top {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-top img {
            max-width: 200px;
            height: auto;
            margin-bottom: 10px;
        }

        .input-field {
            margin-bottom: 15px;
        }

        .input-error {
            background-color: #ea7f7f !important;
	        color: #000000;
	        font-weight: bold;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type=email],
        input[type="password"] {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .submit-field input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #142238;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .error-message {
            padding: 15px;
            background-color: #ffdddd;
            color: #a94442;
            border-left: 5px solid #f44336;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            position: absolute;
            right: 20px;
            bottom: 20px;
        }
	</style>
	<body>
		<div class="register-wrapper">
			<div>
				<?php if (isset($_GET['error']) && $_GET['error'] === 'registered-user' && !empty($_SESSION['old_input']['username'] ?? '')): ?>
					<div class="error-message"><?= htmlspecialchars($_SESSION['old_input']['username'] ?? '') ?> bestaat al. Log in a.u.b.</div>
				<?php endif; ?>
				<div class="login-top">
					<a href="/"><img src="/assets/img/logo.svg" alt="Lekker Brabant"></a>
					<h1>Registreren</h1>
				</div>
				<div class="form-login-wrapper">
					<form action="" method="POST">
						<div class="content">
                            <div class="input-field">
								<label>
									Voornaam *
									<input type="text" name="first-name" autocomplete="given-name" required value="<?= htmlspecialchars($_SESSION['old_input']['first-name'] ?? '') ?>">
								</label>
							</div>
                            <div class="input-field">
								<label>
									Achternaam *
									<input type="text" name="last-name" autocomplete="family-name" required value="<?= htmlspecialchars($_SESSION['old_input']['last-name'] ?? '') ?>">
								</label>
							</div>
							<div class="input-field">
                                <?php if (isset($_GET['error']) && ($_GET['error'] === 'username-taken-username' || $_GET['error'] === 'username-taken-email')): ?>
                                    <div class="error-message">Deze gebruikersnaam is al in gebruik</div>
                                <?php endif; ?>
								<label>
									Gebruikersnaam *
									<input type="text" name="username" autocomplete="username" required value="<?= htmlspecialchars($_SESSION['old_input']['username'] ?? '') ?>">
								</label>
							</div>
							<div class="input-field">
                                <?php if (isset($_GET['error']) && ($_GET['error'] === 'email-taken-email' || $_GET['error'] === 'email-taken-username')): ?>
                                    <div class="error-message">Dit e-mailadres is al in gebruik</div>
                                <?php endif; ?>
								<?php if (isset($_GET['error']) && ($_GET['error'] === 'invalid-email')): ?>
									<div class="error-message">Dit e-mailadres is ongeldig</div>
								<?php endif; ?>
								<label>
									Email *
									<input type="text" name="email" autocomplete="email" required value="<?= htmlspecialchars($_SESSION['old_input']['email'] ?? '') ?>">
								</label>
							</div>
							<div class="input-field">
								<label>
									Bedrijf *
									<input type="text" name="company" autocomplete="organization" required value="<?= htmlspecialchars($_SESSION['old_input']['company'] ?? '') ?>">
								</label>
							</div>
							<div class="input-field">
                                <?php if (isset($_GET['error']) && ($_GET['error'] === 'password-too-short')): ?>
									<div class="error-message">Dit wachtwoord is te kort</div>
                                <?php endif; ?>
                                <?php if (isset($_GET['error']) && ($_GET['error'] === 'password-too-long')): ?>
									<div class="error-message">Dit wachtwoord is te lang</div>
                                <?php endif; ?>
								<?php if (isset($_GET['error']) && ($_GET['error'] === 'password-taken')): ?>
									<div class="error-message">Dit wachtwoord is al in gebruik</div>
								<?php endif; ?>
                                <?php if (isset($_GET['error'], $_SESSION['password_error_chars']) && $_GET['error'] === 'invalid-password'): ?>
									<div class='error-message'>
										Het wachtwoord bevat verboden tekens: <?= $_SESSION['password_error_chars'] ?>. Gebruik geen ongeoorloofde symbolen.
									</div>
                                    <?php unset($_SESSION['password_error_chars']); ?>
                                <?php endif; ?>
								<label>
									Wachtwoord *
									<input type="password" name="password" autocomplete="new-password" required value="<?= htmlspecialchars($_SESSION['old_input']['password'] ?? '') ?>">
								</label>
							</div>
							<div class="input-field">
                                <?php if (isset($_GET['error']) && $_GET['error'] === 'password-mismatch'): ?>
                                    <div class="error-message">Wachtwoorden komen niet overeen</div>
                                <?php endif; ?>
								<label>
									Bevestig wachtwoord *
									<input type="password" name="confirm-password" autocomplete="new-password" required value="<?= htmlspecialchars($_SESSION['old_input']['confirm-password'] ?? '') ?>">
								</label>
							</div>
							<br><br>
							<div class="submit-field">
								<input type="submit" name="submit" value="Registreren">
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</body>
	<script>
        // Helper om parameter uit URL te lezen
        function getQueryParam(param) {
            const params = new URLSearchParams(window.location.search);
            return params.get(param);
        }

        // Mapping van error codes naar input name attributes
        const errorInputMap = {
            'invalid-email': ['email'],
            'email-taken-email': ['email'],
            'email-taken-username': ['email'],
            'username-taken-username': ['username'],
            'username-taken-email': ['username'],
            'registered-user': ['username'],
            'password-taken': ['password'],
	        'password-too-short': ['password'],
	        'password-too-long': ['password'],
            'invalid-password': ['password'],
            'password-mismatch': ['password', 'confirm-password']
        };

        const error = getQueryParam('error');
        if (error && errorInputMap[error]) {
            errorInputMap[error].forEach(name => {
                const input = document.querySelector(`input[name="${name}"]`);
                if (input) {
                    input.classList.add('input-error');
                }
            });
        }
	</script>
	<?php
	    if (ob_get_level()) {
	        ob_end_flush();
	    }
        unset($_SESSION['old_input']);
	?>