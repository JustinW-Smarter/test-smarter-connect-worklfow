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

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    input[type="username"],
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
        margin-top: 20px;
        padding: 15px;
        background-color: #ffdddd;
        color: #a94442;
        border-left: 5px solid #f44336;
        border-radius: 5px;
        font-weight: bold;
        text-align: center;
        position: absolute;
        right: 15px;
        bottom: 30px;
    }
    .registry-redirect {
	    text-align: center;
	    padding-top: 30px;
	    font-size: small;
    }
</style>
<div class="login-wrapper">
    <div>
        <div class="login-top">
            <a href="/"><img src="/assets/img/logo.svg" alt="Lekker Brabant"></a>
            <h1>Inloggen</h1>
        </div>
        <div class="form-login-wrapper">
            <form action="/modules/loginCheck.php" method="POST">
                <div class="content">
                    <div class="input-field">
                        <label>Gebruikersnaam *</label>
                        <input type="username" name="username" id="username" autocomplete="username" required">
                    </div>
                    <div class="input-field">
                        <label>Wachtwoord *</label>
                        <input type="password" name="password" id="password" autocomplete="password" required>
                    </div>
                    <div class="submit-field">
                        <input type="submit" name="submit" value="Inloggen">
                    </div>
                </div>
            </form>
	        <div class="registry-redirect">
		        <p><a href="/registreren">Nog geen gebruiker, registreer hier</a></p>
	        </div>
        </div>
    </div>
</div>
<?php if (!empty($_SESSION["error"])): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($_SESSION["error"]); ?>
    </div>
    <?php unset($_SESSION["error"]); ?>
<?php endif; ?>