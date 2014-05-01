<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Product extends AbstractDocument {

    /** @ODM\String */
    protected $title;                               // 标题

    /** @ODM\String */
    protected $short_title;                         // 短标题

    /** @ODM\String */
    protected $sub_title;                           // 附标题

    /** @ODM\String */
    protected $sku;                                 // SKU

    /** @ODM\String */
    protected $product_type = 'product';

    /** @ODM\String */
    protected $status = 'online';                   // 商品状态: 'online', 'soldout'

    /** @ODM\String */
    protected $description;

    /** @ODM\ReferenceMany(targetDocument="\Documents\Photo") */
    protected $photo = array();

    /** @ODM\Collection */
    protected $location = array('cn');

    /** @ODM\Float */
    protected $base_price = 0;                      // 商品进价

    /** @ODM\Hash */
    protected $selling_price = array();             // 商品售价（各种货币）

    /** @ODM\ReferenceOne(targetDocument="\Documents\User") */
    protected $owner;
    
    /** @ODM\Hash */
    protected $scale;
    
    /** @ODM\ReferenceOne(targetDocument="\Documents\Brand") */
    protected $brand;
    
    /** @ODM\ReferenceOne(targetDocument="\Documents\Category") */
    protected $category;
    
    /** @ODM\Int */
    protected $view = 0;

    /** @ODM\Int */
    protected $sold = 0;

    /** @ODM\Hash */
    protected $css;
        /**
     * 添加图片
     */
    public function addPhoto(\Documents\Photo $p) {
        $this->photo[] = $p;
    }
    /**
     * 清空图片
     */
    public function clearPhoto() {
        $this->photo = array();
    }

    public function addSellingPrice($currency, $amount) {
        $priceDoc = new \Documents\PriceDoc();
        $priceDoc->currency = $currency;
        $priceDoc->amount = $amount;
        
        $this->selling_price[] = $priceDoc;
    }

}
