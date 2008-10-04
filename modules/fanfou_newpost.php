<?php
if ($_GET['fanfou-posted']) {
    print('
        <div id="message" class="updated fade">
        <p>'._f('Fanfou posted.').'</p>
        </div>');
}
?>
<div class="wrap">

<h2>Fanfou Tools v<?php echo FANFOU_TOOLS_VER;?> - <em style="color: gray; font-size: 18px;"><?php echo _f('Write New'); ?></em></h2>
<?php include_once('tab_navbar.php');?>

<?php
// check username and password
if (!$fanfou->username or !$fanfou->password) {
    print('<p style="color: red;">' . _f('Please enter your <a href="http://fanfou.com">fanfou.com</a> account information in <a href="./admin.php?page=fanfou-tools.php">Options</a> page.') . '</p>');
}
?>

<p>
<?php echo _f('This will create a new \'Fanfou\' status in <a href="http://fanfou.com">fanfou.com</a> using the account information saved in <a href="./admin.php?page=fanfou-tools.php">Options</a> page.');?><br/>
<?php echo _f('You can use the UBBCode <span style="color: red">[tiny][/tiny]</span> to automatically convert an URL into a Tiny URL.');?>
</p>

<form action="'.get_bloginfo('wpurl').'/wp-admin/post-new.php?page=fanfou-tools.php" method="post" id="fanfou_post_form">
<fieldset>
<p><textarea type="text" cols="60" rows="5" maxlength="140" id="fanfou_status_text" name="fanfou_status_text" onkeyup="fanfouCharCount();"></textarea></p>
<input type="hidden" name="fanfou_action" value="post_status" />
<script type="text/javascript">
//<![CDATA[
function fanfouCharCount()
{
    var count = document.getElementById("fanfou_status_text").value.length;
    if (count > 0) {
        document.getElementById("fanfou_char_count").innerHTML = (140 - count) + "<?php echo _f(' characters remaining');?>";
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
<input type="submit" id="fanfou_submit" name="fanfou_submit" value="<?php echo _f('Post Fanfou Status!');?>" />
<span id="fanfou_char_count"></span>
</p>
<div class="clear"></div>
</fieldset>
</form>
</div>

