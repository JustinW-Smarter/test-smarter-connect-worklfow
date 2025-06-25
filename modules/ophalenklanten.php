<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/modules/functions.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/requests.php");

$klanten = itemsToDatabaseOrangeTread();
var_dump($klanten);
