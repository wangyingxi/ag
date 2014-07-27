<?php

class Angel_OrderController extends Angel_Controller_Action {

    protected $login_not_required = array('create');

    public function init() {
        parent::init();
    }

    public function createAction() {



        if ($this->request->isPost()) {

            $orderModel = $this->getModel('order');
            $user = false;
            if ($this->me) {
                $user = $this->me->getUser();
            }
            
            $currency = $_COOKIE['currency'];
            if (!$currency) {
                $currency = key($this->bootstrap_options['currency']);
            }

            $orderModel->create($user, $currency);
        }
    }

    public function updateAction() {
        
    }

}
