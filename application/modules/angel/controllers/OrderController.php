<?php

class Angel_OrderController extends Angel_Controller_Action {

    protected $login_not_required = array('create', 'cart');

    public function init() {
        parent::init();
    }

    public function createAction() {

        if ($this->request->isPost()) {

            $orderModel = $this->getModel('order');
            // parse cart
            $query = $this->request->getParam('query');
            $update_it = $this->request->getParam('update_it');
            $selected_address_type = $this->request->getParam('selected_address_type');
            $address = false;
            if ($selected_address_type != 2) {
                $address = new \Documents\AddressDoc();
                $address->contact = $this->request->getParam('contact');
                $address->phone = $this->request->getParam('phone');
                $address->street = $this->request->getParam('street');
                $address->city = $this->request->getParam('city');
                $address->state = $this->request->getParam('state');
                $address->zip = $this->request->getParam('zip');
                $address->country = $this->request->getParam('country');
            }

            $resource = $this->parseCart($query);
            // me
            $user = false;
            if ($this->me) {
                $user = $this->me->getUser();
            }

            $currency = $_COOKIE['currency'];
            if (!$currency) {
                $currency = key($this->bootstrap_options['currency']);
            }
            try {
                $order = $orderModel->create($user, $currency, $selected_address_type, $address);
                $result = $this->updateOrderDetail($order, $currency, $resource);
                if ($result && $update_it && $this->me && $address) {
                    // 更新用户地址
                    $me = $this->me->getUser();
                    $userModel = $this->getModel('user');
                    $userModel->updateAddress($me, $address->contact, $address->street, $address->phone, $address->state, $address->city, $address->country, $address->zip);
                }
                $this->_helper->json(array('code' => 200));
            } catch (Angel_Exception_Order $e) {
                $this->_helper->json(array('code' => 500, 'error' => $e->getDetail()));
            }
        }
    }

    protected function updateOrderDetail($order, $currency, $order_details) {
        $productModel = $this->getModel('product');
        $orderModel = $this->getModel('order');
        $total = 0;
        foreach ($order_details as $detail) {
            if ($detail["unit"]) {
                $orderDetail = new \Documents\OrderDetailDoc();
                $p = $productModel->getById($detail["id"]);
                if ($p) {
                    $orderDetail->product_id = $p->id;
                    $orderDetail->product_title = $p->title;
                    $orderDetail->product_sku = $p->sku;
                    if (count($p->photo)) {
                        $photoDoc = new \Documents\PhotoDoc();
                        $photoDoc->photo_id = $p->photo[0]->id;
                        $photoDoc->photo_url_small = $this->view->photoImage($p->photo[0]->name . $p->photo[0]->type, 'small');
                        $orderDetail->product_photo_doc = $photoDoc;
                    }
                }
                $orderDetail->unit = $detail["unit"];
                $orderDetail->price = $detail['selling_price'][$currency]['amount'];
                $total += $orderDetail->price * $orderDetail->unit;

                $order->order_detail[] = $orderDetail;
            }
        }
        $order->total = $total;
        $result = $orderModel->update($order);
        return $result;
    }

    public function updateAction() {
        
    }

    public function cartAction() {
        if ($this->request->isPost()) {
            $query = $this->request->getParam('query');
            $resource = array();
            $code = 404;
            if ($query) {
                $resource = $this->parseCart($query);
                if (count($resource)) {
                    $code = 200;
                }
            }
            $this->_helper->json(array('data' => $resource,
                'code' => $code));
        } else {
            $this->view->title = "Shopping Cart";
        }
    }

    protected function parseCart($query) {
        $resource = array();
        if ($query) {
            $productModel = $this->getModel('product');
            $qs = explode("|", $query);
            foreach ($qs as $qitem) {
                list($id, $unit) = split(":", $qitem);
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
                if ($unit) {
                    $pArr['unit'] = $unit;
                }
                $resource[] = $pArr;
            }
        }
        return $resource;
    }

}
