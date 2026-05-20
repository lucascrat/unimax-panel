<?php
// Developer eonbry


ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
error_reporting(32767);
$jsondata = file_get_contents("./includes/ansibo.json");
$data = json_decode($jsondata, true);
$json = $data["info"];
$col = $json["aa"];
$lang = $json["aa"] == "1" ? "selected" : "";
$lang1 = $json["aa"] == "2" ? "selected" : "";
$lang2 = $json["aa"] == "3" ? "selected" : "";
$lang3 = $json["aa"] == "4" ? "selected" : "";
$lang4 = $json["aa"] == "5" ? "selected" : "";
$lang5 = $json["aa"] == "6" ? "selected" : "";
$lang6 = $json["aa"] == "7" ? "selected" : "";
$lang7 = $json["aa"] == "8" ? "selected" : "";
$lang8 = $json["aa"] == "9" ? "selected" : "";
$lang9 = $json["aa"] == "11" ? "selected" : "";
$lang10 = $json["aa"] == "12" ? "selected" : "";
$lang11 = $json["aa"] == "13" ? "selected" : "";
$lang12 = $json["aa"] == "14" ? "selected" : "";
$message = "<div class=\"alert alert-primary\" id=\"flash-msg\"><h4><i class=\"icon fa fa-check\"></i>Theme Updated!</h4></div>";
if (isset($_POST["submit"])) {
    $jsonData = file_get_contents("./includes/ansibo.json");
    $date = date("d-m-Y H:i:s");
    $time = time();
    $mug = base64_encode($date);
    $mug1 = sha1($mug);
    $mug2 = $json["ii"] + 1;
    $arrayData = json_decode($jsonData, true);
    $replacementData = ["info" => ["aa" => $_POST["aa"], "bb" => $date, "cc" => $time, "dd" => $mug, "ff" => $mug2, "gg" => $mug1]];
    $newArrayData = array_replace_recursive($arrayData, $replacementData);
    $newJsonData = json_encode($newArrayData, JSON_UNESCAPED_UNICODE);
    file_put_contents("./includes/mugibo.json", $newJsonData);
    header("Location: colours.php?message=" . $message);
}
include "includes/header.php";
echo " <!-- Begin Page Content -->\n        <div class=\"container-fluid\">\n\n";
if (isset($_GET["message"])) {
    echo $_GET["message"];
}
echo "          <!-- Page Heading -->\n          <h1 class=\"h3 mb-1 text-gray-800\">Colour Changes</h1>\n         \n          <!-- Content Row -->\n          <div class=\"row\">\n\n            <!-- First Column -->\n            <div class=\"col-lg-12\">\n\n              <!-- Custom codes -->\n                <div class=\"card border-left-primary shadow h-100 card shadow mb-4\">\n                <div class=\"card-header py-3\">\n                <h6 class=\"m-0 font-weight-bold text-primary\"><i class=\"fa fa-paintbrush\"></i> Colours</h6>\n                </div>\n                <div class=\"card-body\">\n                            <form method=\"post\">\n\t\t\t\t\t\t\t<div class=\"form-group \">\n                            <label class=\"control-label requiredField\" for=\"aa\" >\n                            <strong> Pick Your Colour</strong>\n                            </label>\n                            <select class=\"select form-control text-primary\" id=\"select\" name=\"aa\">\n";
echo "\t\t\t\t\t\t\t\t<option value=\"1\"" . $lang . ">Blue</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"2\"" . $lang1 . ">Dark-Blue</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"3\"" . $lang2 . ">Red</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"4\"" . $lang3 . ">Orange</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"5\" " . $lang4 . ">Green</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"6\"" . $lang5 . ">Teal</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"7\"" . $lang6 . ">Cyan</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"8\"" . $lang7 . ">Silver</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"9\"" . $lang8 . ">Gray</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"11\"" . $lang9 . ">Black</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"12\"" . $lang10 . ">Purple</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"13\"" . $lang11 . ">Yellow</option>" . "\n";
echo "\t\t\t\t\t\t\t\t<option value=\"14\"" . $lang12 . ">Dark Black</option>" . "\n";
echo "                            </select>\n\t\t\t\t\t\t\t</div>\n                        <button class=\"btn btn-success btn-icon-split\" name=\"submit\" type=\"submit\">\n                        <span class=\"icon text-white-50\"><i class=\"fas fa-check\"></i></span><span class=\"text\">Submit</span>\n                        </button>\n              </div>\n            </div>\n            </div>\n            </div>\n            </div>\n\n    <br><br><br>";
include "includes/footer.php";
require "includes/ans.php";
echo "</body>\n";

?>