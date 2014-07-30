<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class OrderDetailDoc extends AbstractDocument {

    /** @ODM\String */
    protected $photo_id;

    /** @ODM\String */
    protected $photo_url_small;

    /** @ODM\String */
    protected $photo_url_orig;


}
