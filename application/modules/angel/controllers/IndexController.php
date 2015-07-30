<?php

class Angel_IndexController extends Angel_Controller_Action {

    protected $login_not_required = array('index', 'login', 'register', 'email-validation', 'is-email-exist', 'forgot-password');

    public function init() {
        parent::init();
    }

    public function indexAction() {
//        $this->_forward('login');
        $productModel = $this->getModel('product');
        $paginator = $productModel->getAll(false);
        $resource = array();
        foreach ($paginator as $r) {
            $path = $this->bootstrap_options['image_broken_ico']['middle'];
            if (count($r->photo)) {
                try {
                    if ($r->photo[0]->name) {
                        $path = $this->view->photoImage($r->photo[0]->name . $r->photo[0]->type, 'main');
                    }
                } catch (Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
                    // 图片被删除的情况
                }
            }

            $resource[] = array('title' => $r->title,
                'id' => $r->id,
                'sub_title' => $r->sub_title,
                'location' => $r->location,
                'path' => $path);
        }
        $this->view->products = $resource;
    }

    public function testAction() {
//        $this->_helper->json(1);
//        var_dump('hahaha');exit;
    }
    
    public function aboutAction() {

    }

    /**
     * 登录
     */
    public function loginAction() {
        $this->userLogin('home', "Welcome to login");
    }

    /**
     * 注册 
     */
    public function registerAction() {
        $this->userRegister('login', "Welcome to register", "user");
    }

    /**
     * 验证email地址，激活帐号 
     */
    public function emailValidationAction() {
        $result = false;
        $token = $this->request->getParam('token', '');

        try {
            $result = $this->getModel('user')->activateAccount($token);
        } catch (Angel_Exception_User $e) {
            if ($e->getMessageCode() == Angel_Exception_User::EMAIL_VALIDATION_TOKEN_INVALID) {
                $this->_redirect($this->view->url(array(), 'not-found'));
            } else if ($e->getMessageCode() == Angel_Exception_User::EMAIL_VALIDATION_VALIDATED_USER) {
                $this->_forward('login', 'index');
            } else if ($e->getMessageCode() == Angel_Exception_User::EMAIL_VALIDATION_TOKEN_EXPIRED) {
                $this->view->isTokenExpired = true;
            }
        }

        if ($result) {
            $this->view->isValidatedSuccess = true;
            $this->view->user = $result;

            // 当用户是在登录状态下激活帐号，帐号激活成功后，应该强迫它退出登录
            Zend_Auth::getInstance()->clearIdentity();
            $this->view->me = null;
        }
    }

    /**
     * 当用户email还没有验证通过(帐户帐号还未激活)时，当他访问其它页面时，应该强行跳转到这个页面 
     */
    public function waitToBeActivatedAction() {
        $uid = $this->me->getId();
        $userModel = $this->getModel('user');

        $user = $userModel->getUserById($uid);

        if ($user->isEmailValidated()) {
            $this->_redirect($this->view->url(array(), 'dashboard'));
        }

        if ($this->request->isXmlHttpRequest() && $this->request->isPost()) {
            $userModel->sendAccountValidationEmail($user);
            $this->_helper->json(1);
        } else {
            $this->view->user = $user;
        }
    }

    public function isEmailExistAction() {
        if ($this->request->isXmlHttpRequest() && $this->request->isPost()) {

            $email = $this->request->getParam('email');
            $result = false;
            try {
                $userModel = $this->getModel('user');
                $result = $userModel->isEmailExist($email);
            } catch (Angel_Exception_User $e) {
                $this->_helper->json(0);
            }
            // email已经存在返回1，不存在返回0
            $this->_helper->json(($result === false) ? 0 : 1);
        }
    }

    public function forgotPasswordAction() {
        if ($this->request->isXmlHttpRequest() && $this->request->isPost()) {

            $email = $this->request->getParam('email');
            $result = false;
            try {
                $userModel = $this->getModel('user');
                $result = $userModel->forgotPassword($email);
            } catch (Angel_Exception_User $e) {
                $this->_helper->json(0);
            }
            $this->_helper->json(($result === false) ? 0 : 1);
        } else {
            $this->view->title = 'Forgot password';
        }
    }

    public function logoutAction() {
        $this->userLogout('cart');
    }

}
