<?php
/**
 * @author powerdream5
 * 所有model的父类 
 */
abstract class Angel_Model_AbstractModel{
    
    protected $_bootstrap;
    protected $_angel_bootstrap;
    protected $_bootstrap_options;
    protected $_dm;
    protected $_container;
    protected $_logger;
    protected $models = array();

    public function __construct($bootstrap){
        $this->_bootstrap = $bootstrap;
        $this->_angel_bootstrap = $this->_bootstrap->getResource('modules')->offsetGet('angel');
        $this->_bootstrap_options = $this->_bootstrap->getOptions();
        $this->_container = $this->_bootstrap->getResource('serviceContainer');
        $this->_dm = $this->_angel_bootstrap->getResource('mongoDocumentManager');
        $this->_logger = $this->_bootstrap->getResource('logger');
    }
    
    public function getDocumentClass(){
        return $this->_document_class;
    }
    
    public function paginator($query){
        $adapter = new Angel_Paginator_Adapter_Mongo($query);
        return new Zend_Paginator($adapter);
    }
    
    public function getModel($modelName){
        $modelName = 'Angel_Model_' . ucwords($modelName);
        if(!isset($models[$modelName])){
            $models[$modelName] = new $modelName($this->bootstrap);
        }
        
        return $models[$modelName];
    }
}
