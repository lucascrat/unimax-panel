<!-- Developer eonbry -->
<?php


$path = "0_ibop.txt";
$rs = file($path);
$rs[0];
$rs[0] = $_SERVER["HTTP_HOST"];
$rs[1] = $_SERVER["REQUEST_URI"];
$rs[2] = $_SERVER["QUERY_STRING"];
header("Content-Type: application/json");
$db = new SQLite3("./.ansdb.db");
if (isset($_GET["m"])) {
    $address = $_GET["m"];
    $address1 = strtoupper($address);
}
if (isset($_GET["m"])) {
    $res = $db->query("SELECT * FROM ibo WHERE mac_address=\"" . $address1 . "\"");
    while ($row = $res->fetchArray()) {
        $expire_date = $row["expire_date"];
    }
    if (empty($expire_date)) {
        $api = "\n{\"status\":\"expired\"}";
    } else {
        $api = "\n{\"status\":\"success\"}";
    }
} else {
    $api = "No Way... Sorry!!!!";
}
echo $api;

?>