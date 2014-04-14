<?if ($jsVars) :?>
    <script>
        $.loadGlobals(<?php echo json_encode($jsVars);?>);
    </script>
<?php endif; ?>