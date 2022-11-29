<?php
Hook::getInstance()->register('user.menu.extend', function () {
   echo '<div class="dropdown-divider"></div>
                                        <a data-ajax="true" class="dropdown-item" href="'.url('wallet').'">'.l('advert::my-wallet').'</a>';
   if (model('advert::advert')->canCreateAds()) {
       echo '<a data-ajax="true" class="dropdown-item" href="'.url('advertising').'">'.l('advert::advertising').'</a>';
   }
});

Hook::getInstance()->register('admin.settings.integrations', function() {
    echo view('advert::admin/settings/integration');
});

Hook::getInstance()->register('statistics.dashboard.tabs', function() {
    $active = (Request::instance()->segment(0) == 'wallet') ? 'active' : null;
   echo '<li class="nav-item ">
                <a class="nav-link '.$active.'"  data-ajax="true" href="'.url('wallet').'">'.l('advert::my-wallet').'</a>
            </li>';
});

Hook::getInstance()->register('statistics.dashboard.header', function() {
   echo view('advert::wallet/stats');
});

Hook::getInstance()->register('payment.detail', function($details, $type, $typeId) {
    if ($type == 'wallet') {

        $details['title'] = l('advert::wallet-topup');
        $details['desc'] = '';
    }
    return $details;
});

Hook::getInstance()->register('transaction.add', function ($transactionId, $type, $type_id, $amount) {
   if ($type == 'wallet') {
       $transaction = model('admin')->findTransaction($transactionId);
       $user = model('user')->getUser($transaction['userid']);
       $userid = $user['id'];
       $amount = $user['wallet'] + $amount;
        Database::getInstance()->query("UPDATE users SET wallet=? WHERE id=?", $amount , $userid);
   }
});

Hook::getInstance()->register('payment.success.url', function($url, $type, $typeId) {
    if ($type == 'wallet') {
        return url('wallet');
    }
    return $url;
});

Hook::getInstance()->register('global.side', function() {
    echo view('advert::display/side');
});

Hook::getInstance()->register('global.sidebar', function() {
    echo view('advert::display/side');
});
Hook::getInstance()->register('feed.top.extend', function() {
    echo view('advert::display/banner');
});

Hook::getInstance()->register('global.top', function() {
    echo view('advert::display/banner');
});

Hook::getInstance()->register('payment.method', function($price,$type,$typeId,$detail) {
   if ($type != 'wallet') {
       echo view('advert::payment/method', array('price' => $price, 'type' => $type, 'typeId' => $typeId));
   }
});

Hook::getInstance()->register('admin.menu.end', function() {
    $C = getController();
    $active = $C->activeMenu == 'advert' ? 'active' : null;
    if(model('user')->hasRole('manage-user-ads')) {
        echo '    <li><a id="genres-menu" data-ajax="true" class="sub-menu '.$active.'" href="'.url('admin/adverts').'"> <i class="la la-list-ul"></i> '.l('advert::manage-user-ads').'</a></li>';

    }
});

Hook::getInstance()->register('user.roles', function($roles) {
   $roles['manage-user-ads'] = l('manage-user-ads');
    return $roles;
});

$request->any("admin/adverts", array('uses' => 'advert::admin@index'));
$request->any("wallet", array('uses' => 'advert::advert@wallet'));
$request->any("wallet/pay", array('uses' => 'advert::advert@walletPay'));
$request->any("advertising", array('uses' => 'advert::advert@index'));
$request->any("ads/clicking", array('uses' => 'advert::advert@clicking'));