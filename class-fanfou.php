<?php
require_once ABSPATH . WPINC . '/class-snoopy.php';
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
class Fanfou
{
    var $data = array();
    var $json;
    var $snoop;
    var $snoop_options = array(
        'rawheaders' => array(),
    );

    // {{{ Construct
    /**
     * Fanfou Constructor
     *
     * @param mixed $username
     * @param mixed $password
     * @access public
     * @return void
     */
    function __construct()
    {
        // Load options
        $this->get_settings();

        // Initialize Services_JSON
        $this->json = new Services_JSON();

        // Expires
        //$this->snoop_options['rawheaders']['Expires'] = gmdate('D, d M Y H:i:s', 0) . ' GMT';
    }
    // }}}

    // {{{ init_snoopy()
    /**
     * init_snoopy
     *
     * @access public
     * @return void
     */
    function init_snoopy()
    {
        if (is_object($this->snoop) and is_a($this->snoop, 'Snoopy')) {
            return;
        }

        $this->snoop = new Snoopy;
        foreach ($this->snoop_options as $key=> $val) {
            $this->snoop->$key = $val;
        }

        // Http basic auth
        $this->snoop->user = $this->username;
        $this->snoop->pass = $this->password;
    }
    // }}}

    // {{{ install_table()
    /**
     * Install table when first active fanfou-tools plugin
     *
     * @access public
     * @return void
     */
    function install_table()
    {
        global $wpdb;
		$fanfou = $wpdb->fanfou;

		// check to see if the table has already been created.
		if($wpdb->get_var("SHOW TABLES LIKE '$fanfou'") == $fanfou) {
			return;
		}

        $engine  = 'MyISAM';
        $charset = 'utf8';
        $foo     = $wpdb->get_var("SHOW CREATE TABLE $wpdb->posts", 1);
        if ($foo) {
            preg_match("/ENGINE=([a-zA-Z]+) .* CHARSET=([a-zA-Z0-9]+)/i", $foo, $matches);
            if (isset($matches[1])) {
                $engine = $matches[1];
            }
            if (isset($matches[2])) {
                $charset = $matches[2];
            }
        }
        $wpdb->query("
CREATE TABLE IF NOT EXISTS `$fanfou` (
  `id`                  int(11)         NOT NULL AUTO_INCREMENT,
  `fanfou_id`           varchar(255)    NOT NULL,
  `fanfou_text`         varchar(255)    NOT NULL,
  `fanfou_created_at`   int(10)         NOT NULL,
  `modified`            int(10)         NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fanfou_id` (`fanfou_id`)
) ENGINE=$engine DEFAULT CHARSET=$charset AUTO_INCREMENT=1;
        ");
    }
    // }}}

    // {{{ install_options()
    /**
     * Install default options
     *
     * @access public
     * @return void
     */
    function install_options()
    {
        add_option('fanfou_username',					'');
        add_option('fanfou_password',					'');
        add_option('fanfou_notify_fanfou',				1);
        add_option('fanfou_notify_exclude_categories',  '');
        add_option('fanfou_notify_format',				'New Blog Post: %postname% - %permalink%');
        add_option('fanfou_notify_use_tinyurl',			0);
        add_option('fanfou_sidebar_status_num',			5);
        add_option('fanfou_sidebar_friends_num',		10);
        add_option('fanfou_date_format',				'Y-m-d H:i');
        add_option('fanfou_download_interval',			600);
        add_option('fanfou_last_download',				time() - 600);
        add_option('fanfou_locale',						'default');

        add_option('fanfou_update_hash',				'');
	    add_option("fanfou_tools_ver",					FANFOU_TOOLS_VER);
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
    function tinyurl($text)
    {
        if (!class_exists('TinyURL')) {
            require_once ABSPATH . PLUGINDIR . '/fanfou-tools/TinyURL.php';;
        };
        return preg_replace('|\[tiny\](.*?)\[/tiny\]|ise', "TinyURL::transform('\\1')", $text);
    }
    // }}}

    // {{{ login($username, $password)
    /**
     * login fanfou with given username and password
     *
     * @param mixed $username
     * @param mixed $password
     * @access public
     * @return void
     */
    function login($username, $password)
    {
        $this->init_snoopy();
        // init_snoopy 调用后，程序保存的帐号密码是从数据库中读取的。
        // 而这里使用 login 函数提供的帐号密码替换掉
        $this->snoop->user = $username;
        $this->snoop->pass = $password;

        $this->snoop->fetch('http://api.fanfou.com/statuses/user_timeline.json');
        return (boolean) strpos($this->snoop->response_code, '200 OK');
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
    function post($text = '')
    {
        if (!$this->username or !$this->password or !$text) {
            return;
        }

        // Convert TinyURL
        $text = $this->tinyurl($text);
        $this->init_snoopy();
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
     * 首先从饭否服务器端删除消息，然后再删除本地的消息
     *
     * @param mixed $id
     * @param mixed $fanfou_id
     * @access public
     * @return void
     */
    function delete_post($id, $fanfou_id)
    {
        global $wpdb;
        if ($id and $fanfou_id) {
            // delete post from fanfou.com
            $this->init_snoopy();
            $url = "http://api.fanfou.com/statuses/destroy/$fanfou_id.xml";
            if ($this->snoop->submit($url)) {
                if (strpos($this->snoop->response_code, '200 OK')) {
                    update_option('fanfou_update_hash'  , '');
                    update_option('fanfou_last_download', strtotime('-8 minutes'));

                    // delete post from WordPress Cache
                    $result = $wpdb->query("DELETE FROM `$wpdb->fanfou` WHERE `id` = $id AND `fanfou_id` = '$fanfou_id'");
                }
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
    function delete_friend($user_id)
    {
        if ($user_id) {
            // delete friend
            $this->init_snoopy();
            $url = "http://api.fanfou.com/friendships/destroy/$user_id.xml";
            if ($this->snoop->fetch($url)) {
                // successful
            }
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
    function get_friends()
    {
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
     * @access public
     * @return void
     */
    function get_user_timeline(&$posts)
    {
        $url = "http://api.fanfou.com/statuses/user_timeline.json?id={$this->username}";
        $this->init_snoopy();
        if ($this->snoop->fetch($url)) {
            if (strpos($this->snoop->response_code, '200 OK')) {
                $hash  = md5($this->snoop->results);
                $posts = $this->json->decode($this->snoop->results);
                return $hash;
            }
        }
    }
    // }}}

    // {{{ get_settings()
    /**
     * get_settings
     *
     * @access public
     * @return void
     */
    function get_settings()
    {
        $this->username					= get_option('fanfou_username');
        $this->password					= get_option('fanfou_password');
        $this->notify_fanfou			= (int) get_option('fanfou_notify_fanfou');
        $this->notify_exclude_categories= get_option('fanfou_notify_exclude_categories');
        $this->notify_format			= get_option('fanfou_notify_format');
        $this->notify_use_tinyurl		= (int) get_option('fanfou_notify_use_tinyurl');
        $this->date_format				= get_option('fanfou_date_format');
        $this->sidebar_status_num		= (int) get_option('fanfou_sidebar_status_num');
        $this->sidebar_friends_num		= (int) get_option('fanfou_sidebar_friends_num');
        $this->download_interval		= (int) get_option('fanfou_download_interval');
        $this->last_download			= (int) get_option('fanfou_last_download');
        $this->locale					= get_option('fanfou_locale');
    }
    // }}}

    // {{{ save_settings()
    /**
     * save_settings
     *
     * @access public
     * @return void
     */
    function save_settings()
    {
        $this->username						= trim($_POST['ff_username']);
        $this->password						= trim($_POST['ff_password']);
        $this->notify_fanfou				= intval(trim($_POST['ff_notify_fanfou']));
        $this->notify_format				= htmlspecialchars(trim($_POST['ff_notify_format']));
        $this->notify_use_tinyurl			= intval(trim($_POST['ff_notify_use_tinyurl']));
        $this->date_format					= trim($_POST['ff_date_format']);
        $this->sidebar_status_num			= intval(trim($_POST['ff_sidebar_status_num']));
        $this->sidebar_friends_num			= intval(trim($_POST['ff_sidebar_friends_num']));
        $this->download_interval			= intval(trim($_POST['ff_download_interval']));
        $this->locale						= $_POST['ff_locale'];

		// format exclude categories ids
		$str = str_replace('，', ',', trim($_POST['ff_notify_exclude_categories']));
		$str = str_replace('　', '', $str);
		$foo = array_unique(explode(',', $str));
		$baz = array();
		foreach ($foo as $id) {
			if (is_numeric($id)) {
				$baz[] = (int) $id;
			}
		}
		sort($baz);
        $this->notify_exclude_categories = join(',', $baz);

        update_option('fanfou_username',					$this->username);
        update_option('fanfou_password',					$this->password);
        update_option('fanfou_notify_fanfou',				$this->notify_fanfou);
        update_option('fanfou_notify_exclude_categories',	$this->notify_exclude_categories);
        update_option('fanfou_notify_format',				$this->notify_format);
        update_option('fanfou_notify_use_tinyurl',			$this->notify_use_tinyurl);
        update_option('fanfou_date_format',					$this->date_format);
        update_option('fanfou_sidebar_status_num',			$this->sidebar_status_num);
        update_option('fanfou_sidebar_friends_num',			$this->sidebar_friends_num);
        update_option('fanfou_download_interval',			$this->download_interval);
        update_option('fanfou_locale',						$this->locale);
    }
    // }}}

    // {{{ Overloaded functions
    /**
     * __set
     *
     * @param mixed $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }


    /**
     * __get
     *
     * @param mixed $name
     * @return void
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }
    // }}}
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */

