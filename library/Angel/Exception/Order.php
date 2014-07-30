<?php

class Angel_Exception_Order extends Angel_Exception_Abstract{
    
    const ORDER_NOT_FOUND = 'order_not_found';
    const ORDER_CREATE_FAILED = 'order_create_failed';
    
    /**
     * 返回exception的描述信息
     * @return string
     */
    public function getDetail(){
        switch($this->msg_code){
            case self::ORDER_NOT_FOUND:
                return 'Order can not be found';
            case self::ORDER_CREATE_FAILED:
                return 'Error occurred when order was creating';
        }
    }
}

?>
