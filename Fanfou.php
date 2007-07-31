<?php

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


require_once ABSPATH.WPINC.'/class-snoopy.php';
if (!class_exists('Services_JSON')) {
    require_once ABSPATH . PLUGINDIR . '/fanfou-tools/JSON.php';
}


/**
 * Class FanFou
 *
 * @package wp-content/plugins/fanfou-tools
 * @version $Id$
 * @copyright Copyright (C) 2007 Verdana Mu
 * @author Verdana Mu <verdana.cn@gmail.com>
 * @license LGPL license {@link http://www.gnu.org/licenses/lgpl.txt}
 */
class Fanfou {
    // {{{ Properties

    /**
     * json
     *
     * @var object
     * @access public
     */
    var $json;

    /**
     * snoop
     *
     * @var object
     * @access public
     */
    var $snoop;

    /**
     * snoop_options
     *
     * @var mixed
     * @access public
     */
    var $snoop_options = array(
        'agent'      => 'Fanfou Tools - http://www.phpvim.net',
        'version'    => '1.0b5',
        'client'     => 'Fanfou Tools',
        'client-url' => 'http://www.phpvim.net/wordpress/fanfou-tools.html',
    );

    /**
     * username
     *
     * @var mixed
     * @access public
     */
    var $username;

    /**
     * password
     *
     * @var mixed
     * @access public
     */
    var $password;

    /**
     * notify_fanfou
     *
     * @var integer
     * @access public
     */
    var $notify_fanfou;

    /**
     * notify_format
     *
     * @var mixed
     * @access public
     */
    var $notify_format;

    /**
     * notify_use_tinyurl
     *
     * @var mixed
     * @access public
     */
    var $notify_use_tinyurl;

    /**
     * date_format
     *
     * @var mixed
     * @access public
     */
    var $date_format;

    /**
     * sidebar_status_num
     *
     * @var integer
     * @access public
     */
    var $sidebar_status_num;

    /**
     * sidebar_friends_num
     *
     * @var mixed
     * @access public
     */
    var $sidebar_friends_num;

    /**
     * download_interval
     *
     * @var integer
     * @access public
     */
    var $download_interval;

    /**
     * last_down
     *
     * @var integer
     * @access public
     */
    var $last_download;

    // }}}


    // {{{ Constructor Fanfou()

    /**
     * Fanfou Constructor
     *
     * @param mixed $username
     * @param mixed $password
     * @access public
     * @return void
     */
    function Fanfou() {
        // Load options
        $this->get_settings();

        // Initialize Services_JSON
        $this->json = new Services_JSON();
    }

    // }}}


    // {{{ init_snoopy($username = '', $password = '')

    /**
     * init_snoopy
     *
     * @param  string $username
     * @param  string $password
     * @access public
     * @return void
     */
    function init_snoopy($username = '', $password = '') {
        if (is_object($this->snoop) and is_a($this->snoop, 'Snoopy')) {
            return;
        }

        $this->snoop = &new Snoopy;
        $this->snoop->agent = $this->snoop_options['agent'];
        $this->snoop->rawheaders = array(
            'X-Twitter-Client'         => $this->snoop_options['client'],
            'X-Twitter-Client-Version' => $this->snoop_options['version'],
            'X-Twitter-Client-URL'     => $this->snoop_options['client-url']
        );

        if ($username) $this->snoop->user = $username;
        if ($password) $this->snoop->pass = $password;
    }

    // }}}


    // {{{ install_table()

    /**
     * install
     *
     * @access public
     * @return void
     */
    function install_table() {
        global $wpdb;
        $result = $wpdb->query("
            CREATE TABLE `$wpdb->fanfou` (
                `id`                INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `fanfou_id`         VARCHAR(255) NOT NULL ,
                `fanfou_text`       VARCHAR(255) NOT NULL ,
                `fanfou_created_at` INT(10) NOT NULL ,
                `modified`          INT(10) NOT NULL ,
                INDEX (`fanfou_id`)
            )
        ");
    }

    // }}}


    // {{{ install_options()

    /**
     * install_options
     *
     * @access public
     * @return void
     */
    function install_options() {
        add_option('fanfou_username',            '');
        add_option('fanfou_password',            '');
        add_option('fanfou_notify_fanfou',       1);     // true
        add_option('fanfou_notify_format',       __('New Blog Post: %s %s'));
        add_option('fanfou_notify_use_tinyurl',  0);
        add_option('fanfou_sidebar_status_num',  10);
        add_option('fanfou_sidebar_friends_num', 20);
        add_option('fanfou_date_format',         'Y-m-d H:i');
        add_option('fanfou_download_interval',   1800);
        add_option('fanfou_last_download',       time() - 1800);

        // .....
        add_option('fanfou_update_hash',         '');
    }

    // }}}


    // {{{ login($username, $password)

    /**
     * login
     *
     * @param mixed $username
     * @param mixed $password
     * @access public
     * @return void
     */
    function login($username, $password) {
        $this->init_snoopy($username, $password);
        $this->snoop->fetch('http://api.fanfou.com/statuses/user_timeline.json');
        return (boolean) strpos($this->snoop->response_code, '200');
    }

    // }}}


    // {{{ tinyurl($text)

    /**
     * tinyurl
     *
     * @param mixed $text
     * @access public
     * @return void
     */
    function tinyurl($text) {
        require_once ABSPATH . PLUGINDIR . '/fanfou-tools/TinyURL.php';
        return preg_replace('|\[tiny\](.*?)\[/tiny\]|ise', "TinyURL::transform('\\1')", $text);
    }

    // }}}


    // {{{ post($text)

    /**
     * post
     *
     * @param string $text
     * @access public
     * @return void
     */
    function post($text = '') {
        if (empty($this->username) or empty($this->password) or empty($text)) {
            return;
        }

        // Convert TinyURL
        $text = $this->tinyurl($text);

        $this->init_snoopy($this->username, $this->password);
        $this->snoop->submit(
            'http://api.fanfou.com/statuses/update.json',
            array(
                'status' => $text,
                'source' => 'fanfoutools'
            )
        );

        if (strpos($this->snoop->response_code, '200')) {
            update_option('fanfou_update_hash'  , '');
            update_option('fanfou_last_download', strtotime('-8 minutes'));
            return true;
        }

        return false;
    }

    // }}}


    // {{{ delete_post($id, $fanfou_id)

    /**
     * delete_post
     *
     * @param mixed $id
     * @param mixed $fanfou_id
     * @access public
     * @return void
     */
    function delete_post($id, $fanfou_id) {
        global $wpdb;

        if ($id and $fanfou_id) {
            // delete post from WordPress Cache
            $wpdb->query("DELETE FROM `$wpdb->fanfou` WHERE `id`=$id AND `fanfou_id` = '$fanfou_id'");

            // delete post from Fanfou
            $this->init_snoopy($this->username, $this->password);
            $this->snoop->fetch("http://api.fanfou.com/statuses/destroy.json?id=$fanfou_id");

            if (strpos($this->snoop->response_code, '200')) {
                update_option('fanfou_update_hash'  , '');
                update_option('fanfou_last_download', strtotime('-8 minutes'));
                return true;
            }
        }
    }

    // }}}


    // {{{ delete_friend($user_id)

    /**
     * delete_friend
     *
     * @param mixed $user_id
     * @access public
     * @return void
     */
    function delete_friend($user_id) {
        if ($user_id) {
            // delete friend
            $this->init_snoopy($this->username, $this->password);
            $this->snoop->fetch("http://api.fanfou.com/friendships/destroy.json?id=$user_id");
            var_dump($this->snoop->results);
            var_dump($this->snoop->response_code);
        }
    }

    // }}}


    // {{{ get_friends()

    /**
     * get_friends
     *
     * @access public
     * @return void
     */
    function get_friends() {
        $this->init_snoopy();
        $this->snoop->fetch("http://api.fanfou.com/statuses/friends.json?id={$this->username}");

        $friends = array();
        if (strpos($this->snoop->response_code, '200')) {
            $friends = $this->json->decode($this->snoop->results);
        }

        sort($friends);

        return $friends;
    }

    // }}}


    // {{{ get_user_timeline(&$posts)

    /**
     * get_user_timeline
     *
     * @param  array    $posts
     * @param  integer  $count
     * @access public
     * @return void
     */
    function get_user_timeline(&$posts) {
        if (!$count) {
            $count = 10;
        }

        $this->init_snoopy();
        $this->snoop->fetch("http://api.fanfou.com/statuses/user_timeline.json?id={$this->username}");
        if (!strpos($this->snoop->response_code, '200')) {
            return;
        }

        $hash = md5($this->snoop->results);
        $posts = $this->json->decode($this->snoop->results);
        return $hash;
    }

    // }}}


    // {{{ get_settings()

    /**
     * get_settings
     *
     * @access public
     * @return void
     */
    function get_settings() {
        $this->username            = get_option('fanfou_username');
        $this->password            = get_option('fanfou_password');
        $this->notify_fanfou       = (int) get_option('fanfou_notify_fanfou');
        $this->notify_format       = get_option('fanfou_notify_format');
        $this->notify_use_tinyurl  = (int) get_option('fanfou_notify_use_tinyurl');
        $this->date_format         = get_option('fanfou_date_format');
        $this->sidebar_status_num  = (int) get_option('fanfou_sidebar_status_num');
        $this->sidebar_friends_num = (int) get_option('fanfou_sidebar_friends_num');
        $this->download_interval   = (int) get_option('fanfou_download_interval');
        $this->last_download       = (int) get_option('fanfou_last_download');
    }

    // }}}


    // {{{ save_settings()

    /**
     * save_settings
     *
     * @access public
     * @return void
     */
    function save_settings() {
        $this->username            = trim($_POST['ff_username']);
        $this->password            = trim($_POST['ff_password']);
        $this->notify_fanfou       = intval(trim($_POST['ff_notify_fanfou']));
        $this->notify_format       = trim($_POST['ff_notify_format']);
        $this->notify_use_tinyurl  = intval(trim($_POST['ff_notify_use_tinyurl']));
        $this->date_format         = trim($_POST['ff_date_format']);
        $this->sidebar_status_num  = intval(trim($_POST['ff_sidebar_status_num']));
        $this->sidebar_friends_num = intval(trim($_POST['ff_sidebar_friends_num']));
        $this->download_interval   = intval(trim($_POST['ff_download_interval']));

        update_option('fanfou_username',            $this->username);
        update_option('fanfou_password',            $this->password);
        update_option('fanfou_notify_fanfou',       $this->notify_fanfou);
        update_option('fanfou_notify_format',       $this->notify_format);
        update_option('fanfou_notify_use_tinyurl',  $this->notify_use_tinyurl);
        update_option('fanfou_date_format',         $this->date_format);
        update_option('fanfou_sidebar_status_num',  $this->sidebar_status_num);
        update_option('fanfou_sidebar_friends_num', $this->sidebar_friends_num);
        update_option('fanfou_download_interval',   $this->download_interval);
    }

    // }}}
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */

