<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class AddressDoc extends AbstractDocument {

    /** @ODM\String */
    protected $contact;

    /** @ODM\String */
    protected $street;

    /** @ODM\String */
    protected $phone;

    /** @ODM\String */
    protected $state;

    /** @ODM\String */
    protected $city;

    /** @ODM\String */
    protected $country;

    /** @ODM\String */
    protected $zip;

}
