<?php

class Angel_Model_Phototype extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Phototype';

    public function removePhototype($id) {

        $result = false;
        $phototype = $this->getById($id);
        if (!$phototype) {
            throw new Angel_Exception_Phototype(Angel_Exception_Phototype::PHOTOTYPE_NOT_FOUND);
        }
//        待检查， 为什么不能在Model中采用getModel方法
//        $photoModel = $this->getModel('photo');
//        $ps = $photoModel->getPhotoByPhototype($id);
//        if (!empty($ps)) {
//            throw new Angel_Exception_Phototype(Angel_Exception_Phototype::PHOTOTYPE_CANT_BE_REMOVED);
//        }
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
        $data = array("name" => $name, "description" => $description, "owner" => $owner);
        $result = $this->add($data);
        return $result;
    }

    public function savePhototype($id, $name, $description) {
        $data = array("name" => $name, "description" => $description);
        $result = $this->save($id, $data, Angel_Exception_Phototype, Angel_Exception_Phototype::PHOTOTYPE_NOT_FOUND);
        return $result;
    }

}
