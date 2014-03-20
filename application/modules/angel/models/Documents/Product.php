<?php
namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Product extends AbstractDocument{

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
    protected $description = 'nothing';

    /** @ODM\ReferenceMany(targetDocument="\Documents\Photo") */
    protected $photo = array();

    /** @ODM\String */
    protected $location = 'cn';

    /** @ODM\Float */
    protected $base_price = 0;                      // 商品进价

    /** @ODM\EmbedMany(targetDocument="\Documents\PriceDoc") */
    protected $selling_price = array();             // 商品售价（各种货币）

    /** @ODM\ReferenceOne(targetDocument="\Documents\User") */
    protected $owner;
    
    /** @ODM\Int */
    protected $view = 0;
    
    /** @ODM\Int */
    protected $sold = 0;

    /**
     * 添加图片
     */
    public function addPhoto($name, $type, $description, $owner){
        $photo = new \Documents\Photo();
        $photo->name = $name;
        $photo->description = $description;
        $photo->type = $type;
        $photo->owner = $owner;
        
        $this->photo[] = $photo;
        
        return $photo;
    }

}