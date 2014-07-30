<?php
namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Order extends AbstractDocument{
    
    /** @ODM\String */
    protected $oid;    // 订单流水号
    
    /** @ODM\ReferenceOne(targetDocument="\Documents\User") */
    protected $owner;

    /** @ODM\EmbedOne(targetDocument="\Documents\AddressDoc") */
    protected $address;
    
    /** @ODM\String */
    protected $currency;
    
    /** @ODM\Float */
    protected $total;
    
    /** @ODM\EmbedMany(targetDocument="\Documents\OrderDetailDoc") */
    protected $order_detail = array();
}