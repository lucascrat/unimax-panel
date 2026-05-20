<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db = new SQLite3('./api/.anspanel.db');
$res = $db->query("SELECT * FROM USERS");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        echo "ID: " . $row['id'] . "<br>";
        echo "NAME: " . htmlspecialchars($row['NAME'] ?? '') . "<br>";
        echo "USERNAME: " . htmlspecialchars($row['USERNAME'] ?? '') . "<br>";
        echo "PASSWORD: " . htmlspecialchars($row['PASSWORD'] ?? '') . "<br>";
        echo "LOGO: " . htmlspecialchars($row['LOGO'] ?? '') . "<br>";
        echo "----------------------------------------<br>";
    }
} else {
    echo "No users found or table does not exist.";
}
?>
