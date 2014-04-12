<?php

class Angel_Model_Phototype extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Phototype';
    protected $_document_user_class = '\Documents\User';

    public function removePhototype($id) {
        $result = false;
        $phototype = $this->getPhototypeById($id);
        try {
            // remove document
            $result = $this->_dm->createQueryBuilder($this->_document_class)
                    ->remove()
                    ->field('id')
                    ->equals($id)
                    ->getQuery()
                    ->execute();
        } catch (Exception $e) {
            $result = false;
        }
        return $result;
    }

    public function addPhototype($name, $description, $owner) {
        $result = false;
        $phototype = new $this->_document_class();
        $phototype->name = $name;
        $phototype->description = $description;
        $newPhoto->owner = $owner;
        $this->_dm->persist($newPhoto);
        $this->_dm->flush();
        $result = true;
        return $result;
    }

}
