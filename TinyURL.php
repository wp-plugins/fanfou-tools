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
 * TinyURL
 *
 * @version $Id$
 * @copyright Copyright (C) 2007 Verdana Mu
 * @author Verdana Mu <verdana.cn@gmail.com>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class TinyURL
{
    // {{{ transform($url)

    /**
     * transform
     *
     * @param mixed $url
     * @access public
     * @return void
     */
    public function transform($url) {
        if (!$url or !preg_match('|^(?:http://)?([^/]+)|i', $url)) {
            return;
        }

        $createUrl = "http://tinyurl.com/create.php?url=$url";
        $content = @file_get_contents($createUrl);
        if (!$content) {
            return $url;
        }

        $pattern = '|<blockquote><b>(http://tinyurl\.com/([^<]+))</b><br><small>|i';
        preg_match($pattern, $content, $matches);
        return $matches[1];
    }

    // }}}


    // {{{ revert($tinyurl)

    /**
     * revert
     *
     * @param mixed $tinyurl
     * @access public
     * @return void
     */
    public function revert($tinyurl) {
        $url     = explode('.com/', $tinyurl);
        $url     = 'http://preview.tinyurl.com/' . $url[1];
        $preview = file_get_contents($url);
        $pattern = '/redirecturl" href="(.*)">/i';
        preg_match($pattern, $preview, $matches);
        return $matches[1];
    }

    // }}}
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */

