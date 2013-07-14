<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DBCommand
 *
 * @author barbarka
 */
class DBCommand {
    /** @var DB*/
    private $parent;
    /** @var mixed[String] */
    public $parameters;
    /** @var String*/
    public $text;
    /** @var DBResult*/
    public $result;
    /**
     * 
     * @param DB $parent
     */
    public function __construct($parent) {
        $this->parent = $parent;
    }
    
    /**
     * 
     * @param String $name
     * @param mixed $value
     */
    public function addParameter($name, $value) {
        $parameters[$name] = $value;
    }
    
    /**
     * 
     * @return DBResult
     */
    public function execute() {
       $query = $this->text;
       foreach ($this->parameters as $name=>$value) {
           if (is_string($value))
                $eval = "'".mysql_escape_string($value)."'";
           if (is_numeric($value))
               $eval = $value;
           if (is_object($value))
               $eval = "'".mysql_escape_string($value->__toString())."'";
           
           $query = str_replace ($name, $eval, $query);
       }
        
       $this->result = new DBResult($this->parent->query($query)); 
       return $result;
    }
    
    //put your code here
}

?>
