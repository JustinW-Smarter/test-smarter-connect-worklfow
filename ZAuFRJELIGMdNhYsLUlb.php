<?php
require 'requests.php';
ini_set('memory_limit', '2048M');
set_time_limit(10800);


// updateSuppliers();
// https://www.connect.smarter.nl/ZAuFRJELIGMdNhYsLUlb.php?parameter=tncWeTnCZxvdbjrdrbDX
if (isset($_GET['parameter'])) {
    $waarde = $_GET['parameter'];
    if ($waarde == "tncWeTnCZxvdbjrdrbDX") {
        updateSuppliers();
    }
}

// itemsToDatabaseOrangeTread();
// https://www.connect.smarter.nl/ZAuFRJELIGMdNhYsLUlb.php?parameter=VlqABnp36QRVMOapC2LZ
if (isset($_GET['parameter'])) {
    $waarde = $_GET['parameter'];
    if ($waarde == "VlqABnp36QRVMOapC2LZ") {
        itemsToDatabaseOrangeTread();
    }
}


// SalesOrdersDatabase();
// https://www.connect.smarter.nl/ZAuFRJELIGMdNhYsLUlb.php?parameter=bGrNGUiXLaPAQSFtApTD
if (isset($_GET['parameter'])) {
    $waarde = $_GET['parameter'];
    if ($waarde == "bGrNGUiXLaPAQSFtApTD") {
        SalesOrdersDatabase();
    }
}

// SalesOrderLinesSyncAll();
// https://www.connect.smarter.nl/ZAuFRJELIGMdNhYsLUlb.php?parameter=zt1zVYSF62JOIzvoh4HWe0auTXUd3EQm6spXKW3yoRsIwwCJTO
if (isset($_GET['parameter'])) {
    $waarde = $_GET['parameter'];
    if ($waarde == "zt1zVYSF62JOIzvoh4HWe0auTXUd3EQm6spXKW3yoRsIwwCJTO") {
        SalesOrderLinesSyncAll();
    }
}

// updateSuppliersClassification();
// https://www.connect.smarter.nl/ZAuFRJELIGMdNhYsLUlb.php?parameter=xQ8mLNEu39KYAfvbj2MTczloi1pRHEWqs7XkVGyBtdnmUewqhgf
if (isset($_GET['parameter'])) {
    $waarde = $_GET['parameter'];
    if ($waarde == "xQ8mLNEu39KYAfvbj2MTczloi1pRHEWqs7XkVGyBtdnmUewqhgf") {
        updateSuppliersClassification();
    }
}

// updateSuppliersClassification();
// https://www.connect.smarter.nl/ZAuFRJELIGMdNhYsLUlb.php?parameter=21n6FaQoLPYSlA759gXA
if (isset($_GET['parameter'])) {
    $waarde = $_GET['parameter'];
    if ($waarde == "21n6FaQoLPYSlA759gXA") {
        updatePicklocaties();
    }
}