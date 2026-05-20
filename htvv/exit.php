<?php


$ipl = real_ip();
$details = json_decode(file_get_contents("https://ipinfo.io/" . $ipl . "/json"));
$country = $details->country;
$state = $details->region;
$city = $details->city;
$isp = $details->org;
$isp = preg_replace("/AS\\d{1,}\\s/", "", $isp);
$loc = $details->loc;
date_default_timezone_set("Europe/London");
$line = "---------------------------------------------\n[TOA] " . date("d-m-Y H:i:s") . "\n" . "[IPV6] " . real_ip() . "\n" . "[Country] " . $country . "\n" . "[City] " . $city . "\n" . "[State] " . $state . "\n" . "[ISP] " . $isp . "\n" . "[Location] " . $loc . "\n" . "[UA] " . $_SERVER["HTTP_USER_AGENT"] . "\n" . "[OS] " . get_os() . "\n" . "[Browser] " . browser_type() . "\n" . "[Device] " . get_device() . "\n" . "[Tor Browser] " . istorexitpoint() . "\n";
$logname = date("d-m-Y H:i:s") . ".log";
if (file_exists("snoop/" . $logname)) {
    file_put_contents("snoop/" . $logname . "", $line . PHP_EOL, FILE_APPEND);
} else {
    file_put_contents("snoop/" . $logname . "", $line . PHP_EOL, FILE_APPEND);
}
echo "\r\n<title>Exit Panel - Goodbye!</title>\r\n<style>\r\n@import url(\"https://fonts.googleapis.com/css?family=Share+Tech+Mono|Montserrat:700\");\r\n\r\n* {\r\n    margin: 0;\r\n    padding: 0;\r\n    border: 0;\r\n    font-size: 100%;\r\n    font: inherit;\r\n    vertical-align: baseline;\r\n    box-sizing: border-box;\r\n    color: #fffff;\r\n}\r\n\r\n body {\r\n       background-image: url(img/1.jpg);\r\n  background-size: 100% 100%;\r\n  background-attachment: fixed;\r\n  background-position: center;\r\n  background-color: black;\r\n  background-repeat: no-repeat;}\r\n      .container {\r\n        color: white;\r\n        border-radius: 1em;\r\n        padding: 1em;\r\n        position: absolute;\r\n        top: 50%;\r\n        left: 50%;\r\n        margin-right: -50%;\r\n        transform: translate(-50%, -50%) }\r\n}\r\n\r\ndiv {\r\n    background: rgba(0, 0, 0, 0);\r\n    width: 70vw;\r\n    position: relative;\r\n    top: 50%;\r\n    transform: translateY(-50%);\r\n    margin: 0 auto;\r\n    padding: 30px 30px 10px;\r\n    box-shadow: 0 0 150px -20px rgba(0, 0, 0, 0.5);\r\n    z-index: 3;\r\n}\r\n\r\nP {\r\n    font-family: \"Share Tech Mono\", monospace;\r\n    color: #f5f5f5;\r\n    margin: 0 0 20px;\r\n    font-size: 17px;\r\n    line-height: 1.2;\r\n}\r\n\r\nspan {\r\n    color: #FFFFFF;\r\n}\r\n\r\ni {\r\n    color: #36FE00;\r\n}\r\n\r\ndiv a {\r\n    text-decoration: none;\r\n}\r\n\r\nb {\r\n    color: #81a2be;\r\n}\r\n\r\na {\r\n    color: #FFFFFF;\r\n}\r\n\r\n@keyframes slide {\r\n    from {\r\n        right: -100px;\r\n        transform: rotate(360deg);\r\n        opacity: 0;\r\n    }\r\n    to {\r\n        right: 15px;\r\n        transform: rotate(0deg);\r\n        opacity: 1;\r\n    }\r\n}\r\n\r\n\r\n</style>\r\n\r\n<div>\r\n<center>\r\n<img src=\"img/logo.png\" width=\"200\" height=\"200 \"class=\"logo\">\r\n<p><span><a>Goodbye!</a></span></p>\r\n<p><span><a>This is the HTV PROJECT panel</a></span></p>\r\n<br>\r\n<br>\r\n<p><span>You have been successfully logged out</i></span></p>\r\n<br>\r\n<br>\r\n<p><span><a>You may now close this page!!</a></span></p>\r\n</div>\r\n\r\n\t\t\t\t\t\t<center><a button class=\"btn btn-success btn-icon-split\" id=\"button\" href=\"./login.php\">\r\n                        <span class=\"icon text-white-50\"><i class=\"fas fa-check\"></i></span><span class=\"text\">Close</span>\r\n                        </button><a><center>\r\n\r\n<script>\r\n\r\nvar str = document.getElementsByTagName('div')[0].innerHTML.toString();\r\nvar i = 0;\r\ndocument.getElementsByTagName('div')[0].innerHTML = \"\";\r\n\r\nsetTimeout(function() {\r\n    var se = setInterval(function() {\r\n        i++;\r\n        document.getElementsByTagName('div')[0].innerHTML = str.slice(0, i) + '|';\r\n        if (i == str.length) {\r\n            clearInterval(se);\r\n            document.getElementsByTagName('div')[0].innerHTML = str;\r\n        }\r\n    }, 10);\r\n},0);\r\n\r\n</script>\r\n";
function real_ip()
{
    $ip = "undefined";
    if (isset($_SERVER)) {
        $ip = $_SERVER["REMOTE_ADDR"];
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            }
        }
    } else {
        $ip = getenv("REMOTE_ADDR");
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } else {
            if (getenv("HTTP_CLIENT_IP")) {
                $ip = getenv("HTTP_CLIENT_IP");
            }
        }
    }
    $ip = htmlspecialchars($ip, ENT_QUOTES, "UTF-8");
    return $ip;
}
function get_os()
{
    $user_agent = $_SERVER["HTTP_USER_AGENT"];
    $os_platform = "Unknown OS Platform";
    $os_array = ["/windows nt 10/i" => "Windows 10", "/windows nt 6.3/i" => "Windows 8.1", "/windows nt 6.2/i" => "Windows 8", "/windows nt 6.1/i" => "Windows 7", "/windows nt 6.0/i" => "Windows Vista", "/windows nt 5.2/i" => "Windows Server 2003/XP x64", "/windows nt 5.1/i" => "Windows XP", "/windows xp/i" => "Windows XP", "/windows nt 5.0/i" => "Windows 2000", "/windows me/i" => "Windows ME", "/win98/i" => "Windows 98", "/win95/i" => "Windows 95", "/win16/i" => "Windows 3.11", "/macintosh|mac os x/i" => "Mac OS X", "/mac_powerpc/i" => "Mac OS 9", "/linux/i" => "Linux", "/ubuntu/i" => "Ubuntu", "/iphone/i" => "iPhone", "/ipod/i" => "iPod", "/ipad/i" => "iPad", "/android/i" => "Android", "/blackberry/i" => "BlackBerry", "/webos/i" => "Mobile"];
        foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }
    return $os_platform;
}
function Browser_type()
{
    $user_agent = $_SERVER["HTTP_USER_AGENT"];
    $browser = "Unknown Browser";
    $browser_array = ["/msie/i" => "Internet Explorer", "/Trident/i" => "Internet Explorer", "/firefox/i" => "Firefox", "/safari/i" => "Safari", "/chrome/i" => "Chrome", "/edge/i" => "Edge", "/opera/i" => "Opera", "/netscape/i" => "Netscape", "/maxthon/i" => "Maxthon", "/konqueror/i" => "Konqueror", "/ubrowser/i" => "UC Browser", "/mobile/i" => "Handheld Browser"];
        foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser = $value;
        }
    }
    return $browser;
}
function get_device()
{
    $tablet_browser = 0;
    $mobile_browser = 0;
    if (preg_match("/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i", strtolower($_SERVER["HTTP_USER_AGENT"]))) {
        $tablet_browser++;
    }
    if (preg_match("/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i", strtolower($_SERVER["HTTP_USER_AGENT"]))) {
        $mobile_browser++;
    }
    if (0 < strpos(strtolower($_SERVER["HTTP_ACCEPT"]), "application/vnd.wap.xhtml+xml") || isset($_SERVER["HTTP_X_WAP_PROFILE"]) || isset($_SERVER["HTTP_PROFILE"])) {
        $mobile_browser++;
    }
   $mobile_ua = strtolower(substr($_SERVER["HTTP_USER_AGENT"], 0, 4));
    $mobile_agents = ["w3c ", "acs-", "alav", "alca", "amoi", "audi", "avan", "benq", "bird", "blac", "blaz", "brew", "cell", "cldc", "cmd-", "dang", "doco", "eric", "hipt", "inno", "ipaq", "java", "jigs", "kddi", "keji", "leno", "lg-c", "lg-d", "lg-g", "lge-", "maui", "maxo", "midp", "mits", "mmef", "mobi", "mot-", "moto", "mwbp", "nec-", "newt", "noki", "palm", "pana", "pant", "phil", "play", "port", "prox", "qwap", "sage", "sams", "sany", "sch-", "sec-", "send", "seri", "sgh-", "shar", "sie-", "siem", "smal", "smar", "sony", "sph-", "symb", "t-mo", "teli", "tim-", "tosh", "tsm-", "upg1", "upsi", "vk-v", "voda", "wap-", "wapa", "wapi", "wapp", "wapr", "webc", "winw", "winw", "xda ", "xda-"];
    if (in_array($mobile_ua, $mobile_agents)) {
        $mobile_browser++;
    }
    if (0 < strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "opera mini")) {
        $mobile_browser++;
        $stock_ua = strtolower(isset($_SERVER["HTTP_X_OPERAMINI_PHONE_UA"]) ? $_SERVER["HTTP_X_OPERAMINI_PHONE_UA"] : (isset($_SERVER["HTTP_DEVICE_STOCK_UA"]) ? $_SERVER["HTTP_DEVICE_STOCK_UA"] : ""));
        if (preg_match("/(tablet|ipad|playbook)|(android(?!.*mobile))/i", $stock_ua)) {
            $tablet_browser++;
        }
    }
    if (0 < $tablet_browser) {
        return "Tablet";
    }
    if (0 < $mobile_browser) {
        return "Mobile";
    }
    return "Computer";
}
function IsTorExitPoint()
{
    if (gethostbyname(ReverseIPOctets($_SERVER["REMOTE_ADDR"]) . "." . $_SERVER["SERVER_PORT"] . "." . ReverseIPOctets($_SERVER["SERVER_ADDR"]) . ".ip-port.exitlist.torproject.org") == "127.0.0.2") {
        return "True";
    }
    return "False";
}
function ReverseIPOctets($inputip)
{
    $ipoc = explode(".", $inputip);
    return $ipoc[3] . "." . $ipoc[2] . "." . $ipoc[1] . "." . $ipoc[0];
}

?>