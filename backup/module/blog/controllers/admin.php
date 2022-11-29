<?php
class AdminController extends Controller {
    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->addBreadCrumb(l('admin-panel'));
        $this->sideMenu = "admin/includes/side-menu";
        $this->activeMenu = "blogs";
    }

    public function index() {
        $this->adminSecure('manage-blogs');
        $this->setTitle(l('manage-blogs'));

        if ($action = $this->request->input('action')) {
            $this->model('blog::blog')->delete($this->request->input('id'));
        }

        $blogs = $this->model('blog::blog')->getAdminBlogs($this->request->input('term'));
        return $this->render($this->view('blog::admin/index', array('term' => $this->request->input('term'), 'blogs' => $blogs)), true);
    }

    public function add() {
        $this->adminSecure('manage-blogs');
        $this->setTitle(l('add-blog'));
        $this->useEditor = true;
        if ($val = $this->request->input('val', null, false)) {
            if ($this->isDemo()) $this->defendDemo();
            $validator = Validator::getInstance()->scan($val, array(
                'title' => 'required',
                'desc' => 'required',
                //'content' => 'required',
                'tags' => 'required',
                'category' => 'required',
            ));

            if ($blogArt = $this->request->inputFile('img')) {
                $artUpload = new Uploader($blogArt);
                $artUpload->setPath("blogs/".'preview/'.date('Y').'/');
                if ($artUpload->passed()) {
                    $val['image'] = $artUpload->resize()->result();
                } else {
                    return json_encode(array(
                        'message' => $artUpload->getError(),
                        'type' => 'error'
                    ));
                }
            } else {
                return json_encode(array(
                    'message' => l('please-provide-blog-image-preview'),
                    'type' => 'error'
                ));
            }
            if ($validator->passes()) {
                $this->model('blog::blog')->save($val);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/blogs'),
                    'message' => l('blogs-saved')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        return $this->render($this->view('blog::admin/add'), true);
    }

    public function edit() {
        $this->adminSecure('manage-blogs');
        $this->setTitle(l('edit-blog'));
        $blogId = $this->request->segment(3);
        $blog = $this->model('blog::blog')->findBlog($blogId, true);
        if (!$blog) return $this->request->redirect(url('admin/blogs'));
        $this->useEditor = true;
        if ($val = $this->request->input('val', null, false)) {
            if ($this->isDemo()) $this->defendDemo();
            //$val['content'] = $_POST['content'];
            $validator = Validator::getInstance()->scan($val, array(
                'title' => 'required',
                'desc' => 'required',
                //'content' => 'required',
                'tags' => 'required',
                'category' => 'required',
            ));

            if ($blogArt = $this->request->inputFile('img')) {
                $artUpload = new Uploader($blogArt);
                $artUpload->setPath("blogs/".'preview/'.date('Y').'/');
                if ($artUpload->passed()) {
                    $val['image'] = $artUpload->resize()->result();
                } else {
                    return json_encode(array(
                        'message' => $artUpload->getError(),
                        'type' => 'error'
                    ));
                }
            }
            if ($validator->passes()) {
                $this->model('blog::blog')->save($val,$blog);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/blogs'),
                    'message' => l('blogs-saved')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        return $this->render($this->view('blog::admin/edit', array('blog' => $blog)), true);
    }
}