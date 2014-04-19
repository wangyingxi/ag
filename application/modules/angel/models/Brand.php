<?php

class Angel_Model_Brand extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Brand';
    protected $_document_user_class = '\Documents\User';

//    public function removeBrand($id) {
//        $result = false;
//        $brand = $this->getById($id);
//        try {
//            // remove document
//            $result = $this->_dm->createQueryBuilder($this->_document_class)
//                    ->remove()
//                    ->field('id')
//                    ->equals($id)
//                    ->getQuery()
//                    ->execute();
//        } catch (Exception $e) {
//            $result = false;
//        }
//        return $result;
//    }

    public function saveBrand($id, $name, $description, $logo) {
        $data = array("name" => $name, "description" => $description, "logo" => $logo);
        $result = $this->save($id, $data, Angel_Exception_Brand, Angel_Exception_Brand::BRAND_NOT_FOUND);
        return $result;
    }

    public function addBrand($name, $description, $logo, $owner) {
        $data = array("name" => $name, "description" => $description, "logo" => $logo, "owner" => $owner);
        $result = $this->add($data);
        return $result;
    }


}
