<?php
class AdminController extends Controller {
    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->addBreadCrumb(l('admin-panel'));
        $this->sideMenu = "admin/includes/side-menu";
        $this->activeMenu = "advert";
    }

    public function index() {
        $this->adminSecure('manage-user-ads');
        $this->setTitle(l('advert::manage-user-ads'));

        if ($action = $this->request->input('action')) {
            //$this->model('blog::blog')->delete();
            $adsId = $this->request->input('id');
            if ($action == 'enable') {
                $this->model('advert::advert')->adminToggleStatus($adsId);
                return $this->request->redirect(url('admin/adverts'));
            } elseif($action == 'delete') {
                $this->model('advert::advert')->delete($adsId);
                return $this->request->redirect(url('admin/adverts'));
            }
        }

        $ads = $this->model('advert::advert')->getAdminLists();
        return $this->render($this->view('advert::admin/index', array( 'ads' => $ads)), true);
    }

}