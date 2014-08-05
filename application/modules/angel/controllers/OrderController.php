<?php

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;

class Angel_OrderController extends Angel_Controller_Action {

    protected $login_not_required = array('create', 'cart', 'paypal-pay');

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
                $this->_helper->json(array('code' => 200, 'oid' => $order->oid));
            } catch (Angel_Exception_Order $e) {
                $this->_helper->json(array('code' => 500, 'error' => $e->getDetail()));
            }
        }
    }

    public function paypalPayAction() {
        $sdkConfig = array(
            "mode" => "sandbox"
        );

        $cred = new OAuthTokenCredential("Ac3nVRAR8pjxA3WEjYdOBVfZ4-k_v0SU0gG6OOi9XYroPScRIEpHiyFigxki", "EE0DXRCBjx9V2thW1KgWH9iGVNJVP7ftBM_6XW0f_xisXPN5OanB-MCfjy-_", $sdkConfig);
//        $token = $cred->getAccessToken($sdkConfig);
//
//        $cred = "Bearer " . $token;
        $apiContext = new ApiContext($cred, 'Request' . time());
        $apiContext->setConfig($sdkConfig);
        $payer = new Payer();
        $payer->setPayment_method("paypal");

        $amount = new Amount();
        $amount->setCurrency("USD");
        $amount->setTotal("1.2");

        $transaction = new Transaction();
        $transaction->setDescription("i just creating a payment");
        $transaction->setAmount($amount);

//        $baseUrl = getBaseUrl();
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturn_url("https://devtools-paypal.com/guide/pay_paypal/php?success=true");
        $redirectUrls->setCancel_url("https://devtools-paypal.com/guide/pay_paypal/php?cancel=true");

        $payment = new Payment();
        $payment->setIntent("sale");
        $payment->setPayer($payer);
        $payment->setRedirect_urls($redirectUrls);
        $payment->setTransactions(array($transaction));

        $payment->create($apiContext);
    }

    public function removeAction() {
        if ($this->request->isPost() && $this->me) {
            $orderModel = $this->getModel('order');
            $id = $this->request->getParam('id');
            $user = $this->me->getUser();

            $order = $orderModel->getById($id);
            $code = 500;
            if ($order && $order->status == 1 && ($user->user_type == 'admin' || $order->owner->id == $user->id)) {
                // 可以删除
                try {
                    $orderModel->remove($id);
                    $code = 200;
                } catch (Exception $ex) {
                    $error = $ex->getMessage();
                }
            } else {
                $error = 'sorry you can not remove this order';
            }
            $this->_helper->json(array('code' => $code, 'error' => $error));
        }
    }

    public function receiveOrderAction() {
        if ($this->request->isPost() && $this->me) {
            $this->alterOrderStatusAction(4);
        }
    }

    public function dispatchOrderAction() {
        if ($this->request->isPost() && $this->me) {
            $this->alterOrderStatusAction(3);
        }
    }

    protected function alterOrderStatusAction($status) {
        $orderModel = $this->getModel('order');
        $id = $this->request->getParam('id');
        $user = $this->me->getUser();

        $order = $orderModel->getById($id);
        $code = 500;
        if ($order && $order->status == $status - 1 && ($user->user_type == 'admin' || $order->owner->id == $user->id)) {
            // 可以修改
            try {
                $orderModel->save($id, array('status' => $status));
                $code = 200;
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }
        } else {
            $error = 'sorry you can not alter this order\'s status';
        }
        $this->_helper->json(array('code' => $code, 'error' => $error));
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
