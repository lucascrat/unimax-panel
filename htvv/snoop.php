<!-- Developer eonbry -->
<?php

ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
error_reporting(32767);
$db1 = new SQLite3("./api/.logs.db");
$res1 = $db1->query("SELECT * FROM logs");
if (isset($_GET["delete"])) {
    if ($_GET["delete"] == "all") {
        $db1->exec("DELETE FROM logs");
        header("Location: snoop.php");
    } else {
        $db1->exec("DELETE FROM logs WHERE id=" . $_GET["delete"]);
        header("Location: snoop.php");
    }
}
include "includes/header.php";
echo "\n\t\n<div class=\"modal fade\" id=\"confirm-delete\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"myModalLabel\" aria-hidden=\"true\">\n    <div class=\"modal-dialog\">\n        <div class=\"modal-content\">\n            <div class=\"modal-header\">\n                <h2>Confirm</h2>\n            </div>\n            <div class=\"modal-body\">\n                Do you really want to delete?\n            </div>\n            <div class=\"modal-footer\">\n                <button type=\"button\" class=\"btn btn-primary\" data-dismiss=\"modal\">Cancel</button>\n                <a class=\"btn btn-danger btn-ok\">Delete</a>\n            </div>\n        </div>\n    </div>\n</div>\n<main role=\"main\" class=\"col-15 pt-4 px-5\"><div class=\"row justify-text-center\"><div class=\"chartjs-size-monitor\" style=\"position:absolute ; left: 0px; top: 0px; right: 0px; bottom: 0px; overflow: hidden; pointer-events: none; visibility: hidden; z-index: -1;\"><div class=\"chartjs-size-monitor-expand\" style=\"position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;\"><div style=\"position:absolute;width:1000000px;height:1000000px;left:0;top:0\"></div></div><div class=\"chartjs-size-monitor-shrink\" style=\"position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;\"><div style=\"position:absolute;width:200%;height:200%;left:0; top:0\"></div></div></div>\n          <div id=\"main\">\n          <h1 class=\" h3 mb-1 text-gray-800\"> Snoop</h1>\n          </div>\n\t\t<div class=\"table-responsive\">\n\t\t\t<table class=\"table table-striped table-sm\">\n\t\t\t<thead class= \"text-primary\">\n\t\t\t\t<tr>\n\t\t\t\t<th>IP Address</th>\n\t\t\t\t<th>Date</th>\n\t\t\t\t<th><a class=\"badge badge-danger\" href=\"#\" data-href=\"./snoop.php?delete=all\" data-toggle=\"modal\" data-target=\"#confirm-delete\"> Delete | Delete ALL <span class=\"icon text-red-50\" ><i class=\"fas fa-fw fa fa-trash-o\" \"></i></span></a></th>\n\t\t\t\t</tr>\n\t\t\t</thead>\n";
while ($row1 = $res1->fetchArray()) {
    $id = $row1["id"];
    $ipad = $row1["ipaddress"];
    $date = $row1["date"];
    echo "\t\t\t<tbody>\n\t\t\t\t<tr>\n";
    echo "\t\t\t\t<td>" . $ipad . "</td>" . "\n";
    echo "\t\t\t\t<td>" . $date . "</td>" . "\n";
    echo "             <td><a class=\"btn btn-icon\" href=\"#\" data-href=\"./snoop.php?delete=" . $id . "\" data-toggle=\"modal\" data-target=\"#confirm-delete\"><span class=\"icon text-white-50\"><i class=\"fas fa-fw fa fa-trash-o\" style=\"font-size:20px;color:red\"></i></a></td>" . "\n";
    echo "\t\t\t\t</tr>\n\t\t\t</tbody>\n";
}
echo "\t\t\t</table>\n\t\t</div>\n</main>\n\n    <br><br><br>";
include "includes/footer.php";
require "includes/ans.php";
echo "</body>\n";

?>