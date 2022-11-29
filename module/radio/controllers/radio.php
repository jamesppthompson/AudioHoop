<?php
class RadioController extends Controller {

    public function __construct($request)
    {
        parent::__construct($request);
        $this->activeMenu = "radios";
    }
    public function index() {
        $this->setTitle(l('radio::live-radio'));
        $page = $this->request->segment(1, 'overview');
        $this->useBreadcrumbs = false;
        $this->setTitle(l('radio::live-radio'));
        switch ($page) {
            case 'all':
                $content = $this->view('radio::list/lists', array('type' => 'latest', 'typeid' => ($this->request->input('premium')) ? 1 : ''));
                break;
            case 'top':
                $content = $this->view('radio::list/lists', array('type' => 'top', 'typeid' => null));
                break;
            case 'category':
                $content = $this->view('radio::list/lists', array('type' => 'category', 'typeid' => $this->request->segment(2)));
                break;
            default:
                $content = $this->view('radio::list/overview');
                break;
        }
        return $this->render($this->view('radio::list/layout', array('content' => $content, 'page' => $page)), true);

    }

    public function paginate() {
        $data = perfectUnserialize($this->request->input('data'));
        $type = $data['type'];
        $typeId = $data['typeId'];
        $offset = $this->request->input('offset');
        $limit = config('radio-limit', 20);
        $theOffset = ($offset == 0) ? config('radio-limit', 20) : $offset;
        $newOffset = $theOffset + $limit;

        $radios = $this->model('radio::radio')->getRadios($type, $typeId, $theOffset, $limit);

        $content =  $this->view('radio::list/paginate', array('radios' => $radios));
        $result = array(
            'content' => $content,
            'offset' => $newOffset
        );
        return json_encode($result);
    }


    public function add() {
        $this->setTitle(l('radio::add-radio'));
        $this->addBreadCrumb(l('radio::live-radio'), url('radio'));

        $this->addBreadCrumb(l('radio::add-radio'));
        $this->collapsed = true;
        if (!$this->model('radio::radio')->canAddRadio()) return $this->request->redirectBack();
        $radioId = $radioId2 = $this->request->input('id');
        $radio = ($radioId) ? $this->model('radio::radio')->findRadio($radioId) : null;

        if ($action = $this->request->input('action')) {
            $this->model('radio::radio')->delete($radioId);
            return $this->request->redirect(url('radio'));
        }
        if ($val = $this->request->input('val')) {
            $validator = Validator::getInstance()->scan($val, array(
                'title' => 'required',
                'link' => 'required',
            ));

            if ($validator->passes()) {
                if ($radioArt = $this->request->inputFile('img')) {
                    $artUpload = new Uploader($radioArt);
                    $artUpload->setPath("radio/".$this->model('user')->authId.'/art/'.date('Y').'/');
                    if ($artUpload->passed()) {
                        $val['art'] = $artUpload->resize()->result();
                    } else {
                        return json_encode(array(
                            'message' => $artUpload->getError(),
                            'type' => 'error'
                        ));
                    }
                }
                $radioId = $this->model('radio::radio')->add($val, $radio);
                $radio = $this->model('radio::radio')->findRadio($radioId);
                return json_encode(array(
                    'status' => 1,
                    'type' => 'url',
                    'value' => $this->model('radio::radio')->radioUrl($radio),
                    'message' => ($radioId2) ? l('radio::radio-edit-success') : l('radio::radio-add-success')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }

        }

        return $this->render($this->view('radio::upload/index', array('radio' => $radio)), true);
    }

    public function renderButtons() {
        $radioId = $this->request->input('radio');

        $radio = $this->model('radio::radio')->findRadio($radioId);

        if ($radio) {
            echo $this->view('radio::action-buttons', array('radio' => $radio));
        }
    }
    
    public function profile() {
        $this->loginRequired = false;
        $slug = explode('-', $this->request->segment(1));
        $radioId = $slug[0];
        $radio = $this->model('radio::radio')->findRadio($radioId);
        if (!$radio) return $this->errorPage();
        $this->setTitle(format_output_text($radio['title']), true);
        $this->addBreadCrumb(format_output_text($radio['title']) , $this->model('radio::radio')->radioUrl($radio));

        $headerContent = '<meta property="og:image" content="'.$this->model('radio::radio')->getArt($radio, 600).'"/>';
        $headerContent .= '<meta property="og:title" content="'.format_output_text($radio['title']).'"/>';
        $headerContent .= '<meta property="og:url" content="'.$this->model('radio::radio')->radioUrl($radio).'"/>';
        $headerContent .= '<meta property="og:description" content="'.$radio['description'].'"/>';

        $headerContent .= '<meta property="twitter:image" content="'.$this->model('radio::radio')->getArt($radio, 600).'"/>';
        $headerContent .= '<meta property="twitter:title" content="'.format_output_text($radio['title']).'"/>';
        $headerContent .= '<meta property="title:description" content="'.$radio['description'].'"/>';
        $this->addHeaderContent($headerContent);

        $this->useBreadcrumbs = false;


        return $this->render($this->view('radio::profile/index', array('radio' => $radio)), true);
    }

    public function setViews() {
        $radioId = $this->request->input('track');
        $this->model('radio::radio')->setView($radioId);
        return $this->model('radio::radio')->getCurrentListeners($radioId);
    }
}