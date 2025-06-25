<?php
require_once _DIR_ . '/../vendor/autoload.php'; // pas dit pad aan indien nodig
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']); // pad naar de map waar .env staat
$dotenv->load();

function connectToDB()
{
    $dsn = "mysql:host={$_ENV['DATABASE_HOST']};dbname={$_ENV['DATABASE_NAME']}";

    try {
        $conn = new PDO($dsn, $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASS']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $conn;
    } catch (PDOException $e) {
        throw new RuntimeException('Failed to connect to the database.', (int)$e->getCode(), $e);
    }
}
