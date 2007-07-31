<?php
/**
Plugin Name: FanFou Tools
Plugin URI: http://www.phpvim.net/wordpress/fanfou-tools.html
Description: FanFou Tools for WordPress Blog...<a href="options-general.php?page=fanfou-tools.php">Configuration Page</a>.
Version: 1.00-stable
Author: Verdana Mu <verdana.cn@gmail.com>
Author URI: http://www.phpvim.net
License: LGPL
**/


// Copyright (c) 2007 Verdana Mu. All rights reserved.
//
// Released under the LGPL license
// http://www.gnu.org/licenses/lgpl.txt
//
// This is an add-on for WordPress
// http://wordpress.org
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************


define('FANFOU_TOOLS_VER',         '1.00-stable');

require_once ABSPATH . PLUGINDIR . '/fanfou-tools/Fanfou.php';
require_once ABSPATH . PLUGINDIR . '/fanfou-tools/FanfouPost.php';

$fanfou = new Fanfou();


load_plugin_textdomain('fanfou-tools', 'wp-content/plugins/fanfou-tools');


// {{{ fanfou_menu_items()

/**
 * fanfou_menu_items
 *
 * @access public
 * @return void
 */
function fanfou_menu_items() {
    if (current_user_can('manage_options')) {
        $optitle = __('Fanfou Tools', 'fanfou-tools');
        add_options_page(
            $optitle,
            $optitle,
            10,
            basename(__FILE__),
            'fanfou_options_form'
        );

        add_management_page(
            __('Manage Fanfou Posts', 'fanfou-tools'),
            __('Fanfou Posts', 'fanfou-tools'),
            10,
            basename(__FILE__),
            'fanfou_manage_posts');
    }

    if (current_user_can('publish_posts')) {
        $post_title = __('Write fanfou', 'fanfou-tools');
        add_submenu_page(
            'post-new.php',
            $post_title,
            $post_title,
            10,
            basename(__FILE__),
            'fanfou_write_post_form'
        );
    }
}
add_action('admin_menu', 'fanfou_menu_items');

// }}}


// {{{ fanfou_init()

/**
 * fanfou_init
 *
 * @access public
 * @return void
 */
function fanfou_init() {
    global $wpdb, $fanfou;
    $wpdb->fanfou = $wpdb->prefix . 'fanfou';
    if (isset($_GET['activate']) and $_GET['activate'] == 'true') {
        $tables = $wpdb->get_col('SHOW TABLES');
        if (!in_array($wpdb->fanfou, $tables)) {
            $fanfou->install_table();
        }

        $fanfou->install_options();
    }
    $fanfou->get_settings();

    if (($fanfou->last_download + $fanfou->download_interval) < time()) {
        add_action('shutdown', 'fanfou_update_posts');
    }

    if (is_admin()) {
		wp_enqueue_script('prototype');
	}
}
add_action('init', 'fanfou_init');

// }}}


// {{{ fanfou_head_admin()

/**
 * fanfou_head_admin
 *
 * @access public
 * @return void
 */
function fanfou_head_admin() {
    print("\n<script type=\"text/javascript\" src=\"".get_bloginfo('wpurl')."/index.php?fanfou_action=fanfou_js_admin\"></script>");
}
add_action('admin_head', 'fanfou_head_admin');

// }}}


// {{{ fanfou_request_handler()

/**
 * fanfou_request_handler
 *
 * @access public
 * @return void
 */
function fanfou_request_handler() {
    global $fanfou;

    if (!empty($_GET['fanfou_action'])) {
        switch ($_GET['fanfou_action']) {
        case 'fanfou_update_posts':
            fanfou_update_posts();
            header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fanfou-tools.php&fanfou-updated=true');
            exit;
            break;

        case 'fanfou_js_admin':
            header('Content-type: text/javascript');
?>
function TestLogin() {
    var username = encodeURIComponent($('ff_username').value);
    var password = encodeURIComponent($('ff_password').value);

    var result = $('fanfou_login_test_result');
    result.innerHTML = 'Testing...';

    var params = "fanfou_action=fanfou_login_test&ff_username=" + username + "&ff_password=" + password;
    var myAjax = new Ajax.Updater(
        result,
        "<?php bloginfo('wpurl'); ?>/wp-admin/options-general.php", {
            method: 'post',
            parameters: params,
            onComplete: TestLoginResult
        }
    );
}

function TestLoginResult() {
    Fat.fade_element('fanfou_login_test_result');
}

function DeleteFanfouStatus(id, fanfou_id, message) {
    if(!confirm(message)) {
        return false;
    }

    var params = "page=fanfou-tools.php&fanfou_action=fanfou_delete_post&id=" + id + "&fanfou_id=" + fanfou_id;
    var myAjax = new Ajax.Request(
        '<?php bloginfo('wpurl'); ?>/wp-admin/edit.php', {
            method: 'post',
            parameters: params,
            onLoading: function (transport) { showLoading(id, transport); },
            onComplete: function (transport) { showResponse(id, transport); }
        }
    );

    return false;
}

function showLoading(id, transport) {
    // Hidden the deleted row
    Fat.fade_element('post-' + id, null, 750);
}

function showResponse(id, transport) {
    // Hidden the deleted row
    Fat.fade_element('post-' + id, null, 750, '#FF3300');
    var func = function () { $('post-'+id).hide(); }
    setTimeout(func, 750);
    return false;
}

var seconds = <?php echo ($fanfou->last_download + $fanfou->download_interval - time()); ?>;
function timeLeftCounter() {
    seconds = seconds - 1;
    if (seconds < 0) {
         seconds = 0;
    }

    $('time_left').innerHTML = '( ' + seconds + ' seconds left )';
    window.setTimeout(timeLeftCounter, 1000);
}

<?php
            exit;
            break;

        case 'fanfou_delete_friend':
            $user_id = trim($_GET['user_id']);
            $fanfou->delete_friend($user_id);
            header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fanfou-tools.php&fanfou-delete-friend=true&user_id=' . $user_id);
            exit;
        }
    }

    if (!empty($_POST['fanfou_action'])) {
        switch ($_POST['fanfou_action']) {
        case 'fanfou_login_test':
            $username = trim(stripslashes($_POST['ff_username']));
            $password = trim(stripslashes($_POST['ff_password']));
            if ($fanfou->login($username, $password)) {
                exit(__('User successfully authenticated...', 'fanfou-tools'));
            }
            else {
                exit(__('Login failed. Please check your user name and password and try again...', 'fanfou-tools'));
            }
            break;

        case 'fanfou_post_admin':
            $text = isset($_POST['fanfou_status_text']) ? trim(stripslashes($_POST['fanfou_status_text'])) : null;
            if (!empty($text) and $fanfou->post($text)) {
                header('Location: '.get_bloginfo('wpurl').'/wp-admin/post-new.php?page=fanfou-tools.php&fanfou-posted=true');
            }
            else {
                wp_die(__('Oops, your fanfou status was not posted. Please check your username and password.', 'fanfou-tools'));
            }

            exit;
            break;

        case 'fanfou_delete_post':
            $id  = trim($_POST['id']);
            $fid = trim($_POST['fanfou_id']);
            $fanfou->delete_post($id, $fid);
            exit;
            break;
        }
    }
}
add_action('init', 'fanfou_request_handler', 10);

// }}}


// {{{ fanfou_post_form()

/**
 * fanfou_post_form
 *
 * @access public
 * @return void
 */
function fanfou_post_form() {
    $output = '';
    if (current_user_can('publish_posts')) {
        $output .= '
            <form action="'.get_bloginfo('wpurl').'/wp-admin/post-new.php?page=fanfou-tools.php" method="post" id="fanfou_post_form">
            <fieldset>
            <p><textarea type="text" cols="60" rows="5" maxlength="140" id="fanfou_status_text" name="fanfou_status_text" onkeyup="fanfouCharCount();"></textarea></p>
            <input type="hidden" name="fanfou_action" value="fanfou_post_admin" />
            <script type="text/javascript">
            //<![CDATA[
            function fanfouCharCount() {
                var count = document.getElementById("fanfou_status_text").value.length;
                if (count > 0) {
                    document.getElementById("fanfou_char_count").innerHTML = (140 - count) + "'.__(' characters remaining', 'fanfou-tools').'";
                }
                else {
                    document.getElementById("fanfou_char_count").innerHTML = "";
                }
            }
            setTimeout("fanfouCharCount();", 500);
            document.getElementById("fanfou_post_form").setAttribute("autocomplete", "off");
            //]]>
            </script>
            <p>
                <input type="submit" id="fanfou_submit" name="fanfou_submit" value="'.__('Post Fanfou Status!', 'fanfou-tools').'" />
                <span id="fanfou_char_count"></span>
            </p>
            <div class="clear"></div>
            </fieldset>
            </form>
        ';
    }
    return $output;
}

// }}}


// {{{ fanfou_write_post_form()

/**
 * fanfou_write_post_form
 *
 * @access public
 * @return void
 */
function fanfou_write_post_form() {
    global $fanfou;
    if ($_GET['fanfou-posted']) {
        print('
            <div id="message" class="updated fade">
                <p>'.__('Fanfou posted.', 'fanfou-tools').'</p>
            </div>
        ');
    }

    if (empty($fanfou->username) or empty($fanfou->password)) {
        print('
            <p>' . __('Please enter your <a href="http://fanfou.com">Fanfou</a> account information in your <a href="options-general.php?page=fanfou-tools.php">Fanfou Tools Options</a>.', 'fanfou-tools') . '</p>
        ');
    }

    print('
        <div class="wrap">
            <h2>' . __('Write Fanfou', 'fanfou-tools') . '</h2>
            <p>
                ' . __('This will create a new \'Fanfou\' status in <a href="http://fanfou.com">Fanfou</a> using the account information in your <a href="options-general.php?page=fanfou-tools.php">Fanfou Tools Options</a>.', 'fanfou-tools') . '<br/>
                ' . __('You can use the UBBCode <span style="color: red">[tiny][/tiny]</span> to automatically convert an URL into a Tiny URL.', 'fanfou-tools') . '
            </p>
            '.fanfou_post_form().'
        </div>
    ');
}

// }}}


// {{{ fanfou_options_form()

/**
 * fanfou_options_form
 *
 * @access public
 * @return void
 */
function fanfou_options_form() {
    global $fanfou;

    // Saving settings
    if (isset($_POST['fanfou_action']) and $_POST['fanfou_action'] == 'update_settings') {
        $fanfou->save_settings();
        print('
            <div id="message" class="updated fade">
                <p>'.__('Options updated...', 'fanfou-tools').'</p>
            </div>
        ');
    }

    // Update fanfou status
    if ( $_GET['fanfou-updated'] ) {
		print('
			<div id="message" class="updated fade">
				<p>'.__('Fanfou status updated.', 'fanfou-tools').'</p>
			</div>
		');
	}


    // Delete friends
    if ($_GET['fanfou-delete-friend'] and $_GET['user_id']) {
        print('
            <div id="message" class="updated fade">
                <p>'.__("Your Fanfou Friend <a href='http://fanfou.com/{$_GET['user_id']}' target='_blank'>{$_GET['user_id']}</a> has been deleted...", 'fanfou-tools').'</p>
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
            <p><a target="_blank" href="http://fanfou.com/friend.leave/'.$friend->id.'">取消关注</a> | <a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fanfou-tools.php&fanfou_action=fanfou_delete_friend&user_id='.$friend->id.'">刪除好友</a> | <a target="_blank" href="http://fanfou.com/privatemsg.create/'.$friend->id.'">发送私信</a></p>
        </li>
        ';

        $i ++;
        if ($i >= 10) break;
    }

    // Checked
    $fanfou_notify_fanfou      = ($fanfou->notify_fanfou == 1) ? ' checked="checked"' : '';
    $fanfou_notify_use_tinyurl = ($fanfou->notify_use_tinyurl == 1) ? ' checked="checked"' : '';

    print ('
    <div class="wrap">
        <h2>Fanfou Tools v' . FANFOU_TOOLS_VER . '</h2>
        <form id="fanfou-tools" name="fanfou-tools" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fanfou-tools.php" method="post">
            <input type="hidden" id="fanfou_action" name="fanfou_action" value="update_settings" />
            <p>' . __('For information and updates, please visit:', 'fanfou-tools') . '<br/>
            <a href="http://www.phpvim.net/wordpress/fanfou-tools.html" target="_blank">http://www.phpvim.net/wordpress/fanfou-tools.html</a></p>

            <fieldset class="options">
            <legend>' . __('The Login Information', 'fanfou-tools') . '</legend>
            <table style="padding-left: 20px" width="100%" border="0">
                <tr>
                    <td width="20%" align="right">'.__('FanFou ID or Email:', 'fanfou-tools').'</td>
                    <td width="80%"><input type="text" size="25" name="ff_username" id="ff_username" value="'.$fanfou->username.'" /></td>
                </tr>
                <tr>
                    <td align="right">'.__('FanFou Password:', 'fanfou-tools').'</td>
                    <td><input type="password" size="25" name="ff_password" id="ff_password" value="'.$fanfou->password.'" /></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="button" onclick="TestLogin(); return false;" value="'.__('Test Login', 'fanfou-tools').' &raquo;" id="login_test" name="login_test"/>
                        &nbsp;
                        <span id="fanfou_login_test_result"></span>
                    </td>
                </tr>
            </table>
            </fieldset>

            <fieldset class="options">
            <legend>' . __('Configuration', 'fanfou-tools') . '</legend>
            <div style="padding-left: 20px">
                <p>
                    <input type="checkbox" name="ff_notify_fanfou" id="ff_notify_fanfou" value="1" '.$fanfou_notify_fanfou.' />
                    ' . __('Create a fanfou status when you publish a new blog post?', 'fanfou-tools') . '
                </p>

                <p>
                    <input type="checkbox" name="ff_notify_use_tinyurl" id="ff_notify_use_tinyurl" value="1" '.$fanfou_notify_use_tinyurl.' />
                    ' . __('Shorten the long permalink into a Tiny URL?', 'fanfou-tools') . '
                    <br/>
                    <em style="font:normal 10px verdana; color: gray;">' . __('Using this option will slow down your blog post action.', 'fanfou-tools') . '</em>
                </p>

                <p>
                    ' . __('Format for notifier when publish a new blog post:', 'fanfou-tools') . '
                    <input type="text" name="ff_notify_format" id="ff_notify_format" value="'.$fanfou->notify_format.'" size="25" />
                </p>

                <p>
                    ' . __('Format for the datetime of fanfou status:', 'fanfou-tools') . '
                    <input type="text" name="ff_date_format" id="ff_date_format" value="'.$fanfou->date_format.'" size="25" />
                    <br/>
                    <em style="font:normal 10px verdana; color: gray;">' . __('The dates was formatted by <a target="_blank" href="http://www.php.net/manual/en/function.date.php"><strong>date()</strong></a>', 'fanfou-tools') . '</em>.
                </p>

                <p>
                    ' . __('Fanfou status to show in sidebar:', 'fanfou-tools') . '
                    <input type="text" name="ff_sidebar_status_num" id="ff_sidebar_status_num" value="'.$fanfou->sidebar_status_num.'" size="6" />
                </p>

                <p>
                    ' . __('Your Fanfou friends to show in sidebar:', 'fanfou-tools') . '
                    <input type="text" name="ff_sidebar_friends_num" id="ff_sidebar_friends_num" value="'.$fanfou->sidebar_friends_num.'" size="6" />
                </p>

                <p>
                    ' . __('Time interval for updating new posts:', 'fanfou-tools') . '
                    <input type="text" name="ff_download_interval" id="ff_download_interval" value="'.$fanfou->download_interval.'" size="6" /> seconds
                </p>

            </div>
            </fieldset>

            <div class="submit">
                <input type="submit" name="submit" value="'.__('Update Options', 'fanfou-tools').' &raquo;" />
            </div>
            </fieldset>
        </form>

        <fieldset class="options">
        <legend>' . __('Your Top 10 Newest Fanfou Friends', 'fanfou-tools') . '</legend>
            <ol style="list-style-type: none">
            '.$friends_htmlcode.'
            </ol>

            <a href="http://fanfou.com/friends" target="_blank">' . __('Find more friends...', 'fanfou-tools') . '</a>
        </fieldset>

        <fieldset class="options">
        <legend>' . __('Manage or Write Your Fanfou Status', 'fanfou-tools') . '</legend>
            <ul>
                <li><a href="'.get_bloginfo('wpurl').'/wp-admin/edit.php?page=fanfou-tools.php">'.__('Manage Your Fanfou Status', 'fanfou-tools').'</a>
                <li><a href="'.get_bloginfo('wpurl').'/wp-admin/post-new.php?page=fanfou-tools.php">'.__('Write a New Fanfou Status', 'fanfou-tools').'</a>
            </u>
        </fieldset>

        <form method="get" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" name="fanfou_update_posts">

            <fieldset class="options">
            <legend>' . __('Synchronous Your Fanfou Status', 'fanfou-tools') . '</legend>

                <p style="padding-left: 20px">
                ' . __('Use this button to manually update your fanfou status that show on your wordpress sidebar.', 'fanfou-tools') . '
                <br/><br/>
                ' . __('Last sync time:', 'fanfou-tools') . ' <strong>'.date('Y-m-d H:i:s', $fanfou->last_download).'</strong>
                <br/>
                ' . __('Next sync time:', 'fanfou-tools') . ' <strong>'.date('Y-m-d H:i:s', $fanfou->last_download + $fanfou->download_interval).' <span id="time_left"></span> </strong>
                </p>
                <script type="text/javascript">
                    window.setTimeout(timeLeftCounter, 1000);
                </script>
            </fieldset>

            <div class="submit">
                <input type="submit" name="submit" value="'.__('Synchronous Fanfou Status', 'fanfou-tools').' &raquo;" />
                <input type="hidden" name="fanfou_action" value="fanfou_update_posts" />
            </div>
        </form>
    </div>
    ');
}

// }}}


// {{{ fanfou_notify_post($post_id = 0)

/**
 * fanfou_notify_post
 *
 * @access public
 * @return void
 */
function fanfou_notify_post($post_id = 0) {
    global $fanfou;

    if ($fanfou->notify_fanfou == 0 or $post_id == 0 or get_post_meta($post_id, 'fanfou_marker', true) == '1') {
        return;
    }

    $post       = get_post($post_id);
    $permalink  = get_permalink($post_id);

    // Use TinyURL?
    if ($fanfou->notify_use_tinyurl == 1) {
        require_once ABSPATH . PLUGINDIR . '/fanfou-tools/TinyURL.php';
        $permalink = TinyURL::transform($permalink);
    }

    $text = sprintf(__($fanfou->notify_format, 'fanfou-tools'), $post->post_title, $permalink);
    $fanfou->post($text);

    add_post_meta($post_id, 'fanfou_marker', '1', true);
}
add_action('publish_post', 'fanfou_notify_post');

// }}}


// {{{ fanfou_manage_posts()

/**
 * fanfou_manage_posts
 *
 * @access public
 * @return void
 */
function fanfou_manage_posts() {
    global $fanfou, $wpdb;

    // define the columns to display, the syntax is 'internal name' => 'display name'
    $posts_columns = array(
        'id'         => '<div style="text-align: center">' . __('ID') . '</div>',
        'fanfou_id'  => '<div style="text-align: center">' . __('Fanfou ID') . '</div>',
        'date'       => __('When'),
        'status'     => __('Status'),
    );

    // you can not edit these at the moment
    $posts_columns['control_view']   = '';
    $posts_columns['control_delete'] = '';

    print '
<div class="wrap">
<h2>' . __('Last 20 Fanfou Status', 'fanfou-tools') . ' &nbsp; - &nbsp; <a href="./options-general.php?page=fanfou-tools.php">Fanfou Tools Options</a></h2>

<br style="clear:both;" />

<table class="widefat">
    <thead>
    <tr>
';

    foreach($posts_columns as $column_display_name) {
        print "        <th scope=\"col\">$column_display_name</th>\n";
    }

    print '
    </tr>
    </thead>

    <tbody id="the-list">
';

    // Load fanfou posts
    $query  = "SELECT * FROM $wpdb->fanfou ";
    $query .= "ORDER BY fanfou_created_at DESC ";
    $query .= "LIMIT 20";

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
            case 'id':
            ?>
            <th scope="row" style="text-align: center"><?php echo $post->id; ?></th>
            <?php
                break;

            case 'fanfou_id':
            ?>
            <th scope="row" style="text-align: center"><?php echo $post->fanfou_id; ?></th>
            <?php
                break;

            case 'date':
            ?>
            <td><span class="datetime"><?php echo date('Y-m-d H:i:s', $post->fanfou_created_at); ?></span></td>
            <?php
                break;

            case 'status':
            ?>
            <td><span title="<?php echo $post->fanfou_text;?>"><?php if (strlen($post->fanfou_text) <= 60) echo $post->fanfou_text; else echo substr($post->fanfou_text, 0, 60) . ' ...'; ?></span></td>
            <?php
                break;

            case 'control_view':
            ?>
            <td><a href="http://fanfou.com/statuses/<?php echo $post->fanfou_id; ?>" target="_blank"><?php _e('View', 'fanfou-tools'); ?></a></td>
            <?php
                break;

            case 'control_delete':
            ?>
            <td><a href='edit.php?page=fanfou-tools.php&amp;fanfou_action=fanfou_delete&amp;id=<?php echo $post->fanfou_id;?>' onclick="return DeleteFanfouStatus('<?php echo $post->id;?>', '<?php echo $post->fanfou_id;?>', 'js_encode(<?php _e("You are about to delete this status.\n'OK' to delete, 'Cancel' to stop.", 'fanfou-tools');?>)'); return false;"><?php _e('Delete', 'fanfou-tools');?></a></td>
            <?php
                break;

            default:
            ?>
            <td><?php do_action('manage_posts_custom_column', $column_name, $id); ?></td>
            <?php
                break;
            }
        }
    }
    }
    print '
    </tbody>
</table>

</div>
';
}

// }}}


// {{{ fanfou_update_posts()

/**
 * fanfou_update_posts
 *
 * @access public
 * @return void
 */
function fanfou_update_posts() {
    global $wpdb, $fanfou;
    if (empty($fanfou->username) or empty($fanfou->password)) {
        exit;
    }

    // Load user messages
    $hash = $fanfou->get_user_timeline($posts);
    if ((null == $hash) or ($hash == get_option('fanfou_update_hash'))) {
        return;
    }

    if (is_array($posts) and count($posts) > 0) {
        $fanfou_ids = array();
        foreach ($posts as $post) {
            $fanfou_ids[] = $wpdb->escape($post->id);
        }

        $existing_ids = $wpdb->get_col("
            SELECT fanfou_id FROM $wpdb->fanfou
            WHERE fanfou_id IN ('".implode("', '", $fanfou_ids)."')
            ");

        foreach ($posts as $post) {
            if (!$existing_ids or !in_array($post->id, $existing_ids)) {
                $status = &new FanfouPost($post->id, $post->text, $post->created_at);
                $status->insert();
            }
        }
    }

    update_option('fanfou_update_hash',   $hash);
    update_option('fanfou_last_download', time());
}

// }}}


// {{{ fanfou_get_posts($sort, $sort_order, $limit)

/**
 * fanfou_get_posts
 *
 * @param mixed $sort
 * @param mixed $sort_order
 * @param mixed $limit
 * @access public
 * @return void
 */
function fanfou_get_posts($sort, $sort_order, $limit) {
    global $wpdb;

    $query  = "SELECT fanfou_id, fanfou_text, fanfou_created_at ";
    $query .= "FROM $wpdb->fanfou ";
    $query .= "ORDER BY $sort $sort_order ";
    $query .= "LIMIT $limit";

    return $wpdb->get_results($query);
}

// }}}


// {{{ fanfou_list_posts($args)

/**
 * fanfou_list_posts
 *
 * @param string $args
 * @access public
 * @return void
 */
function fanfou_list_posts($args = '') {
    // Process the arguments for function: fanfou_list_posts($args)
    is_array($args) ? $foo = &$args : parse_str($args, $foo);

    // Default arguments
    $defaults = array(
        'show_date'     => 1,
        'title_li'      => 'Fanfou',
        'echo'          => 1,
        'sort_column'   => 'fanfou_created_at',
        'sort_order'    => 'DESC',
        'class'         => 'fanfou'
    );
    $foo = array_merge($defaults, $foo);

    // If no $date_format defined, using option value
    if (!isset($foo['date_format'])) {
        $date_format = strval(get_option('fanfou_date_format'));
        if (empty($date_format)) {
            $date_format = 'Y-m-d H:i';
        }
        $foo['date_format'] = $date_format;
    }

    // If no $limit value supplied from function arguments,
    // using the option value
    if (!isset($foo['limit']) or !is_numeric($foo['limit'])) {
        $limit = intval(get_option('fanfou_sidebar_status_num'));
        if (!$limit) {
            $limit = 10;
        }
        $foo['limit'] = $limit;
    }


    // Load and build the html code for fanfou posts
    $output = '';
    $posts  = fanfou_get_posts($foo['sort_column'], $foo['sort_order'], $foo['limit']);
    if (!empty($posts)) {
        if ($foo['title_li']) {
            $output .= "\n\n<li id=\"fanfou-tools\">\n";
            $output .= "<h2>$foo[title_li]</h2>\n";
            $output .= "<ul class=\"$foo[class]\">\n";
        }

        foreach ($posts as $post) {
            $time    = date($foo['date_format'], $post->fanfou_created_at);
            $text    = htmlspecialchars($post->fanfou_text);

            $output .= "    <li>\n";
            $output .= "        <a href=\"http://fanfou.com/statuses/{$post->fanfou_id}\">\n";
            $output .= "        $text\n";

            if ($foo['show_date']) {
                $output .= "        <br/>\n";
                $output .= "        <span class=\"time\">$time</span>\n";
            }

            $output .= "        </a>\n";
            $output .= "    </li>\n";
        }

        if ($foo['title_li'])
            $output .= "</ul>\n</li>\n";
    }

    apply_filters('fanfou_list_posts', $output);

    if ($foo['echo'])
        echo $output;
    else
        return $output;
}

// }}}


// {{{ fanfou_list_friends($args)

/**
 * fanfou_list_friends
 *
 * @param mixed $args
 * @access public
 * @return void
 */
function fanfou_list_friends($args = '') {
    is_array($args) ? $foo = &$args : parse_str($args, $foo);

    // Default arguments
    $defaults = array(
        'title_li' => 'Fanfou Friends',
        'echo'     => 1,
        'class'    => 'fanfou-friends'
    );
    $foo = array_merge($defaults, $foo);

    // If no $limit value supplied from function arguments,
    // using the option value
    if (!isset($foo['limit']) or !is_numeric($foo['limit'])) {
        $limit = intval(get_option('fanfou_sidebar_friends_num'));
        if (!$limit) {
            $limit = 20;
        }
        $foo['limit'] = $limit;
    }

    global $fanfou;

    // Load and build the html code for your fanfou friends
    $output  = '';
    $friends = (array) $fanfou->get_friends();
    $friends = array_slice($friends, 0, $foo['limit']);
    if (!empty($friends)) {
        if ($foo['title_li']) {
            $output .= "\n\n<li id=\"fanfou-friends\">\n";
            $output .= "<h2>$foo[title_li]</h2>\n";
            $output .= "<ul class=\"$foo[class]\">\n";
        }

        foreach ($friends as $friend) {
            $output .= "    <li>\n";
            $output .= "        <a href=\"$friend->url\" title=\"$friend->screen_name\">";
            $output .= "<img alt=\"$friend->screen_name\" src=\"$friend->profile_image_url\" />\n";
            $output .= "        </a>\n";
            $output .= "    </li>\n";
        }

        if ($foo['title_li'])
            $output .= "</ul>\n</li>\n";
    }

    apply_filters('fanfou_list_friends', $output);

    if ($foo['echo'])
        echo $output;
    else
        return $output;

}

// }}}


// {{{ fanfou_init_widget()

/**
 * fanfou_init_widget
 * This gets called at the plugins_loaded action
 *
 * @access public
 * @return void
 */
function fanfou_init_widget() {
    // Check for the required API functions
    if (!function_exists('register_sidebar_widget') or !function_exists('register_widget_control')) {
        return;
    }


    // {{{ wp_widget_fanfou($args)

    /**
     * wp_widget_fanfou
     *
     * @access public
     * @return void
     */
    function wp_widget_fanfou($args) {
        extract($args);
        $options = get_option('widget_fanfou');
        $title   = empty($options['status_title']) ? __('Fanfou Tools' ) : $options['status_title'];

        $only_onhome = (boolean) $options['status_only_onhome'];
        if ($only_onhome and !is_home()) {
            return;
        }

        $output  = fanfou_list_posts('title_li=&echo=0');
        if (!empty($output)) {
            echo $before_widget . "\n";
            echo $before_title, $title, $after_title . "\n";
            echo "<ul>\n";
            echo $output . "\n";
            echo "</ul>\n";
            echo $after_widget. "\n";
        }
    }

    // }}}


    // {{{ wp_widget_fanfou_friends($args)

    /**
     * wp_widget_fanfou_friends
     *
     * @param mixed $args
     * @access public
     * @return void
     */
    function wp_widget_fanfou_friends($args) {
        extract($args);
        $options = get_option('widget_fanfou');
        $title   = empty($options['friends_title']) ? __('Fanfou Friends' ) : $options['friends_title'];

        $only_onhome = (boolean) $options['friends_only_onhome'];
        if ($only_onhome and !is_home()) {
            return;
        }

        $output  = fanfou_list_friends('title_li=&echo=0');
        if (!empty($output)) {
            echo $before_widget . "\n";
            echo $before_title, $title, $after_title . "\n";
            echo "<ul>\n";
            echo $output . "\n";
            echo "</ul>\n";
            echo $after_widget. "\n";
        }
    }

    // }}}


    // {{{ wp_widget_fanfou_control()

    /**
     * wp_widget_fanfou_control
     *
     * This prints the widget
     *
     * @param mixed $args
     * @access public
     * @return void
     */
    function wp_widget_fanfou_control() {
        print __("<p>You can config <strong>Fanfou Tools</strong> from: \n<br/><br/>\n", 'fanfou-tools');
        print "<a href='".get_bloginfo('wpurl')."/wp-admin/options-general.php?page=fanfou-tools.php'>Fanfou Tools Options</a></p>\n";
        print "<br/><br/>\n";

        $options = $newoptions = get_option('widget_fanfou');
        if ($_POST["fanfou-submit"] ) {
            $newoptions['status_title']       = strip_tags(stripslashes($_POST['status-title']));
            $newoptions['status_only_onhome'] = isset($_POST['status-only-onhome']);
        }
        if ($options != $newoptions ) {
            $options = $newoptions;
            update_option('widget_fanfou', $options);
        }

        $onhome = $options['status_only_onhome'] ? 'checked="checked" ' : '';
        $title  = attribute_escape($options['status_title']);
?>
        <p><label for="status-title"><?php _e('Title:', 'fanfou-tools'); ?> <input style="width: 250px;" id="status-title" name="status-title" type="text" value="<?php echo $title; ?>" /></label></p>
        <p style="text-align: right; margin-right: 40px;"><label for="status-only-onhome"><?php _e('Only show on homepage', 'fanfou-tools'); ?> <input id="status-only-onhome" name="status-only-onhome" type="checkbox" value="1" <?php echo $onhome; ?>/></label></p>
        <input type="hidden" id="fanfou-submit" name="fanfou-submit" value="1" />
<?php

    }

    // }}}


    // {{{ wp_widget_fanfou_friends_control

    /**
     * wp_widget_fanfou_friends_control
     *
     * @access public
     * @return void
     */
    function wp_widget_fanfou_friends_control() {
        $options = $newoptions = get_option('widget_fanfou');
        if ($_POST["fanfou-submit"] ) {
            $newoptions['friends_title']       = strip_tags(stripslashes($_POST['friends-title']));
            $newoptions['friends_only_onhome'] = isset($_POST['friends-only-onhome']);
        }
        if ($options != $newoptions ) {
            $options = $newoptions;
            update_option('widget_fanfou', $options);
        }

        $onhome = $options['friends_only_onhome'] ? 'checked="checked" ' : '';
        $title  = attribute_escape($options['friends_title']);
?>
        <br/><br/>
        <p><label for="friends-title"><?php _e('Title:', 'fanfou-tools'); ?> <input style="width: 250px;" id="friends-title" name="friends-title" type="text" value="<?php echo $title; ?>" /></label></p>
        <p style="text-align: right; margin-right: 40px;"><label for="friends-only-onhome"><?php _e('Only show on homepage', 'fanfou-tools'); ?> <input id="friends-only-onhome" name="friends-only-onhome" type="checkbox" value="1" <?php echo $onhome;?>/></label></p>
        <input type="hidden" id="fanfou-submit" name="fanfou-submit" value="1" />
<?php
    }

    // }}}


    // Tell Dynamic Sidebar about our new widget and its control
    register_sidebar_widget('Fanfou Tools',   'wp_widget_fanfou');
    register_sidebar_widget('Fanfou Friends', 'wp_widget_fanfou_friends');
    register_widget_control('Fanfou Tools',   'wp_widget_fanfou_control');
    register_widget_control('Fanfou Friends', 'wp_widget_fanfou_friends_control');
}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('plugins_loaded', 'fanfou_init_widget');

// }}}


/* vim: set expandtab tabstop=4 shiftwidth=4: */

