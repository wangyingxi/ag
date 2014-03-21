<?php
namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Brand extends AbstractDocument{
        
    /** @ODM\String */
    protected $name;

    /** @ODM\String */
    protected $description = 'nothing';
    
}
