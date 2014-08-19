<?php
namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Order extends AbstractDocument{
    
    /** @ODM\String */
    protected $oid;    // 订单流水号
    
    /** @ODM\ReferenceOne(targetDocument="\Documents\User") */
    protected $owner;

    /** @ODM\Int */
    protected $selected_address_type;   // 地址种类（1，使用用户填写地址；2，使用paypal地址）
    
    /** @ODM\EmbedOne(targetDocument="\Documents\AddressDoc") */
    protected $address;
    
    /** @ODM\String */
    protected $email;
    
    /** @ODM\String */
    protected $currency;
    
    /** @ODM\Float */
    protected $total;
    
    /** @ODM\EmbedMany(targetDocument="\Documents\OrderDetailDoc") */
    protected $order_detail = array();
    
    /** @ODM\Int */
    protected $status = 1;  // 订单状态（1，awaiting payment（等待支付）；2，pending（echeck）；3，dispatching（发货中）；4，dispatched（已发货）；5，received（已收货）；6，comment（已发表评论））
    
    /** @ODM\Date */
    protected $paid_at;
    
    /** @ODM\Date */
    protected $shipped_at;
    
    /** @ODM\Date */
    protected $received_at;
    
}