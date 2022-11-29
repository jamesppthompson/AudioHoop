<?php
class AdvertController extends Controller {

    public function index() {
        $this->setTitle(l('advert::advertising'));
        $this->addBreadCrumb(l('dashboard'), url('statistics'));
        $this->addBreadCrumb(l('advert::advertising'));
        $ads = array();

        if(!$this->model('advert::advert')->canCreateAds()) return $this->errorPage();

        if ($adsId = $this->request->input('id')) {
            $ads = $this->model('advert::advert')->find($adsId);
            if ($ads['userid'] != $this->model('user')->authId) $this->request->redirectBack();
        }

        if ($action  = $this->request->input('action')) {
            if ($action == 'delete') {
                $this->model('advert::advert')->delete($adsId);
                return $this->request->redirect(url('advertising'));
            }

            if ($action == 'enable') {
                $user = $this->model('user')->authUser;
                if ($user['wallet'] > 0) $this->model('advert::advert')->toggleStatus($ads);
                return $this->request->redirect(url('advertising'));
            }
        }

        if ($val = $this->request->input('val')) {
            $fields = array(
                'name' => 'required',
            );
            if (!$ads) {
                $fields['type'] = 'required';
            }
            $validator = Validator::getInstance()->scan($val, $fields);

            if ($validator->passes()) {
                $artFile = $this->request->inputFile('img');
                if ($artFile) {
                    $artUpload = new Uploader($artFile);
                    $artUpload->setPath("ads/".$this->model('user')->authId.'/image/'.date('Y').'/');
                    if ($artUpload->passed()) {
                        $val['img'] = $artUpload->resize()->result();
                    } else {
                        return json_encode(array(
                            'message' => $artUpload->getError(),
                            'type' => 'error'
                        ));
                    }
                } else {
                    if ($val['type'] == 1 and !$ads){
                        return json_encode(array(
                            'message' => l('advert::kindly-provide-ads-image'),
                            'type' => 'error'
                        ));
                    }
                }

                if (!$ads) {
                    $user = $this->model('user')->authUser;
                    if ($user['wallet'] < 5) {
                        return json_encode(array(
                            'message' => l('advert::wallet-too-low-for-transaction'),
                            'type' => 'error'
                        ));
                    }
                }

                $this->model('advert::advert')->add($val, $ads);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('advertising'),
                    'message' => l('advert::advert-added-success')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        return $this->render($this->view('advert::advertising/index', array('ads' => $ads)), true);
    }
    public function wallet() {
        $this->setTitle(l('advert::wallet'));
        $this->addBreadCrumb(l('dashboard'), url('statistics'));
        $this->addBreadCrumb(l('advert::my-wallet'));
        return $this->render($this->view('advert::wallet/index'), true);
    }

    public function walletPay() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('typeid');
        $price = $this->request->input('price');

        $user = $this->model('user')->authUser;
        $price = convertBackToBase($price);
        if ($user['wallet'] < $price) {
            return json_encode(array(
                'type' => 'error',
                'message' => l('advert::wallet-too-low-for-transaction')
            ));
        } else {
            $url = ($type == 'pro' or $type == 'pro-users') ? url('settings/pro') : url();
            $url = Hook::getInstance()->fire('payment.success.url', $url, array($type, $typeId));
            $wallet = $user['wallet'] - $price;
            Database::getInstance()->query("UPDATE users SET wallet=? WHERE id=?", $wallet, $user['id']);
            $saleId = 'wallet-'.time();

            $this->model('admin')->addTransaction(array(
                'amount' =>  $price,
                'type' => $type,
                'type_id' => $typeId,
                'sale_id' => $saleId,
                'name' => $this->model('user')->authUser['full_name'],
                'country' => $this->model('user')->authUser['country'],
                'email' => $this->model('user')->authUser['email'],
                'userid' => $this->model('user')->authId
            ));
            Hook::getInstance()->fire('payment.success', null, array($type, $typeId));
            if (session_get('mobile-pay') == 1) return json_encode(array(
                'status' => 1,
                'url' => url('api/pay/success'),
                'message' => l('transaction-successful')
            ));
            return json_encode(array(
                'type' => 'modal-url',
                'value' => $url,
                'content' => '#paymentMethodModal',
                'message' => l('transaction-successful')
            ));
        }
    }

    public function clicking() {
        $id = $this->request->input('id');
        $ad = $this->model('advert::advert')->find($id);
        $charge = config('pay-per-click-charge', '0.7');
        $user = model('user')->getUser($ad['userid']);
        if (model('user')->isLoggedIn() and $ad['userid'] == model('user')->authId) {

        } else {
            $wallet = $user['wallet'] - $charge;
            $this->db->query("UPDATE users SET wallet=? WHERE id=? ", $wallet, $user['id']);
        }

    }
}