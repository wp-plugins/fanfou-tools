<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

// Delete friends
if (isset($_GET['fanfou_action']) and 'delete' == $_GET['fanfou_action']) {
    $user_id = trim($_GET['user_id']);
    if (!$user_id) {
        wp_die(__('Unknown action.'));
        return;
    }
    $fanfou->delete_friend($user_id);
}

// Load fanfou friends
$friends_htmlcode = '';
$friends          = (array) $fanfou->get_friends();
$i = 0;
foreach($friends as $friend) {
    $friends_htmlcode .= '
        <li>
        <a target="_blank" title="'.$friend->screen_name.'" href="'.$friend->url.'"><img alt="'.$friend->screen_name.'" src="'.$friend->profile_image_url.'" style="float: left; margin-right: 10px;"/></a>
        <a target="_blank" href="'.$friend->url.'">'.$friend->screen_name.'</a>
        <p><a target="_blank" href="http://fanfou.com/friend.leave/'.$friend->id.'">取消关注</a> | <a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fanfou-tools.php&fanfou_action=fanfou_delete_friend&user_id='.$friend->id.'">刪除好友</a> | <a target="_blank" href="http://fanfou.com/privatemsg.create/'.$friend->id.'">发送私信</a></p>
        </li>
        ';

    $i ++;
    if ($i >= 10) break;
}

?>

<div class="wrap">
<h2>Fanfou Tools v<?php echo FANFOU_TOOLS_VER;?> - <em style="color: gray; font-size: 18px;"><?php echo _f('Friends'); ?></em></h2>
<?php include_once('tab_navbar.php');?>

<p><b>NOT IMPLEMENTED</b></p>
</div>

