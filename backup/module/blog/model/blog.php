<?php
class BlogModel extends Model {
    public function save($val, $blog = null) {
        /**
         * @var $title
         * @var $desc
         * @var $content
         * @var $category
         * @var $status
         * @var $tags
         * @var $image
         */
        extract(array_merge(array(
            'title' => '',
            'desc' => '',
            'content' => '',
            'category' => '',
            'status' => 0,
            'image' => ($blog) ? $blog['image'] : ''
        ), $val));

        $userid = model('user')->authId;
        if ($blog) {
            $this->db->query("UPDATE blogs SET title=?,description=?,content=?,tags=?,image=?,category=?,status=? WHERE id=? ",
                $title,$desc,$content,$tags,$image,$category,$status,$blog['id']);
        } else {
            $time = time();

            $this->db->query("INSERT INTO blogs (title,description,userid,content,tags,category,image,status,time) VALUES(?,?,?,?,?,?,?,?,?)",
                $title,$desc,$userid,$content,$tags,$category,$image,$status,$time);
        }
    }

    public function getAdminBlogs($term = '') {
        $sql = "SELECT * FROM blogs WHERE id!=? ";
        $param = array('');
        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (title LIKE ?) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 10);
    }

    public function findBlog($id, $admin = false) {
        if ($admin) {
            $query = $this->db->query("SELECT * FROM blogs WHERE id=?", $id);
        } else {
            $query = $this->db->query("SELECT * FROM blogs WHERE id=? AND status=? ", $id , 0);
        }
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getUrl($blog) {
        $slug = toAscii($blog['title']);
        if (!$slug) $slug = md5($blog['title']);
        $url = 'article/'.$blog['id'].'-'.$slug.'';
        return $this->C->request->url($url);
    }

    public function delete($id) {
        $blog = $this->findBlog($id, true);
        delete_file($blog['image']);
        return $this->db->query("DELETE FROM blogs WHERE id=?", $id);
    }

    public function getBlogs($category = 'all', $term = '', $offset = 0) {
        $sql = " SELECT * FROM blogs WHERE status=? ";
        $limit = config('blogs-list-per-page', 10);
        $param = array(0);
        if ($category != 'all') {
            $sql .= " AND category=? ";
            $param[]= $category;
        }

        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (title LIKE ? OR description LIKE ? OR content LIKE ?) ";
            $param[] = $term;
            $param[] = $term;
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPopular() {
        $query = $this->db->query("SELECT * FROM blogs WHERE status=? ORDER BY views DESC LIMIT 5", 0);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function increamentViews($id) {
        $this->db->query("UPDATE blogs SET views= views +1 WHERE id=?", $id);
    }
}