<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

$tabs = array(
    'options'   => _f("Options"),
    'posts'     => _f("Posts"),
    'friends'   => _f("Friends"),
//    'followers' => _f("Followers"),
    'newpost'   => _f("Write New")
);


$postsCount     = 0;;
$friendsCount   = 0;
$followersCount = 0;
?>
<ul class="subsubsub">
<?php
$current = (isset($_GET['tab']) and strlen($_GET['tab']) > 0) ? strtolower(trim($_GET['tab'])) : 'options';
$index   = 0;
foreach ($tabs as $tabkey => $tabname) {
    $class = ($current == $tabkey)  ? ' class="current"' : '';
    $link  = "./options-general.php?page=fanfou-tools.php&tab=$tabkey";
    $output = "<li><a {$class}href=\"$link\">$tabname</a>";
    if ($index < (count($tabs) - 1)) $output .= " |";
    $output .= "</li>\n";
    echo $output;
    $index++;
}
?>
</ul>
<div style="clear: both"></div>

