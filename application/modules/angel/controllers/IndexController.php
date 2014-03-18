<?php

class Angel_IndexController extends Angel_Controller_Action
{
    protected $login_not_required = array('index', 'login', 'register', 'email-validation', 'is-email-exist', 'forgot-password');
    
    public function init()
    {
        parent::init();
    }

    public function indexAction()
    {
        $this->_forward('login');
    }
    
    /**
     * 登陆 
     */
    public function loginAction(){
        if($this->request->isXmlHttpRequest() && $this->request->isPost()){
            $email = $this->request->getParam('email');
            $password = $this->request->getParam('pwd');
            $remember = $this->request->getParam('remember', 'no');
            
            try{
                $userModel = $this->getModel('user');
                $auth = $userModel->auth($email, $password);
                
                $success = false;
                
                if($auth['valid'] === true){
                    $ip = $this->getRealIpAddr();
                    $result = $userModel->updateLoginInfo($auth['msg'], $ip);
                    
                    if($result){
                        if($remember == 'yes'){
                            setcookie($this->bootstrap_options['cookie']['remember_me'], $userModel->getRememberMeValue($auth['msg'], $ip), time()+$this->bootstrap_options['token']['expiry']['remember_me']*60, '/', $this->bootstrap_options['site']['domain']);
                        }
                        $success = true;
                    }
                }
                
                $this->_helper->json(($success === true) ? 1 : 0);
            }
            catch(Angel_Exception_User $e){
                $this->_helper->json(0);
            }
        }
    }
    
    /**
     * 注册 
     */
    public function registerAction(){
        if($this->request->isXmlHttpRequest() && $this->request->isPost()){
            
            $user_type = $this->request->getParam('usertype');
            $email = $this->request->getParam('email');
            $username = $this->request->getParam('username');
            $password = $this->request->getParam('pwd');
            
            $result = false;
            try{
                $userModel = $this->getModel('user');
                $result = $userModel->addUser($user_type, $email, $username, $password, Zend_Session::getId());
            }
            catch(Angel_Exception_User $e){
                $this->_helper->json($e->getDetail());
            }
            
            $this->_helper->json(($result === false) ? 0 : 1);
        } else {
            $guarantor_register = $this->request->getParam('guarantor_register');
            if($guarantor_register == '1') {
                // 获取token内容
                $token = $this->request->getParam('token');
                if(empty($token)) {
                    $this->_redirect($this->view->url(array(), 'not-found'));
                }
                $tokenDocument = $this->getModel('token')->getTokenByToken($token);
                if($tokenDocument && !$tokenDocument->isExpired()){
                    $guarantor_email = $tokenDocument->params['email'];
                    if(empty($guarantor_email)) {
                        $this->_redirect($this->view->url(array(), 'not-found'));
                    }
                    $guarantee_company = $tokenDocument->params['company'];
                    $company = $this->getModel('company')->getCompanyById($guarantee_company);
                    if(!isset($company)) {
                        $this->_redirect($this->view->url(array(), 'not-found'));
                    }
                    $guarantors = $company->guarantor_candidate;
                    if(!isset($guarantors) || count($guarantors) == 0) {
                        $this->_redirect($this->view->url(array(), 'not-found'));
                    }
                    $hasIt = false;
                    $guarantor_name = "";
                    foreach($guarantors as $guarantor) {
                        if($guarantor->email == $guarantor_email) {
                            $hasIt = true;
                            $guarantor_name = $guarantor->name;
                            break;
                        }
                    }
                    if(!$hasIt) {
                        $this->_redirect($this->view->url(array(), 'not-found'));
                    }
                    $this->view->guarantor_email = $guarantor_email;
                    $this->view->guarantor_name = $guarantor_name;
                } else {
                    $this->_redirect($this->view->url(array(), 'not-found'));
                }
                $this->view->guarantor_register = 1;
            }
        }
    }
    
    /**
     * 验证email地址，激活帐号 
     */
    public function emailValidationAction(){
        $result = false;
        $token = $this->request->getParam('token', '');
        
        try{
            $result = $this->getModel('user')->activateAccount($token);
        }
        catch(Angel_Exception_User $e){
            if($e->getMessageCode() == Angel_Exception_User::EMAIL_VALIDATION_TOKEN_INVALID){
                $this->_redirect($this->view->url(array(), 'not-found'));
            }
            else if($e->getMessageCode() == Angel_Exception_User::EMAIL_VALIDATION_VALIDATED_USER){
                $this->_forward('login', 'index');
            }
            else if($e->getMessageCode() == Angel_Exception_User::EMAIL_VALIDATION_TOKEN_EXPIRED){
                $this->view->isTokenExpired = true;
            }
        }
        
        if($result){
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
    public function waitToBeActivatedAction(){
        $uid = $this->me->getId();
        $userModel = $this->getModel('user');
        
        $user = $userModel->getUserById($uid);
        
        if($user->isEmailValidated()){
            $this->_redirect($this->view->url(array(), 'dashboard'));
        }
        
        if($this->request->isXmlHttpRequest() && $this->request->isPost()){
            $userModel->sendAccountValidationEmail($user);
            $this->_helper->json(1);
        }
        else{
            $this->view->user = $user;
        }
    }
    
    public function isEmailExistAction(){
        if($this->request->isXmlHttpRequest() && $this->request->isPost()){
            
            $email = $this->request->getParam('email');
            $result = false;
            try{
                $userModel = $this->getModel('user');
                $result = $userModel->isEmailExist($email);
            }
            catch(Angel_Exception_User $e){
                $this->_helper->json(0);
            }
            // email已经存在返回1，不存在返回0
            $this->_helper->json(($result === false) ? 0 : 1);
        }
    }
    
    public function forgotPasswordAction(){
        if($this->request->isXmlHttpRequest() && $this->request->isPost()){
            
            $email = $this->request->getParam('email');
            $result = false;
            try{
                $userModel = $this->getModel('user');
                $result = $userModel->forgotPassword($email);
            }
            catch(Angel_Exception_User $e){
                $this->_helper->json(0);
            }
            $this->_helper->json(($result === false) ? 0 : 1);
        }
    }
    
    public function logoutAction(){
        Zend_Auth::getInstance()->clearIdentity();
        
        $angel = $this->request->getCookie($this->bootstrap_options['cookie']['remember_me']);
        if(!empty($angel)){
            $this->getModel('token')->disableToken($angel);
        }
        
        $this->_redirect($this->view->url(array(), 'login'));
    }
}
