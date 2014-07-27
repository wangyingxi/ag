<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class OrderDetailDoc extends AbstractDocument {

    /** @ODM\ReferenceOne(targetDocument="\Documents\Product") */
    protected $product;

    /** @ODM\Int */
    protected $unit;

    /** @ODM\Float */
    protected $price;

}
