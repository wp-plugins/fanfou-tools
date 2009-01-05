<?php
/**
Plugin Name: FanFou Tools
Plugin URI: http://www.phpvim.net/wordpress/fanfou-tools.html
Description: FanFou Tools for WordPress Blog...
Version: 1.3.1
Author: Verdana Mu
Author URI: http://www.phpvim.net
License: LGPL
**/

define('FANFOU_TOOLS_VER', '1.3.1');
define('FANFOU_PATH', ABSPATH . PLUGINDIR . '/fanfou-tools');

require_once FANFOU_PATH . '/class-fanfou.php';
require_once FANFOU_PATH . '/class-post.php';
$fanfou = new Fanfou();


// {{{ function _f($key)
/**
 * Translates $message using the `fanfou-tools` locale for $domain. Wrap text
 * strings that you are going to use in calculations with this function.
 *
 * @param mixed $key
 * @access protected
 * @return void
 */
function _f($key)
{
    if (!function_exists('__')) {
        return $key;
    }
    return __($key, 'fanfou-tools');
}
// }}}


// {{{ fanfou_add_menu
/**
 * fanfou_add_menu
 *
 * @access public
 * @return void
 */
function fanfou_add_menu()
{
    // Options page
    $title = _f("Fanfou Tools");
    add_options_page($title, $title, 10, basename(__FILE__), 'fanfou_admin');

    if (current_user_can('manage_options')) {
        add_filter('plugin_action_links', 'plugin_action_links', 10, 2);
    }
}
add_action('admin_menu', 'fanfou_add_menu');
// }}}


// {{{ fanfou_init
/**
 * fanfou_init
 *
 * @access public
 * @return void
 */
function fanfou_init()
{
    global $wpdb, $fanfou;
    $wpdb->fanfou = $wpdb->prefix . 'fanfou';
    if (isset($_GET['activate']) and $_GET['activate'] == 'true') {
        $fanfou->install_table();
        $fanfou->install_options();
    }
    $fanfou->get_settings();

    if (($fanfou->last_download + $fanfou->download_interval) < time()) {
        add_action('shutdown', 'fanfou_update_posts');
    }

    if (is_admin()) {
        wp_enqueue_script('prototype');
    }

    // Using our own locale
    $custom_locale = get_option('fanfou_locale');
    if (!$custom_locale or $custom_locale == 'default') {
        $custom_locale = WPLANG;
    }
    $GLOBALS['locale'] = $custom_locale;
    load_plugin_textdomain('fanfou-tools', 'wp-content/plugins/fanfou-tools');

    // Reset locale
    $GLOBALS['locale'] = WPLANG;
}
add_action('init', 'fanfou_init');
// }}}


// {{{ fanfou_head_admin
/**
 * fanfou_head_admin
 *
 * @access public
 * @return void
 */
function fanfou_head_admin()
{
    print("\n<script type=\"text/javascript\" src=\"".get_bloginfo('wpurl')."/index.php?fanfou_action=fanfou_js_code\"></script>");
}
add_action('admin_head', 'fanfou_head_admin');
// }}}


/**
 * plugin_action_links
 * Handler for the 'plugin_action_links' hook. Adds a "Settings" link to this plugin's entry
 * on the plugin list.
 *
 * @param mixed $links
 * @param mixed $file
 * @access public
 * @return void
 */
function plugin_action_links($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $links[] = "<a href='./options-general.php?page=fanfou-tools.php'>" . __('Settings') . "</a>";
    }
    return $links;
}


// {{{ fanfou_request_handler
/**
 * fanfou_request_handler
 *
 * @access public
 * @return void
 */
function fanfou_request_handler()
{
    global $fanfou;

    if ($_GET['fanfou_action']) {
        switch ($_GET['fanfou_action']) {
        case 'update_posts':
            remove_action('shutdown', 'fanfou_update_posts');
            fanfou_update_posts();
            header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fanfou-tools.php&tab=posts');
            exit;
            break;

        case 'synchronize_posts':
            remove_action('shutdown', 'fanfou_update_posts');
            fanfou_update_posts(true);
            header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fanfou-tools.php&tab=posts');
            exit;
            break;

        case 'fanfou_js_code':
            header('Content-type: text/javascript');
            require_once FANFOU_PATH . '/modules/fanfou_js_admin.php';
            exit;
            break;
        }
    }

    if ($_POST['fanfou_action']) {
        switch ($_POST['fanfou_action']) {
        case 'fanfou_login_test':
            $username = trim(stripslashes($_POST['ff_username']));
            $password = trim(stripslashes($_POST['ff_password']));
            if ($fanfou->login($username, $password)) {
                exit(_f('<em style="color: green">User successfully authenticated...</em>'));
            }
            else {
                exit(_f('<em style="color: red">Login failed. Please check your user name and password and try again...</em>'));
            }
            break;

        case 'post_status':
            $text = isset($_POST['fanfou_status_text']) ? trim(stripslashes($_POST['fanfou_status_text'])) : null;
            if (strlen($text) and $fanfou->post($text)) {
                header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fanfou-tools.php&tab=newpost&fanfou-posted=true');;
            }
            else {
                wp_die(_f('Oops, your fanfou status was not posted. Please check your username and password.'));
            }
            break;;
        }
    }
}
add_action('init', 'fanfou_request_handler', 10);
// }}}


// {{{ fanfou_admin
/**
 * fanfou_admin
 *
 * @access public
 * @return void
 */
function fanfou_admin()
{
    global $wpdb, $fanfou;

    $tabPage = !isset($_GET['tab']) ? 'options' : trim(strtolower($_GET['tab']));

    // Check and include module file
    $module = FANFOU_PATH . '/modules/fanfou_' . $tabPage . '.php';
    if (!file_exists($module)) {
        $module = FANFOU_PATH . '/modules/fanfou_options.php';
    }
    include_once $module;
}
// }}}


// {{{ fanfou_notify_post
/**
 * fanfou_notify_post
 *
 * @access public
 * @return void
 */
function fanfou_notify_post($post_id = 0)
{
    global $fanfou;
    if ($fanfou->notify_fanfou == 0 or $post_id == 0 or get_post_meta($post_id, 'fanfou_marker', true) == '1') {
        return;
    }

    // if time when created equals time when modified it is a new post
    // otherwise the author has edited/modified it
    $post = get_post($post_id);
    if ($post->post_date != $post->post_modified) {
        // if author modified or edited an old post, skip it
        return;
    }

    // skip some categories
    if ($fanfou->notify_exclude_categories) {
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $foo = explode(',', $fanfou->notify_exclude_categories);
            foreach ($categories as $category) {
                if (in_array($category->term_id, $foo)) {
                    return;
                }
            }
        }
    }

    $foo = $fanfou->notify_format;
    if (!$foo) return;

    if (false !== strpos($foo, '%blogname%')) {
        $foo = str_replace('%blogname%', get_bloginfo('name'), $foo);
    }
    if (false !== strpos($foo, '%permalink%')) {
        $permalink = get_permalink($post_id);

        // Use TinyURL?
        if ($fanfou->notify_use_tinyurl) {
            require_once FANFOU_PATH . '/TinyURL.php';
            $permalink = TinyURL::transform($permalink);
        }

        $foo = str_replace('%permalink%', $permalink, $foo);
    }
    if (false !== strpos($foo, '%postname%')) {
        $post = get_post($post_id);
        $foo = str_replace('%postname%', $post->post_title, $foo);
    }

    $fanfou->post($foo);
    add_post_meta($post_id, 'fanfou_marker', '1', true);
}
add_action('publish_post', 'fanfou_notify_post');
// }}}


// {{{ fanfou_update_posts
/**
 * fanfou_update_posts
 *
 * @param mixed $truncate truncate the fanfou_posts table
 * @access public
 * @return void
 */
function fanfou_update_posts($truncate = false)
{
    global $wpdb, $fanfou;
    if (!$fanfou->username or !$fanfou->password) {
        exit;
    }

    // try fetch something
    $hash = $fanfou->get_user_timeline($posts);
    if ((null == $hash) or ($hash == get_option('fanfou_update_hash'))) {
        // just return and don't display any error message, if error while
        // fetching fanfou status
        return;
    }

    if (is_array($posts) and count($posts) > 0) {
        $latest_ids = array();
        $exists_ids = array();

        // reverse the result array
        $posts = array_reverse($posts);
        foreach ($posts as $post) {
            $latest_ids[] = $wpdb->escape($post->id);
        }

        if ($truncate) {
            $wpdb->query("TRUNCATE TABLE `$wpdb->fanfou`");
        }
        else {
            $exists_ids = $wpdb->get_col("SELECT `fanfou_id` FROM `$wpdb->fanfou` WHERE `fanfou_id` IN ('".implode("', '", $latest_ids)."')");
        }

        foreach ($posts as $post) {
            if ($truncate or !in_array($post->id, $exists_ids)) {
                $status = new FanfouPost($post->id, $post->text, $post->created_at);
                $status->insert();
            }
        }
    }

    update_option('fanfou_update_hash',   $hash);
    update_option('fanfou_last_download', time());
}
// }}}


// {{{ fanfou_get_posts
/**
 * fanfou_get_posts
 *
 * @param mixed $sort
 * @param mixed $sort_order
 * @param mixed $limit
 * @access public
 * @return void
 */
function fanfou_get_posts($sort, $sort_order, $limit)
{
    global $wpdb;

    $query  = "SELECT `fanfou_id`, `fanfou_text`, `fanfou_created_at` ";
    $query .= "FROM `$wpdb->fanfou` ";
    $query .= "ORDER BY `$sort` $sort_order ";
    $query .= "LIMIT $limit";
    return $wpdb->get_results($query);
}
// }}}


//  {{{ fanfou_latest_post
/**
 * fanfou_latest_post
 *
 * @access public
 * @return void
 */
function fanfou_latest_post()
{
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM `$wpdb->fanfou` ORDER BY  `fanfou_created_at` DESC LIMIT 1");;
}
// }}}


// {{{ fanfou_list_posts
/**
 * fanfou_list_posts
 *
 * @param string $args
 * @access public
 * @return void
 */
function fanfou_list_posts($args = '')
{
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


// {{{ fanfou_list_friends
/**
 * fanfou_list_friends
 *
 * @param mixed $args
 * @access public
 * @return void
 */
function fanfou_list_friends($args = '')
{
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


/**
 * fanfou_init_widget
 * This gets called at the plugins_loaded action
 *
 * @access public
 * @return void
 */
function fanfou_init_widget()
{
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
    function wp_widget_fanfou($args)
    {
        extract($args);
        $options = get_option('widget_fanfou');
        $title   = empty($options['status_title']) ? _f('Fanfou Tools' ) : $options['status_title'];

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
    function wp_widget_fanfou_friends($args)
    {
        extract($args);
        $options = get_option('widget_fanfou');
        $title   = empty($options['friends_title']) ? _f('Fanfou Friends') : $options['friends_title'];

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
    function wp_widget_fanfou_control()
    {
        print "<p>" . _f("You can config <strong>Fanfou Tools</strong> from:");
        print "\n<br/><br/>\n";
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
    function wp_widget_fanfou_friends_control()
    {
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
    register_widget_control('Fanfou Tools',   'wp_widget_fanfou_control');

    register_sidebar_widget('Fanfou Friends', 'wp_widget_fanfou_friends');
    register_widget_control('Fanfou Friends', 'wp_widget_fanfou_friends_control');
}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('plugins_loaded', 'fanfou_init_widget');

/* vim: set expandtab tabstop=4 shiftwidth=4: */

