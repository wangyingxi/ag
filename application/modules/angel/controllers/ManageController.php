<?php

class Angel_ManageController extends Angel_Controller_Action {

    protected $login_not_required = array(
        'login',
        'register',
        'logout'
    );

    protected function getTmpFile($uid) {
        $utilService = $this->_container->get('util');
        $result = $utilService->getTmpDirectory() . '/' . $uid;
        return $result;
    }

    public function init() {
        parent::init();

        $this->_helper->layout->setLayout('manage');
    }

    public function indexAction() {
        
    }

    public function registerAction() {
        if ($this->request->isPost()) {
            // POST METHOD
            $email = $this->request->getParam('email');
            $password = $this->request->getParam('password');

            $result = false;
            try {
                $userModel = $this->getModel('user');
                $isEmailExist = $userModel->isEmailExist($email);
                if ($isEmailExist) {
                    $result = false;
                }
                $result = $userModel->addManageUser($email, $password, Zend_Session::getId(), false);
            } catch (Angel_Exception_User $e) {
                $this->_helper->json($e->getDetail());
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-login') . '?register=success');
            }
        } else {
            // GET METHOD
            $this->view->title = "注册成为管理员";
        }
    }

    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();

        $angel = $this->request->getCookie($this->bootstrap_options['cookie']['remember_me']);
        if (!empty($angel)) {
            $this->getModel('token')->disableToken($angel);
        }

        $this->_redirect($this->view->url(array(), 'manage-login'));
    }

    public function loginAction() {
        if ($this->request->isPost()) {
            $email = $this->request->getParam('email');
            $password = $this->request->getParam('password');
            // remember's value: on or null
            $remember = $this->request->getParam('remember', 'off');

            try {
                $userModel = $this->getModel('user');
                $auth = $userModel->auth($email, $password);

                $success = false;
                $error = "登录失败，请重试或修改密码";
                if ($auth['valid'] === true) {
                    $ip = $this->getRealIpAddr();
                    $result = $userModel->updateLoginInfo($auth['msg'], $ip);

                    if ($result) {
                        if ($remember == 'on') {
                            setcookie($this->bootstrap_options['cookie']['remember_me'], $userModel->getRememberMeValue($auth['msg'], $ip), time() + $this->bootstrap_options['token']['expiry']['remember_me'] * 60, '/', $this->bootstrap_options['site']['domain']);
                        }
                        $success = true;
                    }
                }
            } catch (Angel_Exception_User $e) {
                $error = $e->getMessage();
            }
            if ($success) {
                $goto = $this->getParam('goto');
                $url = $this->view->url(array(), 'manage-index');
                if ($goto) {
                    $url = $goto;
                }
                $this->_redirect($url);
            } else {
                $this->view->error = "登录失败，请重试或修改密码";
            }
        } else {
            if ($this->getParam('register') == 'success') {
                $this->view->register = 'success';
            }
        }
        $this->view->title = "管理员登录";
    }

    public function productListAction() {
        
    }

    public function productCreateAction() {
        $this->view->title = "创建商品";
    }

    public function productEditAction() {
        
    }

    public function productDeleteAction() {
        
    }

    public function photoCreateAction() {

        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $tmp = $this->getParam('tmp');
            $file = $this->getTmpFile($tmp);
//            $owener = $this->me->getUser();
            $photoModel = $this->getModel('photo');
            try {
                $destination = $this->getTmpFile($tmp);
//                $result = $photoModel->addPhoto($destination, $owener);
                $result = $photoModel->addPhoto($destination);
                echo $result;
                exit;
                if ($result) {
                    $result = 1;
                }
            } catch (Exception $e) {
                // image is not accepted
                $result = 2;
            }
            echo $result;
            exit;
        } else {
            // GET METHOD
            $fs = $this->getParam('fs');
            if ($fs) {
                $this->view->fileList = array();
                $f = explode("|", $fs);
                foreach ($f as $k => $v) {
                    $this->view->fileList[] = array('v' => $v, 'p' => $this->getTmpFile($v));
                }
            }
            $this->view->title = "确认保存图片";
        }
    }

    public function photoUploadAction() {
        if ($this->request->isPost()) {
            // POST METHOD
            $result = 0;
            $upload = new Zend_File_Transfer();

            $upload->addValidator('Size', false, 5120000); //5M

            $uid = uniqid();
            $destination = $this->getTmpFile($uid);

            $upload->addFilter('Rename', $destination);

            if ($upload->isValid()) {
                if ($upload->receive()) {
                    $result = $uid;
                }
            }
            echo $result;
            exit;
        } else {
            // GET METHOD
            $this->view->title = "上传图片";
        }
    }

    public function photoClearcacheAction() {
        if ($this->request->isPost()) {
            // POST METHOD
            $result = 0;
            $utilService = $this->_container->get('util');
            $tmp = $utilService->getTmpDirectory();

            try {
                if ($od = opendir($tmp)) {
                    while ($file = readdir($od)) {

                        unlink($tmp . DIRECTORY_SEPARATOR . $file);
                    }
                }
                echo 1;
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            exit;
        }
    }

    public function photoListAction() {
        $page = $this->request->getParam('page');
        if (!$page) {
            $page = 1;
        }
        $photoModel = $this->getModel('photo');
        $paginator = $photoModel->getPhoto();
        $paginator->setItemCountPerPage(40);
        $paginator->setCurrentPageNumber($page);
        $resource = array();
        foreach ($paginator as $r) {
            $resource[] = array('path' => array('orig' => $this->view->photoImage($r->name . $r->type), 'main' => $this->view->photoImage($r->name . $r->type, 'main'), 'xlarge' => $this->view->photoImage($r->name . $r->type, 'xlarge'), 'small' => $this->view->photoImage($r->name . $r->type, 'small'), 'large' => $this->view->photoImage($r->name . $r->type, 'large')),
                'name' => $r->name,
                'type' => $r->type);
        }
        $this->view->paginator = $paginator;
        $this->view->resource = $resource;
        $this->view->title = "图片列表";
    }

//    public function personalInfoListAction() {
//        $userModel = $this->getModel('user');
//        $paginator = $userModel->getAllUsersInWaitTobeValidatedList();
//        $paginator->setItemCountPerPage(100);
//        $paginator->setCurrentPageNumber(1);
//        $this->view->users = $paginator;
//        $this->view->userModel = $userModel;
//        $this->view->type_identity_front = \Angel_Model_User::FILETYPE_IDENTITY_FRONT;
//        $this->view->type_identify_back = \Angel_Model_User::FILETYPE_IDENTITY_BACK;
//    } 
//    public function companyInfoListAction() {
//        $companyModel = $this->getModel('company');
//        $paginator = $companyModel->getAllCompanyInWaitTobeValidatedList();
//        $paginator->setItemCountPerPage(100);
//        $paginator->setCurrentPageNumber(1);
//        $this->view->companies = $paginator;
//        $this->view->companyModel = $companyModel;
//        $this->view->type_funding_specification = \Angel_Model_Company::FILETYPE_FUND_SPEC;
//        $this->view->type_business_licence = \Angel_Model_Company::FILETYPE_BUSINESS_LICENCE;
//        $this->view->type_funder_specification = \Angel_Model_Company::FILETYPE_FUNDER_SPEC;
//        $this->view->type_funder_decision = \Angel_Model_Company::FILETYPE_FUNDER_DECISION;
//        $this->view->type_credit_reports = \Angel_Model_Company::FILETYPE_CREDIT_REPORTS;
//        $this->view->type_personal_credit_reports = \Angel_Model_Company::FILETYPE_PERSONAL_CREDIT_REPORTS;
//        $this->view->type_financial_reports = \Angel_Model_Company::FILETYPE_FINANCIAL_REPORT;
//        $this->view->type_support_files = \Angel_Model_Company::FILETYPE_SUPPORT_FILES;
//    }
//    
//    public function readyCompanyInfoListAction() {
//        $companyModel = $this->getModel('company');
//        $paginator = $companyModel->getCompanyInReady(true);
//        
//        $paginator->setItemCountPerPage(100);
//        $paginator->setCurrentPageNumber(1);
//        $this->view->companies = $paginator;
//    }
//    
//    public function refusedIdentityInfoAction() {
//        if($this->request->isPost()){
//            $userModel = $this->getModel('user');
//            $user_id = $this->request->getParam('user_id');
//            $reason = $this->request->getParam('reason');
//            $result = $userModel->refuseIdentityInfo($user_id, $reason);
//
//            $this->_helper->json($result ? 1 : 0);
//        }
//    }
//    
//    public function acceptIdentityInfoAction() {
//        if($this->request->isPost()){
//            $userModel = $this->getModel('user');
//            $user_id = $this->request->getParam('user_id');
//            $result = $userModel->acceptIdentityInfo($user_id);
//
//            $this->_helper->json($result ? 1 : 0);
//        }
//    }
//    
//    public function refusedCompanyInfoAction() {
//        if($this->request->isPost()){
//            $companyModel = $this->getModel('company');
//            $company_id = $this->request->getParam('company_id');
//            $reason = $this->request->getParam('reason');
//            $result = $companyModel->refuseCompanyInfo($company_id, $reason);
//
//            $this->_helper->json($result ? 1 : 0);
//        }
//    }
//    
//    public function acceptCompanyInfoAction() {
//        if($this->request->isPost()) {
//            $companyModel = $this->getModel('company');
//            $company_id = $this->request->getParam('company_id');
//            $result = $companyModel->acceptCompanyInfo($company_id);
//            
//            $this->_helper->json($result ? 1 : 0);
//        }
//    }
//    
//    public function triggerFundingAction(){
//        $company_id = $this->request->getParam('id');
//        
//        $result = false;
//        try{
//            $result = $this->getModel('company')->triggerFunding($company_id);
//        }
//        catch(Angel_Exception_Common $e){
//            $this->_logger->info(__CLASS__, __FUNCTION__, $e->getMessage()."\n".$e->getTraceAsString());
//        }
//        
//        $this->_helper->json(($result===true) ? 1 : 0);
//    }
}
