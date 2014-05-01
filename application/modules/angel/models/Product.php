<?php

class Angel_Model_Product extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Product';
    protected $_document_user_class = '\Documents\User';

    /**
     * 添加商品
     * 
     * @param string $title
     * @param string $short_title
     * @param string $sub_title
     * @param string $sku
     * @param string $description
     * @param array $photo
     * @param array $location
     * @param float $base_price
     * @param array $selling_price
     * @param \Documents\User $owner
     * @param array $scale
     * @param \Document\Brand $brand
     * @param \Document\Category $category
     * @param array $css
     * @return mix - when user registration success, return the user id, otherwise, boolean false
     * @throws Angel_Exception_Product 
     */
    public function addProduct($title, $short_title, $sub_title, $sku, $status, $description, $photo, $location, $base_price, $selling_price, $owner, $scale, $brand, $category, $css) {
        $result = false;

//        if ($this->isSkuExist($sku)) {
//            throw new Angel_Exception_Product(Angel_Exception_Product::PRODUCT_SKU_EXIST);
//        }
        if (!is_float($base_price)) {
            throw new Angel_Exception_Product(Angel_Exception_Product::PRODUCT_PRICE_INVALID);
        }

        $product = new $this->_document_class();
        if (is_array($photo)) {
            foreach ($photo as $p) {
                $product->addPhoto($p);
            }
        }
        $product->title = $title;
        $product->short_title = $short_title;
        $product->sub_title = $sub_title;
        $product->sku = $sku;
        $product->status = $status;
        $product->description = $description;
        $product->location = $location;
        $product->base_price = $base_price;
        $product->selling_price = $selling_price;
        $product->scale = $scale;
        $product->owner = $owner;
        $product->brand = $brand;
        $product->category = $category;
        $product->css = $css;
        try {
            $this->_dm->persist($product);
            $this->_dm->flush();

            $result = $product->id;
        } catch (Exception $e) {
            $this->_logger->info(__CLASS__, __FUNCTION__, $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Angel_Exception_Product(Angel_Exception_Product::ADD_PRODUCT_FAIL);
        }

        return $result;
    }

    public function getProductByBrand($brand_id, $return_as_paginator = true) {
        $query = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('brand.$id')->equals(new MongoId($brand_id))
                ->sort('created_at', -1);
        $result = null;
        if ($return_as_paginator) {
            $result = $this->paginator($query);
        } else {
            $result = $query->getQuery()->execute();
        }
        return $result;
    }

    public function getProductByCategory($category_id, $return_as_paginator = true) {
        $query = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('category.$id')->equals(new MongoId($category_id))
                ->sort('created_at', -1);
        $result = null;
        if ($return_as_paginator) {
            $result = $this->paginator($query);
        } else {
            $result = $query->getQuery()->execute();
        }
        return $result;
    }

    /**
     * 编辑商品
     * @param string $id
     * @param string $title
     * @param string $short_title
     * @param string $sub_title
     * @param string $sku
     * @param string $description
     * @param array $photo
     * @param array $location
     * @param float $base_price
     * @param array $selling_price
     * @param \Documents\User $owner
     * @param array $scale
     * @param \Document\Brand $brand
     * @param \Document\Category $category
     * @param array $css
     * @return mix - when user registration success, return the user id, otherwise, boolean false
     * @throws Angel_Exception_Product 
     */
    public function saveProduct($id, $title, $short_title, $sub_title, $sku, $status, $description, $photo, $location, $base_price, $selling_price, $owner, $scale, $brand, $category, $css) {
        $result = false;
        if (!is_float($base_price)) {
            throw new Angel_Exception_Product(Angel_Exception_Product::PRODUCT_PRICE_INVALID);
        }

        $product = $this->getById($id);
        if (!$product) {
            throw new Angel_Exception_Product(Angel_Exception_Product::PRODUCT_NOT_FOUND);
        }
        // 清空图片
        $product->clearPhoto();
        // 重新添加图片并保存
        if (is_array($photo)) {
            foreach ($photo as $p) {
                $product->addPhoto($p);
            }
        }
        $product->title = $title;
        $product->short_title = $short_title;
        $product->sub_title = $sub_title;
        $product->sku = $sku;
        $product->status = $status;
        $product->description = $description;
        $product->location = $location;
        $product->base_price = $base_price;
        $product->selling_price = $selling_price;
        $product->scale = $scale;
        $product->owner = $owner;
        $product->brand = $brand;
        $product->category = $category;
        $product->css = $css;
        try {
            $this->_dm->persist($product);
            $this->_dm->flush();

            $result = $product->id;
        } catch (Exception $e) {
            $this->_logger->info(__CLASS__, __FUNCTION__, $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Angel_Exception_Product(Angel_Exception_Product::SAVE_PRODUCT_FAIL);
        }

        return $result;
    }

    /**
     * 根据SKU验证商品是否存在
     * 
     * @param string $sku － 需要被检测的商品SKU
     * @return boolean 
     */
    public function isSkuExist($sku) {
        $result = false;
        $product = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('sku')->equals($sku)
                ->getQuery()
                ->getSingleResult();

        if (!empty($product)) {
            $result = true;
        }
        return $result;
    }

    public function removeProduct($id) {
        $result = false;
        $product = $this->getById($id);
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

}
