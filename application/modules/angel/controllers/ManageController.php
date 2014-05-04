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
        $paginator->setItemCountPerPage($this->bootstrap_options['default_page_size']);
        $paginator->setCurrentPageNumber($page);
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
        $brandModel = $this->getModel('brand');
        $categoryModel = $this->getModel('category');
        if ($this->request->isPost()) {
            // POST METHOD
            $title = $this->request->getParam('title');
            $short_title = $this->request->getParam('short_title');
            $sub_title = $this->request->getParam('sub_title');
            $sku = $this->request->getParam('sku');
            $status = $this->request->getParam('status');
            $description = $this->request->getParam('description');
            $photo = $this->decodePhoto();
            $location = $this->getLocation();
            $base_price = floatval($this->request->getParam('base_price'));
            $selling_price = $this->getSellingPrice();
            $scale = $this->getScale();
            $brandId = $this->request->getParam('brand');
            $categoryId = $this->request->getParam('category');
            $css = $this->getCss();
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
                    $brand = null;
                    if ($brandId) {
                        $brand = $brandModel->getById($brandId);
                        if (!$brand) {
                            $this->_redirect($this->view->url(array(), 'manage-result') . '?error="notfound brand"');
                        }
                    }
                    $category = null;
                    if ($categoryId) {
                        $category = $categoryModel->getById($categoryId);
                        if (!$category) {
                            $this->_redirect($this->view->url(array(), 'manage-result') . '?error="notfound category"');
                        }
                    }
                    $result = $productModel->addProduct($title, $short_title, $sub_title, $sku, $status, $description, $photo, $location, $base_price, $selling_price, $owner, $scale, $brand, $category, $css);
                }
            } catch (Angel_Exception_Product $e) {
                $error = $e->getDetail();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-product-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "创建商品";
            $this->view->separator = $this->SEPARATOR;
            $this->view->location = $this->bootstrap_options['stock_location'];
            $this->view->brand = $brandModel->getAll();
            $this->view->category = $categoryModel->getAll();
        }
    }

    public function productSaveAction() {
        $id = $this->request->getParam('id');
        $copy = $this->request->getParam('copy');
        $brandModel = $this->getModel('brand');
        $categoryModel = $this->getModel('category');

        if ($this->request->isPost()) {
            // POST METHOD
            $title = $this->request->getParam('title');
            $short_title = $this->request->getParam('short_title');
            $sub_title = $this->request->getParam('sub_title');
            $sku = $this->request->getParam('sku');
            $status = $this->request->getParam('status');
            $description = $this->request->getParam('description');
            $photo = $this->decodePhoto();
            $location = $this->getLocation();
            $base_price = floatval($this->request->getParam('base_price'));
            $selling_price = $this->getSellingPrice();
            $scale = $this->getScale();
            $brandId = $this->request->getParam('brand');
            $categoryId = $this->request->getParam('category');
            $css = $this->getCss();
            $result = false;
            $error = "";

            try {
                $productModel = $this->getModel('product');
                $isSkuExist = false;
                $owner = $this->me->getUser();
                $brand = null;
                if ($brandId) {
                    $brand = $brandModel->getById($brandId);
                    if (!$brand) {
                        $this->_redirect($this->view->url(array(), 'manage-result') . '?error="notfound"');
                    }
                }
                $category = null;
                if ($categoryId) {
                    $category = $categoryModel->getById($categoryId);
                    if (!$category) {
                        $this->_redirect($this->view->url(array(), 'manage-result') . '?error="notfound"');
                    }
                }
                if ($copy) {
                    // COPY NEW
                    // Checking Sku Available
                    $sku = strtolower($sku);
                    if ($sku) {
                        $isSkuExist = $productModel->isSkuExist($sku);
                    }
                    if ($isSkuExist) {
                        $error = "该SKU已经存在，不能重复使用";
                    } else {
                        $result = $productModel->addProduct($title, $short_title, $sub_title, $sku, $status, $description, $photo, $location, $base_price, $selling_price, $owner, $scale, $brand, $category, $css);
                    }
                } else {
                    // EDIT
                    // Checking Sku Available
                    $sku = strtolower($sku);
                    $os = $this->request->getParam('origin-sku');
                    if (!($os && $os == $sku)) {
                        $isSkuExist = $productModel->isSkuExist($sku);
                    }
                    if ($isSkuExist) {
                        $error = "该SKU已经存在，不能重复使用";
                    } else {
                        $result = $productModel->saveProduct($id, $title, $short_title, $sub_title, $sku, $status, $description, $photo, $location, $base_price, $selling_price, $owner, $scale, $brand, $category, $css);
                    }
                }
            } catch (Angel_Exception_Product $e) {
                $error = $e->getDetail();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-product-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $notFoundMsg = '未找到目标商品';

            $this->view->title = "编辑商品";
            $this->view->separator = $this->SEPARATOR;
            $this->view->location = $this->bootstrap_options['stock_location'];

            if ($id) {
                $productModel = $this->getModel('product');
                $photoModel = $this->getModel('photo');
                $target = $productModel->getById($id);
                if (!$target) {
                    $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
                }
                $this->view->brand = $brandModel->getAll();
                $this->view->category = $categoryModel->getAll();
                if ($copy) {
                    // 复制一个商品
                    $this->view->title = "复制并创建商品";
                    $this->view->copy = $copy;
                }
                $this->view->model = $target;

                $photo = $target->photo;
                if ($photo) {
                    $saveObj = array();
                    foreach ($photo as $p) {
                        try {
                            $name = $p->name;
                        } catch (Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
                            $this->view->imageBroken = true;
                            continue;
                        }
                        $saveObj[$name] = $this->view->photoImage($p->name . $p->type, 'small');
                        if (!$p->thumbnail) {
                            $saveObj[$name] = $this->view->photoImage($p->name . $p->type);
                        }
                    }
                    if (!count($saveObj))
                        $saveObj = false;
                    $this->view->photo = $saveObj;
                }
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
            }
        }
    }

    public function productRemoveAction() {
        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->getParam('id');
            if ($id) {
                $productModel = $this->getModel('product');
                $result = $productModel->removeProduct($id);
            }
            echo $result;
            exit;
        }
    }

    protected function decodePhoto($paramName = 'photo') {
        $paramPhoto = $this->request->getParam($paramName);
        if ($paramPhoto) {
            $paramPhoto = json_decode($paramPhoto);
            $photoModel = $this->getModel('photo');
            $photoArray = array();
            foreach ($paramPhoto as $name => $path) {
                $photoObj = $photoModel->getPhotoByName($name);
                if ($photoObj) {
                    $photoArray[] = $photoObj;
                }
            }
            return $photoArray;
        } else {
            return false;
        }
    }

    protected function getLocation() {
        $paramLocation = $this->request->getParam('location');
        if ($paramLocation) {
            $tmp = split($this->SEPARATOR, $paramLocation);
            if (count($tmp)) {
                $paramLocation = $tmp;
                return $paramLocation;
            }
        }
        return false;
    }

    protected function getSellingPrice() {
        $result = array();
        foreach ($this->bootstrap_options['currency'] as $key => $val) {
            $price = $this->request->getParam('price_' . $key);
            if ($price) {
                $result[$key] = floatval($price);
            }
        }
        return $result;
    }

    protected function getScale() {
        $result = array();
        $result['weight'] = $this->request->getParam('scale_weight');
        $result['height'] = $this->request->getParam('scale_height');
        $result['width'] = $this->request->getParam('scale_width');
        $result['length'] = $this->request->getParam('scale_length');
        return $result;
    }

    protected function getCss() {
        $result = array();
        $result['backgroundcolor'] = $this->request->getParam('css_backgroundcolor');
        $result['fontcolor'] = $this->request->getParam('css_fontcolor');
        $result['linkcolor'] = $this->request->getParam('css_linkcolor');
        $result['pricecolor'] = $this->request->getParam('css_pricecolor');
        $result['buttoncolor'] = $this->request->getParam('css_buttoncolor');
        
        return $result;
    }
    
    public function resultAction() {
        $this->view->error = $this->request->getParam('error');
        $this->view->redirectUrl = $this->request->getParam('redirectUrl');
    }

    public function photoCreateAction() {
        $phototypeModel = $this->getModel('phototype');

        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $tmp = $this->getParam('tmp');
            $title = $this->getParam('title');
            $description = $this->getParam('description');
            $phototypeId = $this->getParam('phototype');
            $thumbnail = $this->getParam('thumbnail') == "1" ? true : false;

            $phototype = null;
            if ($phototypeId) {
                $phototype = $phototypeModel->getById($phototypeId);
                if (!$phototype) {
                    $this->_redirect($this->view->url(array(), 'manage-result') . '?error="notfound"');
                }
            }
            $owner = $this->me->getUser();
            $photoModel = $this->getModel('photo');
            try {
                $destination = $this->getTmpFile($tmp);
                $result = $photoModel->addPhoto($destination, $title, $description, $phototype, $thumbnail, $owner);
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
            $this->view->phototype = $phototypeModel->getAll();
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
        $phototype = $this->request->getParam('phototype');
        if (!$page) {
            $page = 1;
        }
        $photoModel = $this->getModel('photo');

        $paginator = null;
        if (!$phototype) {
            $paginator = $photoModel->getAll();
        } else {
            $paginator = $photoModel->getPhotoByPhototype($phototype);
        }
        $paginator->setItemCountPerPage($this->bootstrap_options['default_page_size']);
        $paginator->setCurrentPageNumber($page);
        $resource = array();
        foreach ($paginator as $r) {
            $resource[] = array('path' => array('orig' => $this->view->photoImage($r->name . $r->type), 'main' => $this->view->photoImage($r->name . $r->type, 'main'), 'small' => $this->view->photoImage($r->name . $r->type, 'small'), 'large' => $this->view->photoImage($r->name . $r->type, 'large')),
                'name' => $r->name,
                'id' => $r->id,
                'type' => $r->type,
                'thumbnail' => $r->thumbnail,
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

    public function photoSaveAction() {
        $notFoundMsg = '未找到目标图片';
        $photoModel = $this->getModel('photo');
        $phototypeModel = $this->getModel('phototype');
        $id = $this->request->getParam('id');

        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $title = $this->request->getParam('title');
            $description = $this->request->getParam('description');
            $phototypeId = $this->request->getParam('phototype');
            $phototype = null;
            if ($phototypeId) {
                $phototype = $phototypeModel->getById($phototypeId);
                if (!$phototype) {
                    $this->_redirect($this->view->url(array(), 'manage-result') . '?error="notfound"');
                }
            }
            try {
                $result = $photoModel->savePhoto($id, $title, $description, $phototype);
            } catch (Angel_Exception_Photo $e) {
                $error = $e->getDetail();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-photo-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "编辑图片";

            if ($id) {
                $target = $photoModel->getById($id);
                $phototype = $phototypeModel->getAll();
                if (!$target) {
                    $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
                }
                $this->view->model = $target;
                $this->view->phototype = $phototype;
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
            }
        }
    }

    public function photoRemoveAction() {
        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->getParam('id');
            if ($id) {
                $photoModel = $this->getModel('photo');
                $result = $photoModel->removePhoto($id);
            }
            echo $result;
            exit;
        }
    }

    public function phototypeListAction() {
        $page = $this->request->getParam('page');
        if (!$page) {
            $page = 1;
        }
        $phototypeModel = $this->getModel('phototype');
        $photoModel = $this->getModel('photo');
        $paginator = $phototypeModel->getAll();
        $paginator->setItemCountPerPage($this->bootstrap_options['default_page_size']);
        $paginator->setCurrentPageNumber($page);
        $resource = array();
        foreach ($paginator as $r) {
            $resource[] = array('id' => $r->id,
                'name' => $r->name,
                'description' => $r->description,
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
            $this->view->title = "图片分类列表";
            $this->view->photoModel = $photoModel;
        }
    }

    public function phototypeCreateAction() {
        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $name = $this->request->getParam('name');
            $description = $this->request->getParam('description');
            $owner = $this->me->getUser();
            $phototypeModel = $this->getModel('phototype');
            try {
                $result = $phototypeModel->addPhototype($name, $description, $owner);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-phototype-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "创建图片分类";
        }
    }

    public function phototypeSaveAction() {
        $notFoundMsg = '未找到目标图片分类';

        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->request->getParam('id');
            $name = $this->request->getParam('name');
            $description = $this->request->getParam('description');
            $phototypeModel = $this->getModel('phototype');
            try {
                $result = $phototypeModel->savePhototype($id, $name, $description);
            } catch (Angel_Exception_Phototype $e) {
                $error = $e->getDetail();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-phototype-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "编辑图片分类";

            $id = $this->request->getParam("id");
            if ($id) {
                $phototypeModel = $this->getModel('phototype');
                $target = $phototypeModel->getById($id);
                if (!$target) {
                    $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
                }
                $this->view->model = $target;
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
            }
        }
    }

    public function phototypeRemoveAction() {
        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->getParam('id');
            if ($id) {
                $phototypeModel = $this->getModel('phototype');
                $result = $phototypeModel->removePhototype($id);
            }
            echo $result;
            exit;
        }
    }

    public function brandListAction() {
        $page = $this->request->getParam('page');
        if (!$page) {
            $page = 1;
        }
        $brandModel = $this->getModel('brand');
        $productModel = $this->getModel('product');
        $paginator = $brandModel->getAll();
        $paginator->setItemCountPerPage($this->bootstrap_options['default_page_size']);
        $paginator->setCurrentPageNumber($page);
        $resource = array();
        foreach ($paginator as $r) {
            $resource[] = array('id' => $r->id,
                'name' => $r->name,
                'description' => $r->description,
                'logo' => $r->logo,
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
            $this->view->title = "品牌列表";
            $this->view->productModel = $productModel;
        }
    }

    public function brandCreateAction() {
        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $name = $this->request->getParam('name');
            $description = $this->request->getParam('description');
            $logo = $this->decodePhoto('logo');
            if (is_array($logo) && count($logo) > 0) {
                $logo = $logo[0];
            } else {
                $logo = null;
            }
            $owner = $this->me->getUser();
            $brandModel = $this->getModel('brand');
            try {
                $result = $brandModel->addBrand($name, $description, $logo, $owner);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-brand-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "创建品牌分类";
        }
    }

    public function brandRemoveAction() {
        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->getParam('id');
            if ($id) {
                $brandModel = $this->getModel('brand');
                $result = $brandModel->remove($id);
            }
            echo $result;
            exit;
        }
    }

    public function brandSaveAction() {
        $notFoundMsg = '未找到目标品牌';

        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->request->getParam('id');
            $name = $this->request->getParam('name');
            $description = $this->request->getParam('description');
            $logo = $this->decodePhoto('logo');
            if (is_array($logo) && count($logo) > 0) {
                $logo = $logo[0];
            } else {
                $logo = null;
            }
            $brandModel = $this->getModel('brand');
            try {
                $result = $brandModel->saveBrand($id, $name, $description, $logo);
            } catch (Angel_Exception_Brand $e) {
                $error = $e->getDetail();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-brand-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "编辑品牌";

            $id = $this->request->getParam("id");
            if ($id) {
                $brandModel = $this->getModel('brand');
                $target = $brandModel->getById($id);
                if (!$target) {
                    $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
                }
                $this->view->model = $target;

                $logo = $target->logo;
                $saveObj = array();
                if ($logo) {
                    try {
                        $name = $logo->name;
                        $saveObj[$name] = $this->view->photoImage($logo->name . $logo->type, 'small');
                        if (!$logo->thumbnail) {
                            $saveObj[$name] = $this->view->photoImage($logo->name . $logo->type);
                        }
                        if (!count($saveObj))
                            $saveObj = false;
                    } catch (Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
                        $this->view->imageBroken = true;
                    }
                }
                $this->view->logo = $saveObj;
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
            }
        }
    }

    public function categoryCreateAction() {

        $categoryModel = $this->getModel('category');

        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $name = $this->request->getParam('name');
            $description = $this->request->getParam('description');
            $parentId = $this->request->getParam('parent');
            try {
                $result = $categoryModel->addCategory($name, $description, $parentId, $level);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-category-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "创建分类";
            $this->view->categories = $categoryModel->getAll();
        }
    }

    public function categoryListAction() {
        $categoryModel = $this->getModel('category');
        $productModel = $this->getModel('product');
        $root = $categoryModel->getRoot();
        $this->view->title = "分类列表";
        $this->view->categoryModel = $categoryModel;
        $this->view->productModel = $productModel;
        if (count($root)) {
            $resource = array();
            foreach ($root as $r) {
                $resource[] = array('root' => $r, 'children' => $categoryModel->getByParent($r->id));
            }
            // JSON FORMAT
            if ($this->getParam('format') == 'json') {
                $this->_helper->json(array('data' => $resource,
                    'code' => 200));
            } else {
                $this->view->resource = $resource;
            }
        }
    }

    public function categoryRemoveAction() {
        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->getParam('id');
            if ($id) {
                $categoryModel = $this->getModel('category');
                $result = $categoryModel->remove($id);
            }
            echo $result;
            exit;
        }
    }

    public function categorySaveAction() {
        $notFoundMsg = '未找到目标分类';
        $categoryModel = $this->getModel('category');

        if ($this->request->isPost()) {
            $result = 0;
            // POST METHOD
            $id = $this->request->getParam('id');
            $name = $this->request->getParam('name');
            $description = $this->request->getParam('description');
            $parentId = $this->request->getParam('parent');
            try {
                $result = $categoryModel->saveCategory($id, $name, $description, $parentId);
            } catch (Angel_Exception_Category $e) {
                $error = $e->getDetail();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if ($result) {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?redirectUrl=' . $this->view->url(array(), 'manage-category-list-home'));
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $error);
            }
        } else {
            // GET METHOD
            $this->view->title = "编辑分类";
            $this->view->categories = $categoryModel->getAll();

            $id = $this->request->getParam("id");
            if ($id) {
                $categoryModel = $this->getModel('category');
                $target = $categoryModel->getById($id);
                if (!$target) {
                    $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
                }
                $this->view->model = $target;
            } else {
                $this->_redirect($this->view->url(array(), 'manage-result') . '?error=' . $notFoundMsg);
            }
        }
    }

}
