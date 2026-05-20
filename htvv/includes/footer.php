<?php
?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <footer class="sticky-footer mt-auto">
        <div class="container">
            <div class="copyright text-center small">
                <span><a href="https://t.me/maxrebrandoficial/" target="_blank">&#169; Developer: MaxRebrands <sup>Painel UniMax</sup></a></span>
            </div>
        </div>
    </footer>

        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
<script src="js/jquery.datetimepicker.js"></script>
<script>
var today = new Date();
var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
var time = today.getHours() + ':' + today.getMinutes() + ':' + today.getSeconds();
var dateTime = date+' '+time;

if ($('#datetimepicker').length) {
    $('#datetimepicker').datetimepicker({
        step: 30,
        format: 'Y-m-d H:i:s'
    });
}

$(document).ready(function () {
    $('#flash-msg').delay(3000).fadeOut('slow');
    $('#sidebarToggleTop').on('click', function () {
        $('#accordionSidebar').toggleClass('mobile-open');
    });
});
</script>
