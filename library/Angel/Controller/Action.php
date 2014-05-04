<?php

/**
 * Description of Action
 *
 * @author powerdream5
 */
class Angel_Controller_Action extends Zend_Controller_Action {

    protected $bootstrap = null;
    protected $bootstrap_options = null;
    protected $request = null;
    protected $session = null;
    protected $me = null;
    protected $models = array();
    // service container
    protected $_container = null;
    protected $_logger = null;
    protected $login_not_required = array();

    public function init() {
        parent::init();

        $this->bootstrap = $this->getInvokeArg('bootstrap');
        $this->bootstrap_options = $this->bootstrap->getOptions();

        $this->request = $this->getRequest();
        $this->session = new Zend_Session_Namespace();

        // get the DI service container
        $this->_container = $this->bootstrap->getResource('serviceContainer');

        $this->_logger = $this->bootstrap->getResource('logger');

        // diable the layout and set no render when the request is an ajax request
        if ($this->request->isXMLHttpRequest()) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        }

        // some global variable
        $this->view->currency = $this->bootstrap_options['currency'];
        $this->view->currency_symbol = $this->bootstrap_options['currency_symbol'];
    }

    /**
     * 对用户的各项状态进行检测 
     */
    public function preDispatch() {

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $this->getModel('user')->getUserById($auth->getIdentity());
            if (!$user) {
                if (!in_array($this->request->getActionName(), $this->login_not_required)) {
                    $this->_redirect($this->view->url(array(), 'manage-register'));
                }
            } else {
                $this->me = new Angel_Me($user);
                $this->view->me = $this->me;
            }
        } else {
            if ($this->checkRememberMe() === true) {
                $this->me = new Angel_Me($this->getModel('user')->getUserById($auth->getIdentity()));
                $this->view->me = $this->me;
            } else {
                if (!in_array($this->request->getActionName(), $this->login_not_required)) {
                    $this->_redirect($this->view->url(array(), 'manage-login') . '?goto=' . $this->request->getRequestUri());
                }
            }
        }

        // 如果用户还没被激活，跳转到激活页
        if ($this->me) {
            if (!$this->me->isActivated()) {
                $router = Zend_Controller_Front::getInstance()->getRouter()->getCurrentRouteName();
                if (!($router == 'wait-to-be-activated' || $router == 'email-validation')) {
                    $this->_redirect($this->view->url(array(), 'wait-to-be-activated'));
                }
            }
        }
    }

    protected function checkRememberMe() {
        $result = false;

        $angel = $this->request->getCookie($this->bootstrap_options['cookie']['remember_me']);
        if (!empty($angel)) {
            $result = $this->getModel('user')->isRemembered($angel, $this->getRealIpAddr());
        }

        return $result;
    }

    /**
     * A helper method to get the model object in controller
     * @param string $modelName
     * @return Model Object 
     */
    protected function getModel($modelName) {
        $modelName = 'Angel_Model_' . ucwords($modelName);
        if (!isset($models[$modelName])) {
            $models[$modelName] = new $modelName($this->bootstrap);
        }

        return $models[$modelName];
    }

    /**
     * 获得访问用户的真实ip
     * @return type 
     */
    protected function getRealIpAddr() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * 当用户下载或查看文件 
     */
    protected function outputFile($fd, $filepath, $filename) {
        $fsize = filesize($filepath);
        $path_parts = pathinfo($filepath);
        $ext = strtolower($path_parts['extension']);

        switch ($ext) {
            case 'pdf':
                header('Content-type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                break;
            case 'doc':
            case 'docx':
                header('Content-type: application/msword');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                break;
            case 'xls':
            case 'xlsx':
                header('Content-type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                break;
            case 'jpg':
            case 'jpeg':
                header('Content-type: image/jpeg');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                break;
            case 'png':
                header('Content-type: image/png');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                break;
            default:
                header('Content-type: application/octet-stream');
                header('Content-Disposition: filename="' . $filename . '"');
        }
        header('Content-length: ' . $fsize);
        header('Cache-control: private');

        while (!feof($fd)) {
            $buffer = fread($fd, 2048);
            echo $buffer;
        }
    }

}
