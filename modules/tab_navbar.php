<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

$p = (isset($_GET['tab']) and strlen($_GET['tab']) >0) ? trim($_GET['tab']) : '';
$c1 = $c2 = $c3 = $c4 = '';

if (!$p) {
    $c1 = ' class="current"';
}
elseif ($p == 'posts') {
    $c2 = ' class="current"';
}
elseif ($p == 'friends') {
    $c3 = ' class="current"';
}
elseif ($p == 'followers') {
    $c4 = ' class="current"';
}
elseif ($p == 'new') {
    $c5 = ' class="current"';
}

$postsCount     = 0;;
$friendsCount   = 0;
$followersCount = 0;
?>
<ul class="subsubsub">
<li><a <?php echo $c1;?>href="./admin.php?page=fanfou-tools.php"><?php echo _f("Options");?></a> |</li>
<li><a <?php echo $c2;?>href="./admin.php?page=fanfou-tools.php&tab=posts"><?php printf(_f("Posts(%d)"), $postsCount);?></a> |</li>
<li><a <?php echo $c3;?>href="./admin.php?page=fanfou-tools.php&tab=friends"><?php printf(_f("Friends(%d)"), $friendsCount);?></a> |</li>
<!--<li><a <?php echo $c4;?>href="./admin.php?page=fanfou-tools.php&tab=followers"><?php printf(_f("Followers(%d)"), $followersCount);?></a> |</li>-->
<li><a <?php echo $c5;?>href="./admin.php?page=fanfou-tools.php&tab=newpost"><?php echo _f("Write New");?></a></li>
</ul>
<div style="clear: both"></div>

