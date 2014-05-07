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
        if ($id) {
            $productModel = $this->getModel('product');
            $product = $productModel->getById($id);
            $this->view->model = $product;
            $this->view->currency = $this->bootstrap_options['currency'];
            $this->view->currency_symbol = $this->bootstrap_options['currency_symbol'];

            // 将DBRef数组转换为json数组字符串输出到页面
//            $photoJson = false;
//            if ($product->photo) {
//                $ps = array();
//                foreach ($product->photo as $p) {
//                    $ps[] = $p->toArray(false);
//                }
//                $photoJson = json_encode($ps);
//            }
//            $this->view->photoJson = $photoJson;
        }
    }

    public function cartAction() {
        if ($this->request->isPost()) {
            $query = $this->request->getParam('query');
            if ($query) {
                $productModel = $this->getModel('product');
                $qs = explode("|", $query);
                $resource = array();
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

                $this->_helper->json(array('data' => $resource,
                    'code' => 200));
            }
        } else {
            $this->view->title = "My Cart";
        }
    }

}
