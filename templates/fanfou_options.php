<div class="wrap">
    <h2>Fanfou Tools v<?php echo FANFOU_TOOLS_VER;?> - <em style="color: gray; font-size: 18px;"><?php echo  _f('Options'); ?></em></h2>
    <ul class="subsubsub">
        <li><a class="current" href="./admin.php?page=fanfou-tools.php">Fanfou Tools Options</a> |</li>
        <li><a href="./admin.php?page=fanfou-tools.php&p=posts">Fanfou Posts</a> |</li>
        <li><a href="./admin.php?page=fanfou-tools.php&p=friends">Fanfou Friends(1)</a></li>
    </ul>
    <form id="fanfou-tools" name="fanfou-tools" action="<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin.php?page=fanfou-tools.php" method="post">
        <input type="hidden" id="fanfou_action" name="fanfou_action" value="update_settings" />
        <p><?php echo _f('For information and updates, please visit:'); ?><br/>
            <a href="http://www.phpvim.net/wordpress/fanfou-tools.html" target="_blank">http://www.phpvim.net/wordpress/fanfou-tools.html</a>
        </p>

        <fieldset class="options">
        <legend><h3><?php echo _f('The Login Information'); ?></h3></legend>
        <table width="100%" border="0">
            <tr>
                <td width="20%" align="right"><?php echo _f('FanFou ID or Email:'); ?></td>
                <td width="80%"><input type="text" size="25" name="ff_username" id="ff_username" value="<?php echo $fanfou->username; ?>" /></td>
            </tr>
            <tr>
                <td align="right"><?php echo _f('FanFou Password:'); ?></td>
                <td><input type="password" size="25" name="ff_password" id="ff_password" value="<?php echo $fanfou->password; ?>" /></td>
            </tr>
        </table>
        <div class="submit">
            <input type="button" onclick="TestLogin(); return false;" value="<?php echo _f('Test Login'); ?> &raquo;" id="login_test" name="login_test"/>&nbsp;
            <span id="fanfou_login_test_result"></span>
        </div>
        </fieldset>

        <br/><br/>

        <fieldset class="options">
        <legend><h3><?php echo _f('Configuration'); ?></h3></legend>
        <div style="padding-left: 20px">
            <p>
                <input type="checkbox" name="ff_notify_fanfou" id="ff_notify_fanfou" value="1" <?php echo $fanfou_notify_fanfou; ?> />
                <?php echo _f('Create a fanfou status when you publish a new blog post?'); ?>
            </p>
            <p>
                <input type="checkbox" name="ff_notify_use_tinyurl" id="ff_notify_use_tinyurl" value="1" <?php echo $fanfou_notify_use_tinyurl; ?> />
                <?php echo _f('Shorten the long permalink into a Tiny URL?'); ?>
                <br/>
                <em style="font:normal 10px verdana; color: gray;"><?php echo _f("Using this option will slow down your blog post action."); ?></em>
            </p>
            <p>
                <?php echo _f('Format for notifier when publish a new blog post:'); ?>
                <input type="text" name="ff_notify_format" id="ff_notify_format" value="<?php echo $fanfou->notify_format; ?>" size="25" />
            </p>
            <p>
                <?php echo _f('Format for the datetime of fanfou status:'); ?>
                <input type="text" name="ff_date_format" id="ff_date_format" value="<?php echo $fanfou->date_format; ?>" size="25" />
                <br/>
                <em style="font:normal 10px verdana; color: gray;"><?php echo _f('The dates was formatted by <a target="_blank" href="http://www.php.net/manual/en/function.date.php"><strong>date()</strong></a>'); ?></em>.
            </p>
            <p>
                <?php echo _f('Fanfou status to show in sidebar:'); ?>
                <input type="text" name="ff_sidebar_status_num" id="ff_sidebar_status_num" value="<?php echo $fanfou->sidebar_status_num; ?>" size="6" />
            </p>
            <p>
                <?php echo _f('Your Fanfou friends to show in sidebar:'); ?>
                <input type="text" name="ff_sidebar_friends_num" id="ff_sidebar_friends_num" value="<?php echo $fanfou->sidebar_friends_num; ?>" size="6" />
            </p>
            <p>
                <?php echo _f('Time interval for updating new posts:'); ?>
                <input type="text" name="ff_download_interval" id="ff_download_interval" value="<?php echo $fanfou->download_interval; ?>" size="6" /> seconds
            </p>
            <p>
                <?php echo _f('Select a locale:'); ?>
                <select>
                    <option><?php echo _f("Automatic selection (default)");?></option>
                    <option><?php echo _f("English (en_US)");?></option>
                    <option><?php echo _f("Chinese (zh_CN)");?></option>
                </select>
            </p>
        </div>
        </fieldset>
        <div class="submit">
            <input type="submit" name="savechanges" value="<?php echo _f('Save Changes'); ?> &raquo;" />
        </div>
    </form>
</div>
