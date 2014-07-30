<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class OrderDetailDoc extends AbstractDocument {

    /** @ODM\String */
    protected $product_id;

    /** @ODM\String */
    protected $protect_title;

    /** @ODM\String */
    protected $product_photo_id;

    /** @ODM\String */
    protected $product_sku;

    /** @ODM\Int */
    protected $unit;

    /** @ODM\Float */
    protected $price;

}
