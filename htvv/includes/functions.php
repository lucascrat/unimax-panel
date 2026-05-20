<?php
// Developer eonbry


echo "<center></center>\n\n  <!-- Custom scripts for all pages-->\n  <script src=\"js/sb-admin.min.js\"></script>\n  <script src=\"js/jquery.datetimepicker.js\"></script>\n \n<script>\n\nvar today = new Date();\nvar date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();\nvar time = today.getHours() + \":\" + today.getMinutes() + \":\" + today.getSeconds();\nvar dateTime = date+' '+time;\n\n\$('#datetimepicker').datetimepicker({\n\t//value:dateTime, \n\tstep:30,\n\tformat:'Y-m-d H:i:s',\n\t});\n\t\n\n\$(document).ready(function () {\n    \$(\"#flash-msg\").delay(3000).fadeOut(\"slow\");\n});\n\n</script>";

?>