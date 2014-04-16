<?if (isset($code)) :?>
<link rel="stylesheet" type="text/css" href="../../css/plugins/jNotify.css">
<script type="text/javascript" src="../../js/libs/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="../../js/plugins/jNotify.js"></script>
<script>
    $(document).ready(function () {
        jError('<?php echo $message;?>', {
            autoHide: false,
            onClosed: function () {
                location.reload();
            }
        });
    });
</script>
<?php endif; ?>