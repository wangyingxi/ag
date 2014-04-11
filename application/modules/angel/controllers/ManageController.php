<?php

class Angel_ManageController extends Angel_Controller_Action {

    protected $login_not_required = array(
        'login',
        'register',
        'logout'
    );
    protected $SEPARATOR = ';';

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
            if ($email) {
                $email = strtolower($email);
            }
            $password = $this->request->getParam('password');

            $result = false;
            $error = "";
            try {
                $userModel = $this->getModel('user');
                $isEmailExist = $userModel->isEmailExist($email);
                if ($isEmailExist) {
                    $error = "该邮箱已经存在，不能重复注册";
                } else {
                    $result = $userModel->addManageUser($email, $password, Zend_Session::getId(), false);
                }
            } catch (Angel_Exception_User $e) {
//                $this->_helper->json($e->getDetail());
                $error = $e->getDetail();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-login') . '?register=success');
            } else {
                $this->view->error = $error;
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
            if ($email) {
                $email = strtolower($email);
            }
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

    public function checkSkuAction() {
        if ($this->request->isPost()) {
            $sku = $this->request->getParam('sku');
            if ($sku) {
                $sku = strtolower($sku);
                $productModel = $this->getModel('product');
                $result = $productModel->isSkuExist($sku);
                if ($result)
                    echo 1;
                else
                    echo 0;
            } else {
                // 空SKU均可使用
                echo 0;
            }
            exit;
        }
    }

    public function productListAction() {
        $page = $this->request->getParam('page');
        if (!$page) {
            $page = 1;
        }
        $productModel = $this->getModel('product');
        $paginator = $productModel->getAll();
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);
        $resource = array();
        foreach ($paginator as $r) {
            $path = 'default path';
            if (count($r->photo)) {
                $path = $this->view->photoImage($r->photo[0]->name . $r->photo[0]->type, 'main');
            }

            $resource[] = array('title' => $r->title,
                'id' => $r->id,
                'sub_title' => $r->sub_title,
                'location' => $r->location,
                'path' => $path,
                'owner' => $r->owner);
        }

        // JSON FORMAT
        if ($this->getParam('format') == 'json') {
            $this->_helper->json(array('data' => $resource,
                'code' => 200,
                'page' => $paginator->getCurrentPageNumber(),
                'count' => $paginator->count()));
        } else {
            $this->view->paginator = $paginator;
            $this->view->resource = $resource;
            $this->view->title = "商品列表";
        }
    }

    public function productCreateAction() {
        if ($this->request->isPost()) {
            // POST METHOD
            $title = $this->request->getParam('title');
            $short_title = $this->request->getParam('short_title');
            $sub_title = $this->request->getParam('sub_title');
            $sku = $this->request->getParam('sku');
            $status = $this->request->getParam('status');
            $description = $this->request->getParam('description');

            $photo = $this->request->getParam('photo');
            if ($photo) {
                $photo = json_decode($photo);
                $photoModel = $this->getModel('photo');
                $photoArray = array();
                foreach ($photo as $name => $path) {
                    $photoObj = $photoModel->getPhotoByName($name);
                    if ($photoObj) {
                        $photoArray[] = $photoObj;
                    }
                }
                $photo = $photoArray;
            }

            $location = $this->request->getParam('location');
            if ($location) {
                $tmp = split($this->SEPARATOR, $location);
                if (count($tmp)) {
                    $location = $tmp;
                }
            }

            $base_price = floatval($this->request->getParam('base_price'));

            $selling_price = array();
            foreach ($this->bootstrap_options['currency'] as $key => $val) {
                $price = $this->request->getParam('price_' . $key);
                if ($price) {
                    $selling_price[$key] = floatval($price);
                }
            }
            $scale = array();
            $scale['weight'] = $this->request->getParam('scale_weight');
            $scale['height'] = $this->request->getParam('scale_height');
            $scale['width'] = $this->request->getParam('scale_width');
            $scale['length'] = $this->request->getParam('scale_length');

            $result = false;
            $error = "";
            try {
                $productModel = $this->getModel('product');
                $isSkuExist = false;
                if ($sku) {
                    $isSkuExist = $productModel->isSkuExist($sku);
                }
                if ($isSkuExist) {
                    $error = "该SKU已经存在，不能重复使用";
                } else {
                    $sku = strtolower($sku);
                    $owner = $this->me->getUser();
                    $result = $productModel->addProduct($title, $short_title, $sub_title, $sku, $status, $description, $photo, $location, $base_price, $selling_price, $owner, $scale, $brand);
                }
            } catch (Angel_Exception_Product $e) {
                $error = $e->getDetail();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-product-list'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "创建商品";
            $this->view->currency = $this->bootstrap_options['currency'];
            $this->view->separator = $this->SEPARATOR;
            $this->view->location = $this->bootstrap_options['stock_location'];
        }
    }

    public function productSaveAction() {
        if ($this->request->isPost()) {
            
        } else {
            
            $notFoundMsg = '未找到目标商品';
            
            // GET METHOD
            $this->view->title = "编辑商品";
            $this->view->currency = $this->bootstrap_options['currency'];
            $this->view->separator = $this->SEPARATOR;
            $this->view->location = $this->bootstrap_options['stock_location'];

            $id = $this->request->getParam('id');
            if ($id) {
                $productModel = $this->getModel('product');
                $photoModel = $this->getModel('photo');
                $target = $productModel->getProductById($id);
                if(!$target) {
                    $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
                }
                if ($this->request->getParam('copy')) {
                    // 复制一个商品
                    $this->view->title = "复制并创建商品";
                    $this->view->model = $target;
                }
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
            }
        }
    }

    public function productRemoveAction() {
        
    }

    public function resultAction() {
        $this->view->error = $this->request->getParam('error');
        $this->view->redirectUrl = $this->request->getParam('redirectUrl');
    }

    public function photoCreateAction() {

        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $tmp = $this->getParam('tmp');
            $owner = $this->me->getUser();
            $photoModel = $this->getModel('photo');
            try {
                $destination = $this->getTmpFile($tmp);
                $result = $photoModel->addPhoto($destination, $owner);
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
                $result = 1;
            } catch (Exception $e) {
                $result = 0;
            }
            echo $result;
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
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);
        $resource = array();
        foreach ($paginator as $r) {
            $resource[] = array('path' => array('orig' => $this->view->photoImage($r->name . $r->type), 'main' => $this->view->photoImage($r->name . $r->type, 'main'), 'xlarge' => $this->view->photoImage($r->name . $r->type, 'xlarge'), 'small' => $this->view->photoImage($r->name . $r->type, 'small'), 'large' => $this->view->photoImage($r->name . $r->type, 'large')),
                'name' => $r->name,
                'id' => $r->id,
                'type' => $r->type,
                'owner' => $r->owner);
        }
        // JSON FORMAT
        if ($this->getParam('format') == 'json') {
            $this->_helper->json(array('data' => $resource,
                'code' => 200,
                'page' => $paginator->getCurrentPageNumber(),
                'count' => $paginator->count()));
        } else {
            $this->view->paginator = $paginator;
            $this->view->resource = $resource;
            $this->view->title = "图片列表";
        }
    }

    public function photoRemoveAction() {
        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->getParam('id');
            if ($id) {
                $owner = $this->me->getUser();
                if ($owner) {
                    $photoModel = $this->getModel('photo');
                    $result = $photoModel->removePhoto($id, $owner);
                }
            }
            echo $result;
            exit;
        }
    }

}
