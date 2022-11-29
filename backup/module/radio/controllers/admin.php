<?php
class AdminController extends Controller {
    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->addBreadCrumb(l('admin-panel'));
        $this->sideMenu = "admin/includes/side-menu";
    }
    public function index() {
        $this->adminSecure('manage-radio');
        $this->setTitle(l('radio::live-radio'));
        $genre = $this->request->input('genre', '');
        $user = $this->request->input('user', '');
        $term = $this->request->input('term', '');
        if ($action= $this->request->input('action') == 'delete') {
            if ($this->isDemo()) $this->defendDemo();
            $radio = $this->model('radio::radio')->findRadio($this->request->input('id'));
            $this->model('radio::radio')->delete($this->request->input('id'));
        }

        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $radio = $this->model('radio::radio')->findRadio($val['id']);
            $validator = Validator::getInstance()->scan($val, array(
                'titles' => 'required',
                'tags' => 'required'
            ));
            if ($validator->passes()) {

                $this->model('radio::radio')->add($val, $radio);

                return json_encode(array(
                    'type' => 'modal-url',
                    'value' => getFullUrl(true),
                    'message' => l('saved-success'),
                    'content' => "#editVideoModal-".$val['id']
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }

        }
        $radios = $this->model('radio::radio')->getAdminRadios($genre, $term, $user);
        return $this->render($this->view('radio::admin/index', array('radios' => $radios, 'genre' => $genre, 'term' => $term, 'user' => $user)), true);
    }
}