<?php

/**
 * This class provides interface to other instance of CDNi interface.
 * Via this class CDN can call remote procedures from other party.
 *
 * @author barbarka
 */
class InterconPeer {
    /** @var SoapClient*/
    protected $_client=null;
    /** @var String local status of this interconnection*/    
    protected $_localStatus=null;
    /** @var String status of interconnection in peer CDN*/
    protected $_peerStatus=null;
    /** @var String Common ID of interconnection*/
    protected $_interconID=null;
    /** @var String*/
    protected $_peerURL;

    /**
     * Constructor of this instance
     * @param mixed[String] $params
     * @throws Exception
     */
    function __construct($params) {
        if (!array_key_exists('peerURL', $params)) {
            throw new MissingParameterException('peerURL');
        }

        $this->_peerURL = $params['peerURL'];
        $this->_client = new SoapClient(
                null,
                array('location' => $this->_peerURL,
                      'uri'      => $this->_peerURL,
                      'trace' => 1
                )
            );
        $this->setParams($params);
    }
    
    /**
     * Status of interconnection in peer CDN
     * @return String
     */
    function getPeerStatus() {
        return $this->_peerStatus;
    }

    /**
     * 
     * @return String Common ID of interconnection
     */
    function getInterconID() {
        return $this->_interconID;
    }

    /**
     * Local status of this interconnection 
     * @return String
     */
    function getLocalStatus() {
        return $this->_localStatus;
    }
    
    /**
     * Returns current client instance
     * @return SoapClient
     */
    function getClient() {
        return $this -> _client;
    }
        
    /**
     * Sets parameters to this instance in one
     * Supported parameters:
     *  - localStatus
     *  - peerStatus
     *  - interconID
     * @param mixed[String] $params Array indexed by strings containing paramaters to this class
     */
    function setParams($params) {
        $params = array_change_key_case($params);
     
        if (isset($params['localStatus'])) $this -> _localStatus = $params['localstatus'];
        if (isset($params['peerStatus'])) $this -> _peerStatus = $params['peerstatus'];
        if (isset($params['interconID'])) $this -> _interconID = $params['interconid'];        
    }
     
    /**
     * Function to be called when invoking any method to this class.
     * http://www.php.net/manual/en/language.oop5.overloading.php#object.call
     * Function name is transfered to real function call
     * invokaction is forwarded to remote party via client
     * @param String $name name of function to be called
     * @param mixed[] $arguments array of arguments 
     * @return mixed
     */
    function __call($name, $arguments) {
        if (MODE_DEBUG) {
            echo "Calling '$name' with";
            print_r($arguments);
            echo "<br/>";
        }
        
        $res = $this->_client->__call($name,$arguments);
        
        if (MODE_DEBUG) {
            echo "Response:";
            var_dump($res);
            echo "<br/>";
        }
        
        return $res;
    }
}

?>
