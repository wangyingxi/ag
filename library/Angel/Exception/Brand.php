<?php

class Angel_Exception_Brand extends Angel_Exception_Abstract {
    
    const BRAND_NOT_FOUND = 'brand_not_found';
    const BRAND_CANT_BE_REMOVED = 'brand_cant_be_removed';

    private static $_brand = null;

    /**
     * a static method to wrap getDetail
     * brand singlaton pattern
     */
    public static function returnDetail($msg_code) {
        if (!self::$_brand) {
            self::$_brand = new Angel_Exception_Brand('');
        }
        self::$_brand->setMessageCode($msg_code);

        return self::$_brand->getDetail();
    }

    /**
     * 返回exception的描述信息
     * @return string
     */
    public function getDetail() {
        switch ($this->msg_code) {
            case self::BRAND_NOT_FOUND:
                return '品牌未找到';
            case self::BRAND_CANT_BE_REMOVED:
                return '品牌无法删除';
        }
    }

}

?>
