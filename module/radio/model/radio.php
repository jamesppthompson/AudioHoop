<?php
class RadioModel extends Model {
    public function canAddRadio($user = null) {
        //if (!model('user')->isLoggedIn()) return false;
        $option = config('who-can-add-radio', 1);
        if ($option == 1) return true;
        $user = ($user) ? $user : model('user')->authUser;

        if ($option == 2) {
            if ($user['user_type'] == 2) return true;
        }
        if ($option == 3) {
            if (model('user')->subscriptionActive($user['id'])) return true;
        }
        return false;
    }

    public function add($val, $radio = null) {
        $ex = array(
            'title' => '',
            'link' => ($radio) ? $radio['link'] : '',
            'link_type' => ($radio) ? $radio['link_type'] : '',
            'description' => '',
            'genre' => '',
            'tags' => '',
            'art' => ($radio) ? $radio['art'] : '',
            'userid' => ''
        );
        /**
         * @var $title
         * @var $link
         * @var $link_type
         * @var $description
         * @var $genre
         * @var $tags
         * @var $art
         * @var $userid
         */
        extract(array_merge($ex, $val));
        $slug = toAscii($title);

        $userid = ($userid and model('user')->isAdmin()) ? $userid : model('user')->authId;
        if ($radio) {
            $radioId = $radio['id'];
            $this->db->query("UPDATE radios SET title=?,description=?,slug=?,link=?,link_type=?,art=?,genre=?,tags=? WHERE id=? ",
                $title,$description,$slug,$link,$link_type,$art,$genre,$tags, $radioId);
        } else {
            $this->db->query("INSERT INTO radios (userid,title,description,slug,link,link_type,art,genre,tags,time)VALUES(?,?,?,?,?,?,?,?,?,?)",
                $userid,$title,$description,$slug,$link,$link_type,$art,$genre,$tags,time());

            $radioId = $this->db->lastInsertId();
        }

        return $radioId;
    }

    public function radioUrl($radio = null,  $slug = '') {
        $dslug = ($radio['slug']) ? $radio['slug'] : toAscii($radio['title']);
        $url = 'radio/'.$radio['id'].'-'.$dslug;
        if ($slug) $url .= '/'.$slug;
        return $this->C->request->url($url);
    }

    public function getArt($radio, $size = 200) {
        $image = assetUrl('assets/images/track.png');
        if ($radio['art']) {
            $image = url_img($radio['art'], $size);
        }
        return $image;
    }

    public function findRadio($radioId) {
        $query = $this->db->query("SELECT * from radios WHERE  id=? ",$radioId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getRadios($type, $typeId = null, $offset = 0, $limit = 10) {
        $sql = "SELECT * FROM radios WHERE id != '' ";
        $param = array();

        switch($type) {
            case 'search':
                $sql .= "";
                $sql .= " AND (title LIKE ? OR description LIKE ? ) ";

                if ($typeId) {
                    $param[] = "%$typeId%";
                    $param[] = "%$typeId%";
                    $sql .= " ORDER BY id DESC ";
                } else {
                    return array();
                }
                break;
            case 'all':
                $sql .= " ";
                if ($typeId) $sql .= " AND price > 0 ";
                $sql .= " ORDER BY id DESC ";
                break;
            case 'category':
                $sql .= " AND genre=?";
                $param[] = $typeId;
                $sql .= " ORDER BY id DESC ";
                break;
            case 'top':
                $sql = "SELECT *,(SELECT count(radio_id) FROM radio_listeners WHERE radio_listeners.radio_id=radios.id ) as count FROM radios ";
                $sql .= " ORDER BY count DESC ";
                break;
        }
        if (!$sql) {
            return array();
        } else {
            $sql .= " LIMIT $limit OFFSET $offset";
            $query = $this->db->query($sql, $param);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function setView($radioId) {
        if ($this->hasView($radioId)) {
            //we are updating the time only
            $ip = (model('user')->isLoggedIn()) ? '' : get_ip();
            if (model('user')->isLoggedIn()) {
                $this->db->query("UPDATE radio_listeners SET time=?,userip=? WHERE userid=? AND radio_id=?", time(), get_ip(), model('user')->authId,$radioId);
            } else {
                $this->db->query("UPDATE radio_listeners SET time=? WHERE userip=? AND radio_id=?", time(), get_ip(),$radioId);
            }
        } else {
            $ip = get_ip();
            $userid = model('user')->authId;
            $this->db->query("INSERT INTO radio_listeners (userid,userip,radio_id,time)VALUES(?,?,?,?)",$userid, $ip,$radioId,time());
        }
    }

    public function hasView($radioId) {
        if (model('user')->isLoggedIn()) {
            $userid = model('user')->authId;
            $query = $this->db->query("SELECT id FROM radio_listeners WHERE userid=? AND radio_id=?", $userid, $radioId);
            return $query->rowCount();
        } else {
            $query = $this->db->query("SELECT id FROM radio_listeners WHERE userip=? AND radio_id=?", get_ip(), $radioId);
            return $query->rowCount();
        }
    }

    public function countViews($radioId) {
        $query = $this->db->query("SELECT id FROM radio_listeners WHERE radio_id=?", $radioId );
        return $query->rowCount();
    }

    public function getCurrentListeners($radioId) {
        $interval = time() - config('live-current-listener-interval', 60);
        $query = $this->db->query("SELECT id FROM radio_listeners WHERE time > $interval AND radio_id=?", $radioId );
        return $query->rowCount();
    }

    public function getAdminRadios($genre, $term, $user) {
        $sql = "SELECT * FROM radios WHERE id!=? ";
        $param = array('');
        if ($genre) {
            $sql .= " AND genre=? ";
            $param[] = $genre;
        }
        if ($user) {
            $sql .= " AND userid=? ";
            $param[] = $user;
        }
        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (title LIKE ?) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 10);
    }

    public function delete($radio) {
        $radio = $this->findRadio($radio);
        if ($radio['art']) {
            delete_file(path($radio['art']));
        }


        $this->db->query("DELETE FROM likes WHERE type=? AND typeid=?", 'radio', $radio['id']);
        $this->db->query("DELETE FROM comments WHERE type=? AND typeid=?", 'radio', $radio['id']);
        $this->db->query("DELETE FROM radio_listeners WHERE  radio_id=?", $radio['id']);
        $this->db->query("DELETE FROM radios WHERE  id=?", $radio['id']);
        return true;
    }


    public function getMyRadiosId($id = null) {
        $id = ($id) ? $id : $this->C->model('user')->authId;
        $query = $this->db->query("SELECT id FROM radios WHERE userid=?", $id);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['id'];
        }
        return $ids;
    }

    public function deleteUserRadios($id) {
        $radios = $this->getMyRadiosId($id);
        foreach($radios as $radio) {
            $this->delete($radio);
        }
        return true;
    }

}