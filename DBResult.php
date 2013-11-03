<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DBResultWrapper class for raw result from query methods of databases.
 *
 * @author barbarka
 */
class DBResult {
    /** @var resource raw result from query command*/
    private $result = null;
    public $row;
    
    /**
     * Constructs new query result wrapper
     * @param resource $result result from query
     */
    public function __construct($result) {
        $this->result = $result;
    }
    
    public function __destruct() {
        $this->free();
    }
    
    /**
     * Returns numebr of rows in result
     * @return int
     */
    public function num_rows() {
        return mysql_num_rows($this->result);
    }
    
    /**
     * Fetch a result row as an object
     * @param String $class_name
     * @param array $params
     */
    public function fetch_object($class_name = null, $params = null) {
        return mysql_fetch_object($this->result, $class_name, $params);
    }
    
    /**
     * Return number of all fields in result
     * @return int
     */
    public function num_fields() {
        return mysql_num_fields($this->result);
    }
    
    /**
     * frees internal resource variable.
     */
    public function free() {
        if (!is_null($this->result)) {
            mysql_free_result($this->result);
            $this->result = null;
        }
    }
    
    /**
     * Return actual record from query result and increments internal pointer to next record.
     * @param resource $qr If not set local variable of this instance is taken
     * @return mixed[string]
     */
    function fetch_assoc() {  
        return mysql_fetch_assoc($this->result); 
    }

    /**
     * Return actual record from query result and increments internal pointer to next record.
     * @param resource $qr If not set local variable of this instance is taken
     * @return mixed[string|int]
     */
    function fetch_array($result_type='MYSQL_BOTH') {
        return mysql_fetch_array($this->result, $result_type); 
    }

    /**
     * Get result data
     * @param int $row
     * @param mixed $field
     * @return type
     */
    function result($row=0, $field=0) { 
        return mysql_result($this->result, $row, $field);
    }

    /**
     * Return actual record from query result and increments internal pointer to next record.
     * @param resource $qr If not set local variable of this instance is taken
     * @return mixed[int]
     */    
    function fetch_row() {
        return mysql_fetch_row($this->result); 
    }
    
    /**
     * Get column information from a result and return as an object
     * @param int $field_offset
     * @return object
     */
    function fetch_field($field_offset = 0) {
        return mysql_fetch_field($this->result, $field_offset);
    }
    
    /**
     * Get the length of each output in a result
     * @return array|false Description
     */
    function fetch_lengths () {
        return mysql_fetch_lengths($this->result);
    }
    
    function read () {
        $this->row = $this->fetch_array();
    }
    
    /**
     * Fetches field from row
     * @param type $name
     * @param int $row Defines row number
     * @return type
     */
    function fetch_namedField($name, $row=null) {
        if (is_null($row)) {
            if (is_null($this->row)) {
                $this->read();
            }
        
            return $this->row[$name]; 
        } else {
            throw new Exception("Not yet implemented");       
            return $this->fetch_field($num, $row);
        }
    } 
}

?>
