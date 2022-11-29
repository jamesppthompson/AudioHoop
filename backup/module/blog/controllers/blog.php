<?php
class BlogController extends Controller {
    public function viewArticle()
    {
        list($id) = explode('-',$this->request->segment(1));
        $this->model('blog::blog')->increamentViews($id);
        $blog = $this->model('blog::blog')->findBlog($id);
        $this->collapsed = true;
        $this->setTitle(format_output_text($blog['title']));
        $this->addBreadCrumb(l('articles'), url('articles'));
        $this->addBreadCrumb(format_output_text($blog['title']));


        $headerContent = '<meta property="og:image" content="'.url_img($blog['image'], 600).'"/>';
        $headerContent .= '<meta property="og:title" content="'.format_output_text($blog['title']).'"/>';
        $headerContent .= '<meta property="og:url" content="'.$this->model('blog::blog')->getUrl($blog).'"/>';
        $headerContent .= '<meta property="og:description" content="'.$blog['description'].'"/>';

        $headerContent .= '<meta property="twitter:image" content="'.url_img($blog['image'], 600).'"/>';
        $headerContent .= '<meta property="twitter:title" content="'.format_output_text($blog['title']).'"/>';
        $headerContent .= '<meta property="title:description" content="'.$blog['description'].'"/>';
        $this->addHeaderContent($headerContent);

        if (!$blog) return $this->request->redirect('articles');
        return $this->render($this->view('blog::view/index', array('blog' => $blog)), true);
    }

    public function lists() {
        $this->setTitle(l('articles'));
        $this->activeMenu = "articles";
        $this->addBreadCrumb(l('articles'));
        $this->collapsed = true;

        return $this->render($this->view('blog::lists', array(
            'category' => $this->request->input('category', 'all'),
            'term' => $this->request->input('term', '')
        )), true);
    }

    public function  paginate() {
        $data = perfectUnserialize($this->request->input('data'));
        $type = $data['type'];
        $typeId = $data['typeId'];
        $offset = $this->request->input('offset');
        $limit = config('blogs-list-per-page', 10)*2;
        $theOffset = ($offset == 0) ? config('blogs-list-per-page', 10) : $offset;
        $newOffset = $offset + $limit;

        $blogs = $this->model('blog::blog')->getBlogs($type, $typeId, $theOffset);

        $content =  '';
        foreach($blogs as $blog) {
            $content .= view('blog::format', array('blog' => $blog));
        }
        $result = array(
            'content' => $content,
            'offset' => $newOffset
        );
        return json_encode($result);
    }
}