<?php
class AdvertModel extends Model {
    public function canCreateAds($user = null) {
        //if (!model('user')->isLoggedIn()) return false;
        $option = config('who-create-ads', 0);
        if ($option == 0) return true;
        $user = ($user) ? $user : model('user')->authUser;
        if ($option == 2 and model('user')->isAdmin()) return true;

        if ($option == 1) {
            if ($user['user_type'] == 2 and model('user')->subscriptionActive($user['id'])) return true;
            if ($user['user_type'] == 1 and model('user')->listenerSubscriptionActive($user['id'])) return true;
        }
        return false;
    }

    public function getWalletTransactions() {
        $sql = "SELECT * FROM transactions ";
        $sql .= " WHERE type=? AND userid=? ";
        $param = array('wallet', model('user')->authId);
        $sql .= " ORDER BY id DESC";

        return $this->db->paginate($sql, $param, 20);
    }

    public function add($val, $ads = null) {
        $exp = array(
            'name' => '',
            'track' => '',
            'title' => '',
            'description' => '',
            'country' => array(),
            'pay_type' => 2,
            'placement' => 1,
            'type' => '',
            'link' => '',
            'img' => ''
        );
        /**
         * @var $name
         * @var $track
         * @var $title
         * @var $url
         * @var $description
         * @var $country
         * @var $pay_type
         * @var $placement
         * @var $type
         * @var $link
         * @var $img
         */
        extract(array_merge($exp, $val));
        $userid = model('user')->authId;
        $country = implode(',', $country);
        if ($ads) {
            if ($track) {
                $placement = 0;
                $pay_type = 2;
            }
            $this->db->query("UPDATE user_ads SET ad_title=?, ad_desc=?,pay_type=?,ad_placement=?,target=?,track_id=?,ad_link=?,ad_slug=? WHERE id=?",
                $title, $description,$pay_type,$placement,$country,$track,$link,$name, $ads['id']);
        } else {
            $track = (!$track) ? 0 : $track;
            if ($track) $placement = 0;
            $this->db->query("INSERT INTO user_ads
 (ad_slug,ad_link,userid,ad_type,track_id,ad_image,ad_title,ad_desc,ad_placement,pay_type,target,date_created) 
 VALUES(?,?,?,?,?,?,?,?,?,?,?,?)", $name, $link,$userid, $type,$track,$img,$title,$description,$placement,$pay_type,$country,time());
        }

        return true;
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM user_ads WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getMyLists() {
        $query = $this->db->query("SELECT * FROM user_ads WHERE userid=? ORDER BY id DESC", model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        return $this->db->query("DELETE FROM user_ads WHERE id=?", $id);
    }

    public function toggleStatus($ads, $admin = false ) {
        if ($ads['admin_status'] and !$admin) return false;//prevent false enabling by users
        $status = !$ads['status'] ? 1 : 0;
        return $this->db->query("UPDATE user_ads SET status=? WHERE id=?", $status, $ads['id']);
    }

    public function adminToggleStatus($adsId) {
        $ads = $this->find($adsId);
        $status = !$ads['admin_status'] ? 1 : 0;
        return $this->db->query("UPDATE user_ads SET admin_status=? WHERE id=?", $status, $ads['id']);
    }

    public function display($type, $placement = null) {
        $type = ($type == 'image') ? 1 : 2;
        $placement = ($placement == 'sidebar') ? 1 : 2;
        if ($type == 2)  $placement = 0;
        if ($type == 2) {
            $lastPlayedCount = session_get('ads.track.count', 0);
            $configValue = config('audio-ads-interval', 5);

            if ($lastPlayedCount >= $configValue) {
                session_put('ads.track.count', 0);
            } else {
                session_put('ads.track.count', $lastPlayedCount + 1);
                return false;
            }
        }


        $hides = array(0);
        if (model('user')->isLoggedIn()) {
            //$hides[] = model('user')->authId;
        }
        $hides = implode(',', $hides);

        $query = $this->db->query("SELECT * FROM user_ads WHERE userid NOT IN ($hides) AND ad_type=? AND ad_placement=? AND status=? AND admin_status=? ORDER BY rand() LIMIT 1",
            $type,$placement,1,0);
        $ad = $query->fetch(PDO::FETCH_ASSOC);

        if (!$ad) return false;

        $user = model('user')->getUser($ad['userid']);
        $clickCharge = config('pay-per-click-charge', '0.7');
        $impressionCharge = config('pay-per-impression-charge', 0.1);
        $charge = ($ad['pay_type'] == 2) ? $impressionCharge : $clickCharge;
        if($type == 2) {
            $charge = $impressionCharge; //force audio ads on impression charge
            $ad['pay_type'] = 2;
        }
        if ($user['wallet'] < $charge) {
            //disable this ad
            $this->db->query("UPDATE user_ads SET status=? WHERE id=?", 0, $ad['id']);
            return false;
        }

        if ($ad['pay_type'] == 2) {
            if (model('user')->isLoggedIn() and $ad['userid'] == model('user')->authId) {
                //dont do anything
            } else {
                $wallet = $user['wallet'] - $charge;
                $this->db->query("UPDATE users SET wallet=? WHERE id=? ", $wallet, $ad['userid']);
            }

        }
        return $ad;
    }

    public function countActiveAds($admin = false) {
        if ($admin) {
            $query = $this->db->query("SELECT id FROM user_ads WHERE  status=? AND admin_status=?",1, 0);
        } else {
            $query = $this->db->query("SELECT id FROM user_ads WHERE userid=? AND status=? AND admin_status=?", model('user')->authId, 1, 0);
        }
        return $query->rowCount();
    }

    public function countNonActiveAds($admin = false) {
        if ($admin) {
            $query = $this->db->query("SELECT id FROM user_ads WHERE (status=? OR admin_status=?)", 0, 1);
        } else {
            $query = $this->db->query("SELECT id FROM user_ads WHERE userid=? AND (status=? OR admin_status=?)", model('user')->authId, 0, 1);
        }
        return $query->rowCount();
    }

    public function countAllAds() {
        $query = $this->db->query("SELECT id FROM user_ads ");
        return $query->rowCount();
    }


    public function getAdminLists($term = '') {
        $sql = "SELECT * FROM user_ads WHERE id!=? ";
        $param = array('');

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 10);
    }
}