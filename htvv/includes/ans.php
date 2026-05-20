<!-- Developer eonbry -->
<?php


echo " <script> \n\$(document).ready(function () {\n    \$('#flash-msg').delay(3000).fadeOut('slow');\n});\n  </script>\n <script>\n\$('#confirm-delete').on('show.bs.modal', function(e) {\n    \$(this).find('.btn-ok').attr('href', \$(e.relatedTarget).data('href'));\n});\n    </script>\n";

?>