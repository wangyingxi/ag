<?php
namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Candidate extends AbstractDocument{
    
    /** @ODM\String */
    protected $name;
    
    /** @ODM\String */
    protected $phone;
    
    /** @ODM\String */
    protected $email;
    
    /** @ODM\String */
    protected $relationship;
    
    /** @ODM\ReferenceOne(targetDocument="\Documents\User") */
    protected $user;

    /** @ODM\Boolean */
    protected $accepted_bln;    // 是否接受了邀请, false表示还未接受邀请
    
    /** @ODM\Boolean */
    protected $refused_bln;     // 是否拒绝了邀请
    
    /** @ODM\String */
    protected $refused_reason;   // 拒绝邀请的原因

    public function getRequiredFields(){
        return array("name"=>'姓名', 'phone'=>'电话', 'email'=>'email地址', 'relationship'=>'关系');
    }
    
    public function validateInfo(){
        $fields = array();
        
        foreach($this->getRequiredFields() as $field=>$label){
            $value = $this->$field;
            if(empty($value)){
                $fields[] = $label;
            }
        }
        
        return $fields;
    }
}

?>
