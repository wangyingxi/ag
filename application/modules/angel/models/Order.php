<?php

class Angel_Model_Order extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Order';

    /**
     * 创建订单
     * @param \Documents\User $user 订单所有者
     * @return \Documents\Order － 返回的是新创建的订单
     * @throws \Angel_Exception_Order 
     */
    public function create($user, $currency, $selected_address_type, $address) {
        $order = new $this->_document_class();

        if (is_object($user) && ($user instanceof \Documents\User)) {
            $order->owner = $user;
        }

        /* CREATE NEW ORDER ID (START) */
        $new_oid = "";
        $is_oid_validated = false;
        while (!$is_oid_validated) {
            $new_oid = "V" . rand(10000, 99999);
            $r = $this->getSingleBy(array('oid' => $new_oid));
            if ($r) {
                $is_oid_validated = false;
            } else {
                $is_oid_validated = true;
            }
        }
        $order->selected_address_type = $selected_address_type;
        $order->oid = $new_oid;
        /* CREATE NEW ORDER ID (END) */
        $order->currency = $currency;
        if($selected_address_type != 2 && $address) {
            $order->address = $address;
        }
        $this->_dm->persist($order);
        $this->_dm->flush();

        $result = $order;

        return $result;
    }

}
