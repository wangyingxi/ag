<?php

class Angel_OrderController extends Angel_Controller_Action {

    protected $login_not_required = array('create','cart');

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

    public function cartAction() {
        if ($this->request->isPost()) {
            $query = $this->request->getParam('query');
            $resource = array();
            $code = 404;
            if ($query) {
                $productModel = $this->getModel('product');
                $qs = explode("|", $query);
                foreach ($qs as $id) {
                    $product = $productModel->getById($id);
                    $pArr = array('id' => $id,
                        'title' => $product->title,
                        'location' => $product->location);
                    $new_selling_price = array();
                    foreach ($product->selling_price as $key => $val) {
                        $new_selling_price[$key] = array('symbol' => $this->bootstrap_options['currency_symbol'][$key], 'amount' => $val);
                    }
                    $pArr['selling_price'] = $new_selling_price;
                    if (count($product->photo) > 0) {
                        $p0 = $product->photo[0];
                        $pArr['photo'] = $this->view->photoImage($p0->name . $p0->type, 'small');
                    }
                    $pArr['link'] = $this->view->url(array('id' => $id), 'product-view');
                    $resource[] = $pArr;
                }
                $code = 200;
            }
            $this->_helper->json(array('data' => $resource,
                'code' => $code));
        } else {
            $this->view->title = "Shopping Cart";
        }
    }

}
