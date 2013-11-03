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
    public $parameters = array();
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
        $this->parameters[$name] = $value;
    }
    
    /**
     * 
     * @return DBResult
     */
    public function execute() {
       $query = $this->text;
       foreach ($this->parameters as $name=>$value) {
           if (is_numeric($value))
               $eval = $value;
           elseif (is_object($value))
               $eval = "'".mysql_escape_string($value->__toString())."'";
           else
               $eval = "'".mysql_escape_string($value)."'";
           
           $query = str_replace ($name, $eval, $query);
       }
       
       $qr = $this->parent->query($query);
       $this->result = $qr!==false ? (   is_resource($qr) ? new DBResult($qr) : $qr   ) : null; 
       return $this->result;
    }
    
    //put your code here
}

?>
