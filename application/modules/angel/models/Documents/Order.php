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
    protected $currency;
    
    /** @ODM\Float */
    protected $total;
    
    /** @ODM\EmbedMany(targetDocument="\Documents\OrderDetailDoc") */
    protected $order_detail = array();
    
    /** @ODM\Int */
    protected $status = 1;  // 订单状态（1，pending（等待支付）；2，paid & awaiting dispatching（等待发货）；3，dispatched（已发货）；4，received（已收货）；5，comment（已发表评论））
    
}