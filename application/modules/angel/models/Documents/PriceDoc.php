<?php
namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class PriceDoc extends AbstractDocument{
    
    /** @ODM\String */
    protected $currency = 'us';
    
    /** @ODM\Float */
    protected $amount = 0;
}
