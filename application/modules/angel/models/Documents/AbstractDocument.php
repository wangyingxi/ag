<?php
/**
 * The parent class for all document class
 * Contains the magic method to set/get the value to its properties
 */

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

abstract class AbstractDocument{
    
    /** @ODM\Id */
    protected $id;
    
    /** @ODM\Date */
    protected $created_at;
    
    /** @ODM\Date */
    protected $updated_at;

    
    /** @ODM\PrePersist */
    public function updateCreatedAt(){
        $this->created_at = new \DateTime();
    }

    /** @ODM\PreUpdate */
    public function updateUpdatedAt(){
        $this->updated_at = new \DateTime();
    }

    public function __set($name, $value){
        $class = get_class($this);
        
        $method = 'set'.strtoupper($name);
        if(method_exists($class, $method)){
            return call_user_func(array($this, $method), $value);
        }
        else if(property_exists($class, $name)){
            $this->$name = $value;
        }else{
            throw new \Exception("Property ".$name." not exist in ".$class);
        }
    }
    
    public function __get($name){
        $class = get_class($this);
        
        $method = 'get'.strtoupper($name);
        if(method_exists($class, $method)){
            return call_user_func(array($this, $method));
        }
        else if(property_exists($class, $name)){
            return $this->$name;
        }
        else{
            throw new \Exception("Property ".$name." not exist in ".$class);
        }
    }
}

?>
