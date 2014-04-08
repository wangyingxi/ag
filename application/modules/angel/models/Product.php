<?php

class Angel_Model_Product extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Product';

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
     * @param \Document\Brand $brand
     * @return mix - when user registration success, return the user id, otherwise, boolean false
     * @throws Angel_Exception_Product 
     */
    public function addProduct($title, $short_title, $sub_title, $sku, $status, $description, $photo, $location, $base_price, $selling_price, $owner, $brand) {
        $result = false;

        if ($this->isSkuExist($sku)) {
            throw new Angel_Exception_Product(Angel_Exception_Product::PRODUCT_SKU_EXIST);
        }
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
        if (is_array($selling_price)) {
            foreach ($selling_price as $price) {
                if (!is_float($price->amount)) {
                    throw new Angel_Exception_Product(Angel_Exception_Product::PRODUCT_PRICE_INVALID);
                }
                $product->addSellingPrice($price->currency, $price->amount);
            }
        }
        $product->owner = $owner;
        if ($brand) {
            $product->brand = $brand;
        }
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

    /**
     * 根据id获取user document
     * 
     * @param string $id
     * @return mix - when the user found, return the user document
     */
    public function getProductById($id) {
        $result = false;
        $user = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('id')->equals($id)
                ->getQuery()
                ->getSingleResult();

        if (!empty($user)) {
            $result = $user;
        }

        return $result;
    }
}
