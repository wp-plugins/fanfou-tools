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


/**
 * Class FanfouPost
 *
 * @package wp-content/plugins/fanfou-tools
 * @version $Id$
 * @copyright Copyright (C) 2007 Verdana Mu
 * @author Verdana Mu <verdana.cn@gmail.com>
 * @license LGPL license {@link http://www.gnu.org/licenses/lgpl.txt}
 */
class FanfouPost
{
    var $id;
    var $fanfou_id;
    var $fanfou_text;
    var $fanfou_created_at;
    var $modified;

    // {{{ __construct($fanfou_id, $fanfou_text, $fanfou_created_at = 0)

    /**
     * FanfouPost Construct
     *
     * @param mixed $fanfou_id
     * @param mixed $fanfou_text
     * @param int $fanfou_created_at
     * @access public
     * @return void
     */
    function __construct($fanfou_id, $fanfou_text, $fanfou_created_at = 0)
    {
        $this->id                = null;
        $this->fanfou_id         = $fanfou_id;
        $this->fanfou_text       = $fanfou_text;
        $this->fanfou_created_at = $this->date_to_time($fanfou_created_at);
        $this->modified          = null;
    }

    // }}}

    // {{{ date_to_time($date)

    /**
     * date_to_time
     *
     * @param mixed $date
     * @access public
     * @return void
     */
    function date_to_time($date)
    {
        $parts = explode(' ', $date);
        $gmt   = (int) strtotime($parts[1].' '.$parts[2].', '.$parts[5].' '.$parts[3]);
        // + 8 hours, convert to PRC time
        return $gmt + 28800;
    }

    // }}}

    // {{{ insert()

    /**
     * insert
     *
     * @access public
     * @return void
     */
    function insert()
    {
        global $wpdb, $fanfou;
        $wpdb->query("
            INSERT INTO $wpdb->fanfou
            (fanfou_id , fanfou_text , fanfou_created_at , modified)
            VALUES (
                '".$wpdb->escape($this->fanfou_id)."',
                '".$wpdb->escape($this->fanfou_text)."',
                '".$this->fanfou_created_at."',
                ".time().")
                ");
    }

    // }}}
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */

