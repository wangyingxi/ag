<?php

class Angel_Exception_Product extends Angel_Exception_Abstract {

    const ADD_PRODUCT_FAIL = 'add_product_fail';
    const PRODUCT_SKU_EXIST = 'product_sku_exist';
    const PRODUCT_PRICE_INVALID = 'product_price_invalid';

    /**
     * 返回exception的描述信息
     * @return string
     */
    public function getDetail() {
        switch ($this->msg_code) {
            case self::ADD_PRODUCT_FAIL:
                return '添加商品失败';
            case self::PRODUCT_SKU_EXIST:
                return '商品SKU已经存在，请重新指定';
            case self::PRODUCT_PRICE_INVALID:
                return '价格必须是小数';
        }
    }

}

?>
