<?php
// if ($_SERVER["REMOTE_ADDR"] == '217.100.38.106') {
// } else {
//   require_once('isloggedinCheck.php');
// }


if ($merged == 0) {
  $merged = $_POST;
}


// $query = "INSERT INTO ticketlog (naarstatus, ticketnummer, vorigestatus, timestamp, doormemberid) VALUES (?, ?, ?, ?, ?)";

$time = time();
$memberId = $_SESSION['member']['id'];
$opmerking = $merged["opmerking"];

$sql = "INSERT INTO loginLog (tstamp, doormemberid, opmerking) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $time, $memberId, $opmerking);
$stmt->execute();
$stmt->close();
$conn->close();
