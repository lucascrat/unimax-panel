<?php
// Developer eonbry

ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
error_reporting(32767);
$db = new SQLite3("./api/.anspanel.db");
$res = $db->query("SELECT * \n\t\t\t\t  FROM USERS \n\t\t\t\t  WHERE ID='1'");
$row = $res->fetchArray();
$message = "<div class=\"alert alert-primary\" id=\"flash-msg\"><h4><i class=\"icon fa fa-check\"></i>Profile Updated!</h4></div>";
if (isset($_POST["submit"])) {
    $db->exec("UPDATE USERS \n\t\t\tSET\tNAME='" . $_POST["name"] . "'," . "\n\t\t\t" . "    USERNAME='" . $_POST["username"] . "', " . "\n\t\t\t\t" . "PASSWORD='" . $_POST["password"] . "'," . "\n\t\t\t\t" . "LOGO='" . $_POST["logo"] . "'" . "\n\t\t\t" . "WHERE " . "\n\t\t\t\t" . "ID='1' ");
    session_start();
    session_regenerate_id();
    $_SESSION["loggedin"] = true;
    $_SESSION["name"] = $_POST["username"];
    header("Location: profile.php?m=" . $message);
}
$name = $row["NAME"];
$user = $row["USERNAME"];
$pass = $row["PASSWORD"];
$logo = $row["LOGO"];
include "includes/header.php";
echo " <!-- Begin Page Content -->\n        <div class=\"container-fluid\">\n\n";
if (isset($_GET["m"])) {
    echo $_GET["m"];
}
echo "          <h1 class=\"h3 mb-1 text-gray-800\">Update Login</h1>\n         \n          <!-- Content Row -->\n          <div class=\"row\">\n\n            <!-- First Column -->\n            <div class=\"col-lg-12\">\n\n              <!-- Custom codes -->\n                <div class=\"card border-left-primary shadow h-100 card shadow mb-4\">\n                <div class=\"card-header py-3\">\n                <h6 class=\"m-0 font-weight-bold text-primary\"><i class=\"fa fa-user\"></i> Update Profile</h6>\n                </div>\n                <div class=\"card-body\">\n                            <form method=\"post\">\n                            <div class=\"form-group \">\n                            <label class=\"control-label \" for=\"name\">\n                            <strong>Name</strong>\n                            </label>\n                            <div class=\"input-group\">\n";
echo "                            <input type=\"text\" class=\"form-control text-primary\" name=\"name\" value=\"" . $name . "\" placeholder=\"Enter Name\">" . "\n";
echo "                            </div>\n                            </div>\n                            <div class=\"form-group \">\n                            <label class=\"control-label \" for=\"username\">\n                            <strong>Username</strong>\n                            </label>\n                            <div class=\"input-group\">\n";
echo "                            <input type=\"text\" class=\"form-control text-primary\" name=\"username\" value=\"" . $user . "\" placeholder=\"Enter Username\">" . "\n";
echo "                            </div>\n                            </div>\n                            <div class=\"form-group \">\n                            <label class=\"control-label \" for=\"password\">\n                            <strong>Password</strong>\n                            </label>\n                            <div class=\"input-group\">\n";
echo "                            <input type=\"text\" class=\"form-control text-primary\" name=\"password\" value=\"" . $pass . "\" placeholder=\"Enter Password\">" . "\n";
echo "                            </div>\n                            </div>\n                            <div class=\"form-group \">\n                            <label class=\"control-label \" for=\"logo\">\n                            <strong>Logo</strong>\n                            </label>\n                            <div class=\"input-group\">\n";
echo "                            <input type=\"text\" class=\"form-control text-primary\" name=\"logo\" value=\"" . $logo . "\" placeholder=\"Enter Profile URL\">" . "\n";
echo "                            </div>\n                            </div>\n                            <div class=\"form-group\">\n                            <div>\n                        <button class=\"btn btn-success btn-icon-split\" name=\"submit\" type=\"submit\">\n                        <span class=\"icon text-white-50\"><i class=\"fas fa-check\"></i></span><span class=\"text\">Submit</span>\n                        </button>\n                            </div>\n                            </div>\n";
echo "                            <img type=\"image\" width=\"100px\" src=\"" . $logo . "\" alt=\"image\" /></div>" . "\n";
echo "                </div>\n                            </form>\n                </div>\n            </div>\n                </div>\n";
include "includes/footer.php";
require "includes/ans.php";
echo "</body>\n";

?>