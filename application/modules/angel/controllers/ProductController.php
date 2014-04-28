<?php

class Angel_ProductController extends Angel_Controller_Action {

    protected $login_not_required = array(
        'view'
    );

    public function init() {
        parent::init();

        $this->_helper->layout->setLayout('main');
    }

    public function indexAction() {
        
    }

    public function viewAction() {
        $id = $this->request->getParam('id');
        if($id) {
            $productModel = $this->getModel('product');
            $product = $productModel->getById($id);
            $this->view->model = $product;
        }
    }

}
