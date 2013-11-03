<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DBResult
 *
 * @author barbarka
 */
class DBResult {
    /**@var resource*/
    public $result;
    
    public function __construct($result) {
        $this->result = $result;
    }
    
    public function rows() {
        return mysql_numrows($this->result);   
    }
    
    public function fetch_assoc() {
        return mysql_fetch_assoc($this->result);
    }
    
    public function fetch_row() {
        return mysql_fetch_row($this->result);
    }
    
    public function fetch_array() {
        return mysql_fetch_array($this->result);
    }
    
    public function fetch_field($field_offset=0) {
        return mysql_fetch_field($this->result, $field_offset);
    } 
}

?>
