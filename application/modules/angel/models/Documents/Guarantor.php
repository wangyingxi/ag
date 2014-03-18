<?php
namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Guarantor extends AbstractDocument{
    
    /** @ODM\ReferenceOne(targetDocument="\Documents\Company") */
    protected $company;

    /** @ODM\String */
    protected $guarantor_id;
    
    /** @ODM\Boolean */
    protected $active_bln;

    public function getRequiredFields(){
        return array();
    }
}

?>
