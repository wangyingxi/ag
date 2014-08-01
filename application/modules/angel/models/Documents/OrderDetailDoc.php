<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class OrderDetailDoc extends AbstractDocument {

    /** @ODM\String */
    protected $product_id;

    /** @ODM\String */
    protected $product_title;

    /** @ODM\EmbedOne(targetDocument="\Documents\PhotoDoc") */
    protected $product_photo_doc;

    /** @ODM\String */
    protected $product_sku;

    /** @ODM\Int */
    protected $unit;

    /** @ODM\Float */
    protected $price;

}
