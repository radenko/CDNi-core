<?

class DBException extends Exception {}

class DB {
    /**@var resource*/
    protected $link = null;
    /**@var mixed[]*/
    protected $config = null;
    /**@var resource*/
    protected $qr = null;

    /**
     * cretes new command, which can be executed
     * @return DBCommand
     */
    public function createCommand() {
        return new DBCommand($this);
    }
    
    /**
     * 
     * @return resource
     */
    function getLink() {
        if (  is_null($this -> link)  ) {
            if(isset($config['databasePass']))        
                $this -> link = mysql_connect($this->config['databaseHost'],  $this->config['databaseUser'],  $this->config['databasePass']);
            else
                $this -> link = mysql_connect($this->config['databaseHost'],  $this->config['databaseUser']);

            mysql_select_db($this->config['databaseName'],$this -> link);        
        }
        
        return $this -> link;
    }
    
    /**
     * 
     * @param mixed[] $config
     */
    function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * 
     * @param string $query
     * @return DBResult
     */
    function query($query) {
        $res = mysql_query($query, $this->getLink());
        
        if (  mysql_errno( $this->getLink() )  )
            throw new DBException("Error on query (".$query."): ".mysql_error( $this->getLink() ));
        
        $this->qr = $res?new DBResult($res):null;
        return $this->qr;
    }
    
    /**
     * 
     * @param resource $qr
     * @return int
     */
    function num_rows($qr=null) {
        if (is_null($qr)) $qr = $this->qr;        
        return mysql_num_rows($qr); 
    }

    /**
     * 
     * @param mixed[] $values
     * @param boolean $nokeys
     * @param boolean $novalues
     * @return string
     */
    function formatKeysValues($values,$nokeys=false,$novalues=false) {
        $result='';
        
        foreach ($values as $key => $value) {
            if ($result)                $result.=',';
            if (!$nokeys)               $result .= "`$key`";
            if (!$nokeys && !$novalues) $result .= '=';
            if (!$novalues)             $result .= '\'' . mysql_escape_string($value) . '\'';
        }
        
        return $result;
    }

    /**
     * 
     * @param mixed[] $values
     * @return string
     */
    function formatValues($values) {
        return $this->formatKeysValues($values,true,false);
    }

    /**
     * 
     * @param string[] $keys
     * @return string
     */
    function formatKeys($keys) {
        return $this->formatKeysValues(array_combine($keys, $keys),false,true);
    }
    
    /**
     * 
     * @param string $table
     * @param mixed[] $values
     * @param boolean $returnID
     * @return int|resource
     */
    function insert($table,$values,$returnID=false) {
        $valueStr = $this->formatKeysValues($values);
        $query  = "INSERT INTO $table SET $valueStr;";        
        if ($returnID) {
            $this->query($query);
            $query = "SELECT LAST_INSERT_ID();";
            $qr = $this->query($query);
            return mysql_result($qr,0);
        } else {
            return $this->query($query);
        }
    }

    /**
     * Insert values to table if already existing, nothing done
     * @param string $table
     * @param mixed[] $values
     * @param boolean $returnID If set to true last inserted id is returned
     * @return resource|int
     */
    function insertIgnore($table,$values,$returnID=false) {
        $valueStr = $this->formatKeysValues($values);
        $query  = "INSERT IGNORE INTO $table SET $valueStr;";
        if ($returnID) {
            $this->query($query);
            $query = "SELECT LAST_INSERT_ID();";
            $qr = $this->query($query);
            return mysql_result($qr,0);
        } else {
            return $this->query($query);
        }
    }

    /**
     * Inserts record to table, if key viaolation occured updates
     * @param string $table
     * @param mixed[] $values
     * @param boolean $returnID
     * @return int|resource
     */
    function insertUpdate($table,$values,$returnID=false) {
        $valueStr = $this->formatKeysValues($values);
        $query  = "INSERT INTO $table SET $valueStr ON DUPLICATE KEY UPDATE $valueStr;";

        if ($returnID) {
            $this->query($query);
            $query = "SELECT LAST_INSERT_ID();";
            $qr = $this->query($query);
            return mysql_result($qr,0);
        } else {
            return $this->query($query);
        }
    }

    /**
     * Flushes all tables in selected database
     * Database is taken from config
     */
    function flushdatabase(){
		echo "Entering the flushdatabase function"."<br \>";	
		$database = $this->config['databaseName'];
                
		$query = "SHOW TABLES FROM $database";
	
		$res = $this->query($query);
		
		while($showtablerow = mysql_fetch_array($res, MYSQL_NUM))
		{
			echo "The following table will be flushed:".$showtablerow[0]."<br \>";
		
			$query2 = "TRUNCATE TABLE $showtablerow[0]";
			$res2 = $this->query($query2);
		//$flushed = mysql_query("TRUNCATE TABLE $showtablerow[0]");
		
			echo "Flushed"."<br \>";
		}
                
		echo "End \n";
    }
    
    /**
     * Return actual record from query result and increments internal pointer to next record.
     * @param DBResult $qr If not set local variable of this instance is taken
     * @return mixed[string]
     */
     /*function fetch_assoc(DBResult $qr=null) {
        if (is_null($qr)) $qr = $this->qr;        
        return $qr->fetch_assoc(); 
    }*/

    /**
     * Return actual record from query result and increments internal pointer to next record.
     * @param resource $qr If not set local variable of this instance is taken
     * @return mixed[string|int]
     */
    function fetch_array($qr=null) {
        if (is_null($qr)) $qr = $this->qr;        
        return mysql_fetch_array($qr); 
    }

    function fetch_result($qr=null, $row=0, $field=0) {
        if (is_numeric($qr)) {
            $row = $qr;
            $field = $row;
            $qr = null;
        }
        
        if (is_null($qr)) $qr = $this->qr;
 
        return mysql_result($qr, $row, $field);
    }

    /**
     * Return actual record from query result and increments internal pointer to next record.
     * @param resource $qr If not set local variable of this instance is taken
     * @return mixed[int]
     */    
    function fetch_row($qr=null) {
        if (is_null($qr)) $qr = $this->qr;        
        return mysql_fetch_row($qr); 
    }
    
    function fetch_object($qr=null, $class_name = null, array $params = null) {
        if (is_null($qr)) $qr = $this->qr;        
        return mysql_fetch_object($qr, $class_name, $params); 
    }
    
    function fetch_field($qr=null, $field_offset = 0) {
        if (is_null($qr)) $qr = $this->qr;
        return mysql_fetch_field($qr, $field_offset);
    }
    
    function fetch_lengths ($qr=null) {
        if (is_null($qr)) $qr = $this->qr;
        return mysql_fetch_lengths($qr);
    }

    function result($row=0, $field=0) {
        return mysql_result($this->qr, $row, $field);
    }
    
    /**
     * Executes select on database
     * @param string $table
     * @param mixed[] $values
     * @param mixed[] $options
     * @return resource
     */
    function select($table,$values="*",$options=array()) {
        if (is_array($values))
            $valueStr = $this->formatKeys($values);
        else
            $valueStr = $values;
        
        $query = "SELECT $valueStr FROM $table";
        $options = array_change_key_case($options, CASE_UPPER);
        
        if (isset($options['WHERE'])) $query.=" WHERE ".$options['WHERE'];
        
        return $this->query($query);        
    }
    
    /**
     * Returns error message
     * @param resource $dbLink, If not set, local attribute from this instance is taken
     * @return string
     */
    function error($dbLink = null) {
        if (is_null($dbLink))
            $dbLink = $this->getLink();            
        return mysql_error($dbLink);       
    }

    /**
     * Returns error message
     * @param resource $dbLink, If not set, local attribute from this instance is taken
     * @return int
     */
    function errno($dbLink = null) {
        if (is_null($dbLink))
            $dbLink = $this->getLink();            
        return mysql_errno($dbLink);       
    }
    
    /**
     * Escapes string
     * @param string $unescaped_string
     * @return string
     */
    function escape_string($unescaped_string) {    
        return mysql_escape_string($unescaped_string);
    }
}

/**
 * Connects to database
 * @global mixed[] $config Configuration loaded from config file
 * @return resource
 */
function db_connect() {
        global $config;
        
	if(isset($config['databasePass']))        
		$dbConn=mysql_connect($config['databaseHost'],$config['databaseUser'],$config['databasePass']);
	else
		$dbConn=mysql_connect($config['databaseHost'],$config['databaseUser']);

        mysql_select_db($config['databaseName'],$dbConn);
        
        return $dbConn;
}
?>
