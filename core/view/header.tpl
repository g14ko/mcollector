<meta content="utf-8" http-equiv="encoding">
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<title><?php echo $title;?></title>
<link rel="icon" type="image/png" href="http://mcollector/img/server.png">
<?if ($styles) :?><?php foreach ($styles as $style) : ?>
<link rel="stylesheet" type="text/css" href="<?php echo $style;?>">
<?php endforeach; ?><?php endif; ?>
<?if ($scripts) :?><?php foreach ($scripts as $script) : ?>
<script type="text/javascript" src="<?php echo $script;?>"></script>
<?php endforeach; ?><?php endif; ?>