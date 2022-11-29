<?php
Hook::getInstance()->register('main.menu.home', function () {
   echo view('radio::user-menu');
});


Hook::getInstance()->register('admin.settings.integrations', function() {
    echo view('radio::admin/settings/integration');
});

Hook::getInstance()->register('admin.menu.middle', function() {
    $active = getController()->activeMenu == 'radio' ? 'active' : '';
    if(model('user')->hasRole('manage-radio')) {
        echo '<li><a id="radio-menu" data-ajax="true" class="sub-menu '.$active.'" href="'.url('admin/radio').'"><i class="la la-film"></i>  '.l('radio::manage-radio').'</a></li>';
    }
});

Hook::getInstance()->register('user.roles', function($roles) {
    $roles['manage-radio'] = l('radio::manage-radio');
    return $roles;
});

Hook::getInstance()->register('uploads.type', function() {
    echo view('radio::upload/extend');
});





Hook::getInstance()->register('profile.menus', function($user, $page) {
    $active = ($page == 'videos') ? 'active' : null;
    if(model('radio::radio')->canAddRadio($user)) {
        /**echo '<li class="nav-item">
                <a class="nav-link '.$active.'"  data-ajax="true"  href="'.model('user')->profileUrl($user,'videos').'" >'.l('videos').'</a>
            </li>';**/
    }
});

Hook::getInstance()->register("profile.content", function($result, $user,$page) {
   if ($page == 'videos') {
       $result['content'] = view('radio::profile/content', array('user' => $user));
   }
   return $result;
});

Hook::getInstance()->register('share.details', function($detail, $type, $id) {
    if ($type == 'radio') {
        $radio = model('radio::radio')->findRadio($id);
        $detail['title'] = $radio['title'];
        $detail['link']  = model('radio::radio')->radioUrl($radio);
        $detail['art'] = model('radio::radio')->getArt($radio, 200);
    }
    return $detail;
});

Hook::getInstance()->register('delete.user', function($id) {
    model('radio::radio')->deleteUserRadios($id);
});
$request->any("radio", array('uses' => 'radio::radio@index', 'secure' => false));
$request->any("radio/add", array('uses' => 'radio::radio@add'));
$request->any("radio/edit", array('uses' => 'radio::radio@add'));
$request->any("radio/all", array('uses' => 'radio::radio@index', 'secure' => false));
$request->any("radio/category/{id}", array('uses' => 'radio::radio@index', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+'));
$request->any("radio/top", array('uses' => 'radio::radio@index', 'secure' => false));
$request->any("radio/set/views", array('uses' => 'radio::radio@setViews', 'secure' => false));
$request->any("radio/paginate", array('uses' => 'radio::radio@paginate', 'secure' => false));


$request->any("admin/radio", array('uses' => 'radio::admin@index'));
$request->any("load/radio/player/buttons", array('uses' => 'radio::radio@renderButtons', 'secure' => false));
$request->any('radio/{slug}', array('uses' => 'radio::radio@profile', 'secure' => false))->where(array('slug' => '[a-zA-Z0-9\_\-]+'));
//$request->any('radio/{slug}/{other}', array('uses' => 'radio::radio@profile', 'secure' => false))->where(array('slug' => '[a-zA-Z0-9\_\-]+', 'other' => '[a-zA-Z0-9\_\-]+'));

