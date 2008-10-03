function TestLogin() {
    var username = encodeURIComponent($('ff_username').value);
    var password = encodeURIComponent($('ff_password').value);

    var result = $('fanfou_login_test_result');
    result.innerHTML = "<?php echo _f('Testing...')?>";

    var params = "fanfou_action=fanfou_login_test&ff_username=" + username + "&ff_password=" + password;
    var myAjax = new Ajax.Updater(
        result,
        "?php bloginfo('wpurl');?>/wp-admin/admin.php", {
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
        "<?php bloginfo('wpurl');?>/wp-admin/edit.php", {
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

/* vim: set filetype=javascript expandtab tabstop=4 shiftwidth=4: */

