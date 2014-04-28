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
        
    }

}
