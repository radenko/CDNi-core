<?php

/**
 * Object which handles all interonnection interfaces from other peer CDNs
 *
 * @author barbarka
 */
class InterconPeers {
    /** @var InterconPeer[String] List of interconnections*/
    private $clients = array();
    private $db;
    
    /**
     * 
     * @param DB $db
     */
    public function __construct($db) {
        $this -> db = $db;
    }
    
    /**
     * Gets item identified by index (url, peerID, interconnectionID
     * @param String $index
     */
    public function item($key) {
        return $this->clients[$key];
    }
    
    /**
     * Checks if interconnection interface exists
     * @param String $key
     * @return boolean
     */
    public function exists($key) {
        return array_key_exists($key, $this->clients);
    }

    /**
     * Adds all pears from database and creates clients to them.
     */
    function addDBPeers() {
        $dbResult = $this -> db -> select('interconnections');
            
        while ($item = $dbResult -> fetch_assoc()) {
                $this -> addPeerFromRow($item);
        }
    }
        
    /**
     * Add peer to list of peers
     * @param String[String] $params
     * @param boolean $replace Controls replacement during construction.
     *        All interconnections are indexed by id of peer, url, interconnectionID.
     * @return InterconPeer Interconnection client
     */
    public function addPeerFromRow($params,$replace=false) {
        $lparams = array_change_key_case($params);
        $id = null;
        $url = null;
        
        if (isset($lparams['cdnid']))
            $id = $lparams['cdnid'];
        if (isset($lparams['id']))
            $id = $lparams['id'];

        if (isset($lparams['peerurl']))
            $url = $lparams['peerurl']; 
        if (isset($lparams['url']))
            $url = $lparams['url'];
        if (isset($lparams['apiurl']))
            $url = $lparams['apiurl'];
        
        return $this -> addPeer($id,$url,$params,$replace);
    }
    
    /**
     * Add peer to list of peers
     * All interconnections are indexed by id of peer, url, interconnectionID.
     * 
     * @param String $id
     * @param String $url
     * @param mixed[String] $params
     * @param boolean $replace Controls replacement during construction.
     * @return InterconPeer New object for interconnection
     */
    public function addPeer ($id,$url,$params,$replace=false) {
        if (isset($params['interconID']))
            $interconID = $params['interconID'];
                
        if (isset($url) && !is_null($url) && $url && isset($this -> clients[$url]))
            $client = $this -> clients[$url];
        if (isset($id)  && !is_null($id)  && $id  && isset($this -> clients[$id]))
            $client = $this -> clients[$id];
        if (isset($interconID)  && !is_null($interconID)  && $interconID  && isset($this -> clients[$interconID]))
            $client = $this -> clients[$interconID];
        
        if (!isset($client) || !is_object($client) || $replace) {
            $params['peerURL'] = $url;
            if (isset($id))
                $params['CDNid'] = $id;
            
            $client = new InterconPeer($params);
        }
                
        if (isset($url) && !is_null($url) && $url)
            $this -> clients[$url] = $client;
        if (isset($id)  && !is_null($id)  && $id)
            $this -> clients[$id] = $client;
        if (isset($params['interconID']))
            $this -> clients[$params['interconID']] = $client;
        
        return $client;
    }
    
    /**
     * 
     * @param String $key unique id of interconnetcion (peerURL, peer CDN ID, interconID)
     * @param boolean $canCreate Creates interconnection from database if possible
     * @return InterconPeer
     */
    function getPeer($key, $canCreate = true) {
        if (!$this->exists($CDNid)) {
            if ($canCreate) {
                $dbResult = $this -> db -> select('interconnections','*',
                    array('WHERE' => "CDNid=$key")
                );
            
                while ($item = $dbResult -> fetch_assoc($result)) {
                    $this -> addPeerFromRow($item);
                }
            } else {
                return null;                
            }
        }
        
        return $this->item($CDNid);
    }
    
    /**
     * Calls remote method in peer instance
     * @param String $peerURL
     * @param String $method
     * @param mixed[] $params
     * @return array
     */
    function peerCall($peerURL,$method,$params) {
        if (defined("MODE_DEBUG")) {echo "Calling $method: ".PHP_EOL; var_dump($params);}
        
        $response = http_post_fields($peerURL."/".$method, $params);
        $responseObj=http_parse_message($response);
        $body=$responseObj->body;
        if (defined("MODE_DEBUG")) {echo "Result: $body".PHP_EOL;}
        
        $result=array();
        parse_str($body,$result);
                
        return $result;
    }

}

?>
