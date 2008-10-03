<?php

// Delete friends
if ($_GET['fanfou-delete-friend'] and $_GET['user_id']) {
    print('
        <div id="message" class="updated fade">
        <p>'._f("Your Fanfou Friend <a href='http://fanfou.com/{$_GET['user_id']}' target='_blank'>{$_GET['user_id']}</a> has been deleted...").'</p>
        </div>
        ');
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
        <p><a target="_blank" href="http://fanfou.com/friend.leave/'.$friend->id.'">取消关注</a> | <a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=fanfou-tools.php&fanfou_action=fanfou_delete_friend&user_id='.$friend->id.'">刪除好友</a> | <a target="_blank" href="http://fanfou.com/privatemsg.create/'.$friend->id.'">发送私信</a></p>
        </li>
        ';

    $i ++;
    if ($i >= 10) break;
}

echo "Incoming soon...";

/* vim: set expandtab tabstop=4 shiftwidth=4: */

