<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Brand extends AbstractDocument {

    /** @ODM\String */
    protected $name;

    /** @ODM\String */
    protected $description = 'nothing';

    /** @ODM\ReferenceOne(targetDocument="\Documents\Photo") */
    protected $logo = array();

    /** @ODM\Int */
    protected $view = 0;

    /** @ODM\ReferenceOne(targetDocument="\Documents\User") */
    protected $owner;

}
