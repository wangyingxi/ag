<?php
namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Photo extends AbstractDocument{
        
    /** @ODM\String */
    protected $name;
    
    /** @ODM\String */
    protected $type = '.jpg';
    
    /** @ODM\String */
    protected $description = 'nothing';
    
    /** @ODM\ReferenceOne(targetDocument="\Documents\User") */
    protected $owner;

}
