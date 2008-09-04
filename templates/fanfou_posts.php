<?php
/**
 * Fanfou Posts Management Panel
 *
 * @package Fanfou
 * @subpackage Posts
 */

// Delete the fanfou status
if (isset($_GET['fanfou_action']) and 'delete' == $_GET['fanfou_action']) {
    $id  = (isset($_GET['id']) and is_numeric($_GET['id'])) ? intval($_GET['id']) : 0;
    $fid = isset($_GET['fid']) ? trim($_GET['fid']) : '';
    if (!$id or !$fid) {
        wp_die(__('Unknown action.'));
        return;
    }
    $fanfou->delete_post($id, $fid);
}

// define the columns to display, the syntax is 'internal name' => 'display name'
$posts_columns = array(
    'id'         => '<div style="text-align: center">' . _f('ID') . '</div>',
    'status'     => _f('Status'),
    'fanfou_id'  => '<div style="text-align: center">' . _f('Fanfou ID') . '</div>',
    'date'       => '<div style="text-align: center">' . _f('When') . '</div>',
);

// you can not edit these at the moment
?>

<div class="wrap">
<h2>Fanfou Tools v<?php echo FANFOU_TOOLS_VER;?> - <em style="color: gray; font-size: 18px;"><?php echo  _f('Last 20 Fanfou Status'); ?></em></h2>
<ul class="subsubsub">
    <li><a href="./admin.php?page=fanfou-tools.php&p=options">Fanfou Tools Options</a> |</li>
    <li><a class="current" href="./admin.php?page=fanfou-tools.php&p=posts">Fanfou Posts</a> |</li>
    <li><a href="./admin.php?page=fanfou-tools.php&p=friends">Fanfou Friends</a></li>
</ul>
<br style="clear:both;" />

<table class="widefat">
<thead>
<tr>

<?php
foreach($posts_columns as $column_display_name) {
    print "<th scope='col'>$column_display_name</th>\n";
}
?>
</tr>
</thead>
<tbody id="the-list">

<?php
// Load fanfou posts from database
$query  = "SELECT * FROM `$wpdb->fanfou` ";
$query .= "ORDER BY `fanfou_created_at` DESC LIMIT 20";
$posts  = $wpdb->get_results($query);

//print '<pre>';
//print_r($posts);

if ($posts) {
    foreach ($posts as $post) {
        $class = ('alternate' == $class) ? '' : 'alternate';
        print "
            <tr id='post-{$post->id}' class='$class'>\n";

        foreach ($posts_columns as $c_name => $c_display_name) {
            switch ($c_name) {
            case 'id': ?>
            <th scope="row" style="text-align: center"><?php echo $post->id; ?></th>
<?php
                break;

            case 'status': ?>
            <td>
                <span title="<?php echo $post->fanfou_text;?>"><?php echo $post->fanfou_text; ?></span><br/><br/>
                <span class="view"><a href="http://fanfou.com/statuses/<?php echo $post->fanfou_id; ?>" target="_blank"><?php _e('View', 'fanfou-tools'); ?></a> |</span>
                <span class="delete"><a href='./admin.php?page=fanfou-tools.php&p=posts&fanfou_action=delete&id=<?php echo $post->id;?>&fid=<?php echo $post->fanfou_id;?>'><?php echo _f('Delete');?></a></span>
            </td>
<?php
                break;

            case 'date': ?>
            <td><span class="datetime"><?php echo date('Y-m-d H:i:s', $post->fanfou_created_at); ?></span></td>
<?php
                break;

            case 'fanfou_id': ?>
			<th scope="row" style="text-align: center"><?php echo $post->fanfou_id; ?></th>
<?php
                break;

            default:
            }
        }
    }
}
else {
?>
  <tr>
    <td colspan="8"><?php _e('No posts found.') ?></td>
  </tr>
<?php } ?>
</tbody>
</table>
</div>
