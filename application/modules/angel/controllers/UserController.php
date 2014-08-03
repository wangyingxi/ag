<?php

class Angel_UserController extends Angel_Controller_Action {

    public function init() {
        parent::init();
    }

    public function addressAction() {
        if ($this->request->isXmlHttpRequest() && $this->request->isPost()) {
            $contact = $this->_request->getParam('contact');
            $street = $this->_request->getParam('street');
            $state = $this->_request->getParam('state');
            $city = $this->_request->getParam('city');
            $phone = $this->_request->getParam('phone');
            $zip = $this->_request->getParam('zip');
            $country = $this->_request->getParam('country');

            $result = $this->getModel('user')->updateAddress($this->me->getUser(), $contact, $street, $phone, $state, $city, $country, $zip);

            if ($result) {
                $this->_helper->json(array('code' => 200));
            } else {
                $this->_helper->json(array('code' => 500));
            }
        } else {
            $this->view->title = 'Update Address';
        }
    }

    public function resetPasswordAction() {
        if ($this->request->isXmlHttpRequest() && $this->request->isPost()) {
            $old_pwd = $this->_request->getParam('old');
            $new_pwd = $this->_request->getParam('new');

            try {
                $result = $this->getModel('user')->resetPassword($this->me->getId(), $old_pwd, $new_pwd);
            } catch (\Angel_Exception_User $e) {
                $result = $e->getDetail();
            }

            $this->_helper->json($result);
        }
    }

    public function personalThumbnailAction() {
        $this->_helper->layout->disableLayout();

        $result = 0;
        if ($this->request->isPost()) {
            $upload = new Zend_File_Transfer();

            $upload->addValidator('Size', false, 5120000); //5M

            $utilService = $this->_container->get('util');
            $destination = $utilService->getTmpDirectory() . '/' . uniqid();

            $upload->addFilter('Rename', $destination);

            if ($upload->isValid()) {
                if ($upload->receive()) {
                    $userModel = $this->getModel('user');
                    try {
                        $result = $userModel->addProfileImage($this->me->getUser(), $destination);
                        if ($result) {
                            $result = 1;
                            $this->view->path = $this->view->url(array('image' => $this->me->getProfileImage()), 'profile-image');
                        }
                    } catch (Exception $e) {
                        // image is not accepted
                        $result = 2;
                    }
                }
            }
        }

        $this->view->result = $result;
    }

    public function cropThumbnailAction() {
        $userModel = $this->getModel('user');

        $orig = $userModel->getProfileImagePath($this->me->getProfileImage());

        $x = $this->request->getParam('x', 0);
        $y = $this->request->getParam('y', 0);
        $w = $this->request->getParam('w', 180);
        $h = $this->request->getParam('h', 180);

        $coord = array($x, $y, $w, $h);
        $userModel->generateProfileThumbnail($orig, $coord);

        $imageurl_large = $this->view->url(array('image' => $userModel->getProfileImage($this->me->getProfileImage(), 180)), 'profile-image');
        $imageurl_small = $this->view->url(array('image' => $userModel->getProfileImage($this->me->getProfileImage(), 50)), 'profile-image');

        $this->_helper->json(array("large" => $imageurl_large, "small" => $imageurl_small));
    }

    /**
     * 上传用户文件的action 
     */
    public function uploadUserDocAction() {
        $this->_helper->layout->disableLayout();
        $doctype = $this->request->getParam('doctype');
        $this->view->doctype = $doctype;

        $result = 0;
        if ($this->request->isPost()) {
            $upload = new Zend_File_Transfer();

            $upload->addValidator('Size', false, 10240000); //10M

            $utilService = $this->_container->get('util');
            $fileService = $this->_container->get('file');

            $filename = $utilService->getFilename($upload->getFileName());
            $extension = $fileService->getExtensionByFilename($filename);

            $destination = $utilService->getTmpDirectory() . DIRECTORY_SEPARATOR . uniqid();

            $upload->addFilter('Rename', $destination);

            if ($upload->isValid()) {
                if ($upload->receive()) {
                    $userModel = $this->getModel('user');

                    $mimetype = $upload->getMimeType();
                    if ($fileService->isAcceptedDocument($mimetype, $extension)) {
                        $user = $userModel->getUserById($this->me->getId());
                        if ($user) {
                            $doc = null;

                            if ($doctype == \Angel_Model_User::FILETYPE_IDENTITY_FRONT || $doctype == \Angel_Model_User::FILETYPE_IDENTITY_BACK) {
                                $doc = $userModel->addUserDoc($user, $doctype, $destination, $filename, $mimetype);
                            }

                            if ($doc) {
                                $result = 1;
                                $this->view->filename = $doc->filename;
                                $this->view->path = $this->view->url(array('doctype' => $doctype, 'user_id' => $user->id, 'doc_id' => $doc->id), 'user-doc');
                            }
                        }
                    } else {
                        // 上传的文件格式不接受
                        $result = 2;
                    }
                }
            }
        }

        $this->view->result = $result;
    }

    /**
     * 当用户查看用户的文档 
     */
    public function userDocAction() {

        $doctype = $this->request->getParam('doctype');
        $user_id = $this->request->getParam('user_id');
        $doc_id = $this->request->getParam('doc_id');

        $doc = $this->getModel('user')->getUserDoc($user_id, $doctype, $doc_id);
        if ($doc && ($fd = fopen($doc->path, 'r'))) {
            $filepath = $doc->path;
            $filename = $doc->filename;

            $this->outputFile($fd, $filepath, $filename);

            fclose($fd);
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        } else {
            $this->_redirect($this->view->url(array(), 'not-found'));
        }
    }

    public function profileAction() {
        
    }
    
    public function orderAction() {
        if ($this->request->isXmlHttpRequest() && $this->request->isPost()) {
            
        } else {
            $orderModel = $this->getModel('order');
            $orders = $orderModel->getByUser($this->me->getUser()->id, false);
            $this->view->orders = $orders;
            $this->view->currency_symbol_options = $this->bootstrap_options['currency_symbol'];
            $this->view->order_status_options = $this->bootstrap_options['order_status'];
        }
    }

}
