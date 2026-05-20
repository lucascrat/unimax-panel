<?php
// Developer eonbry
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
error_reporting(32767);
$jsondata = file_get_contents("./api/update.json");
$data = json_decode($jsondata, true);
$json = $data["app_info"];
$avc = $json["android_version_code"];
$apkurl = $json["apk_url"];
$message = "<div class=\"alert alert-primary\" id=\"flash-msg\"><h4><i class=\"icon fa fa-check\"></i>Apk Details Updated!</h4></div>";
if (isset($_POST["submit"])) {
    $jsonData = file_get_contents("./api/update.json");
    $arrayData = json_decode($jsonData, true);
    $replacementData = ["app_info" => ["android_version_code" => $_POST["android_version_code"], "apk_url" => $_POST["apk_url"]]];
    $newArrayData = array_replace_recursive($arrayData, $replacementData);
    $newJsonData = json_encode($newArrayData, JSON_PRETTY_PRINT);
    file_put_contents("./api/update.json", $newJsonData);
    header("Location: update.php?message=" . $message);
}
include "includes/header.php";
echo "        <div class=\"container-fluid\">\n\n          <!-- Page Heading -->\n          <h1 class=\"h3 mb-1 text-gray-800\">Apk Update</h1>\n         \n          <!-- Content Row -->\n          <div class=\"row\">\n\n            <!-- First Column -->\n            <div class=\"col-lg-12\">\n\n              <!-- Custom codes -->\n                <div class=\"card border-left-primary shadow h-100 card shadow mb-4\">\n                <div class=\"card-header py-3\">\n                <h6 class=\"m-0 font-weight-bold text-primary\"><i class=\"fa fa-refresh\"></i> Apk Update</h6>\n                </div>\n                <div class=\"card-body\">\n\t\t\t\t\t\t\t<form method=\"post\">\n\t\t\t\t\t\t\t<div class=\"form-group\">\n                            <h6 class=\"form-text\"><strong>Version Code</strong></h6>\n";
echo "\t\t\t\t\t\t\t\t<input type=\"text\" class=\"form-control\" placeholder=\"Version Code\" name=\"android_version_code\" value=\"" . $avc . "\">" . "\n";
echo "\t\t\t\t\t\t\t</div>\n\t\t\t                <form method=\"post\">\n\t\t\t\t\t\t\t<div class=\"form-group\">\n                            <h6 class=\"form-text\"><strong>Download Url</strong></h6>\n";
echo "\t\t\t\t\t\t\t\t<input type=\"text\" class=\"form-control\" placeholder=\"http://link to app.apk\" name=\"apk_url\" value=\"" . $apkurl . "\">" . "\n";
echo "\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<form method=\"post\">\n\t\t\t\t\t\t\t<button class=\"btn btn-primary btn-icon-split\" name=\"submit\" type=\"submit\">\n                        <span class=\"icon text-white-50\"><i class=\"fas fa-check\"></i></span><span class=\"text\">Submit</span>\n                        </button>\n                            </div>\n                    </form>\n                </div>\n              </div>\n<br><br><br><br>\n            </div>\n            </div>\n            \n\n                                         <br><br><br>\n";
include "includes/footer.php";
echo "                <script> \n\$(document).ready(function () {\n    \$(\"#flash-msg\").delay(3000).fadeOut(\"slow\");\n});\n  </script>\n</body>\n\n</html>";

?>