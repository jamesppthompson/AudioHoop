<?php
Hook::getInstance()->register('main.menu.home', function () {
   echo view('blog::user-menu');
});

Hook::getInstance()->register('admin.settings.integrations', function() {
    echo view('blog::admin/settings/integration');
});




Hook::getInstance()->register('admin.menu.middle', function() {
    $active = getController()->activeMenu == 'blogs' ? 'active' : '';
    if(model('user')->hasRole('manage-blogs')) {
        echo '<li><a id="blogs-menu" data-ajax="true" class="sub-menu '.$active.'" href="'.url('admin/blogs').'"><i class="la la-sticky-note"></i>  '.l('blogs').'</a></li>';
    }
});

Hook::getInstance()->register('user.roles', function($roles) {
    $roles['manage-blogs'] = l('blog::blogs');
    return $roles;
});


Hook::getInstance()->register('share.details', function($result, $type, $id) {
    if ($type == 'blog') {
        $blog = model('blog::blog')->findBlog($id);
        $result['title'] = $blog['title'];
        $result['link'] = model('blog::blog')->getUrl($blog);
        $result['art'] = url_img($blog['image'], 600);
    }
    return $result;
});

Hook::getInstance()->register("comment.added", function($commentId, $type, $id) {
    if ($type == 'blog') {
        $blog  = model('blog::blog')->findBlog($id);
        if ($blog['userid'] != model('user')->authId) {
            $theUser = model('user')->getUser($blog['userid']);
            if ($theUser['notifyc']) {
                model('user')->addNotification($theUser['id'], 'comment-blog', $id);
                model('user')->sendSocialMail($theUser, 'comment','comment-blog', $id);
            }
        }
    }
});

Hook::getInstance()->register("comment.reply", function($commentId, $type, $id) {
    $theComment = model('track')->findComment($id);
    if ($theComment['type'] == 'blog') {
        //$video  = model('video::video')->find($theComment['typeid']);
        if ($theComment['userid'] != model('user')->authId) {
            $theUser = model('user')->getUser($theComment['userid']);
            if ($theUser['notifyc']) {
                model('user')->addNotification($theUser['id'], 'reply-comment-blog', $id);
                model('user')->sendSocialMail($theUser, 'comment','reply-comment-blog', $id);
            }
        }
    }
});

Hook::getInstance()->register('social.mail.comment', function($type, $id,$theUser) {
    switch($type) {
        case 'comment-blog':
            $blog = model('blog::blog')->findBlog($id);
            $link = model('blog::blog')->getUrl($blog);
            $mailer = Email::getInstance();
            $mailer->setAddress($theUser['email'],$theUser['full_name']);
            $mailer->setSubject(l('blog::new-comment-blog'));
            $content = l('blog::new-comment-blog-mail', array('blog' => $blog['title'], 'link' => $link));
            $mailer->setMessage($content);
            $mailer->send();
            break;
        case 'reply-comment-blog':
            $comment = model('track')->findComment($id);
            $blog = model('blog::blog')->findBlog($comment['typeid']);
            $link = model('blog::blog')->getUrl($blog);
            $mailer = Email::getInstance();
            $mailer->setAddress($theUser['email'],$theUser['full_name']);
            $mailer->setSubject(l('blog::new-reply-blog'));
            $content = l('blog::new-reply-blog-mail', array('video' => $blog['title'], 'link' => $link));
            $mailer->setMessage($content);
            $mailer->send();
            break;
    }
});

Hook::getInstance()->register('social.mail.like', function($type, $id,$theUser) {
    switch($type) {
        case 'like-blog':
            $blog = model('blog::blog')->findBlog($id);
            $link = model('blog::blog')->getUrl($blog);
            $mailer = Email::getInstance();
            $mailer->setAddress($theUser['email'],$theUser['full_name']);
            $mailer->setSubject(l('blog::new-like-blog'));
            $content = l('blog::new-like-blog-mail', array('video' => $blog['title'], 'link' => $link));
            $mailer->setMessage($content);
            $mailer->send();
            break;
    }
});

Hook::getInstance()->register('like.item', function($type,$typeId) {
    if($type == 'blog') {
        $blog = model('blog::blog')->findBlog($typeId);
        if ($blog['userid'] != model('user')->authId) {
            $theUser = model('user')->getUser($blog['userid']);
            if ($theUser['notifyl']) {
                model('user')->addNotification($theUser['id'], 'like-blog', $typeId);
            }
            model('user')->sendSocialMail($theUser, 'like','like-blog', $typeId);
        }
    }
});

Hook::getInstance()->register('notification.format', function($notification){
    switch($notification['type']) {
        case 'comment-blog':
            $blog = model('blog::blog')->findBlog($notification['typeid']);
            if($blog['userid'] == model('user')->authId) {
                $notification['title'] = l('blog::commented-your-blog').' <strong>'.str_limit($blog['title'], 35).'</strong>';
            } else {
                $notification['title'] = l('blog::commented-this-blog').' <strong>'.str_limit($blog['title'], 35).'</strong>';
            }
            $notification['link'] = model('blog::blog')->getUrl($blog);
            break;
        case 'reply-comment-blog':
            $comment = model('track')->findComment($notification['typeid']);
            $blog = model('blog::blog')->findBlog($comment['typeid']);
            $notification['link'] = model('blog::blog')->getUrl($blog);
            $notification['title'] = l('blog::replied-your-comment-blog').' <strong>'.str_limit($blog['title'], 35).'</strong>';
            break;
        case 'like-blog':
            $blog = model('blog::blog')->findBlog($notification['typeid']);
            $notification['title'] = l('blog::like-your-blog').' <strong>'.str_limit($blog['title'], 35).'</strong>';
            $notification['link'] = model('blog::blog')->getUrl($blog);
            break;
    }
    return $notification;
});



$request->any("admin/blogs", array('uses' => 'blog::admin@index'));
$request->any("admin/blogs/add", array('uses' => 'blog::admin@add'));
$request->any("admin/blogs/edit/{id}", array('uses' => 'blog::admin@edit'))->where(array('id' => '[0-9]+'));
$request->any("articles", array('uses' => 'blog::blog@lists', 'secure' => false));
$request->any("article/paginate", array('uses' => 'blog::blog@paginate', 'secure' => false));
$request->any("article/{id}", array('uses' => 'blog::blog@viewArticle', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));
