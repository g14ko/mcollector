<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <?php include 'header.tpl';?>
</head>
<body>
<div id="blackout"></div>
<div id="wrapped">
    <div id="navigate"><?php echo $menu;?></div>
    <div id="aside"><?php echo $aside;?></div>
    <div id="main">
        <?php include 'js-vars.tpl';?>
        <div id="content"><?php echo $content;?></div>
    </div>
</div>
</body>
</html>