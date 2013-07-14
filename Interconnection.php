<?
class Interconnection {
    /**@var mixed[] Array of all configration items*/
    protected $config;
    /**@var DB Object to handle all queries to local database of CDNi interface*/
    protected $db = null;
    /**@var */
    public $iCDN = null;
    /**@var InterconPeers */
    protected $peers;

    /**
     * 
     * @param mixed[] $config Array of all configration items
     */
    function __construct($config) {
        $this -> config = $config;       
        $this -> db = new DB($this->config['i']);
        $this -> peers = new InterconPeers($this->db);
    }
        
    /**
     * 
     * @param mixed[] $config Array of all configration items
     */
    function setConfig($config) {
        $this -> config = $config;
    }
            
    function peerSetCapabilities($peerURL) {
        echo "Sending capability to $peerURL".PHP_EOL;
        $this->peers->item($peerURL)->setCapabilities(
                $this -> config['id'],
                $this -> config['capabilities']
        );
    }
    
    function peerSetFootprint($peerURL) {
        echo "Sending footprint to $peerURL".PHP_EOL;
        $this->peers->item($peerURL)->setFootprint(
                $this -> config['id'],
                implode(",",$this -> config['footprint'])
        );
    }
    
    function peerSetOfferLocalStatus($interconID,$status) {     
        $this->db->query("SELECT CDNid, peerURL FROM interconnections WHERE interconID='". $this->db->escape_string($interconID) ."';");       
        if ($this->db->errno()) echo $this->db->error ();
        else {
            $peer = $this->db->fetch_assoc();
            $this->addPeer($peer);
            
            $peerURL = $peer['peerURL'];
            if (defined('MOD_DEBUG')) echo "Sending local status ($status) to $peerURL".PHP_EOL;

            $this->peers->item($peerURL)->setOfferLocalStatus($this -> config['id'],  $status);
        }        
    }

    function peerSetOffer($peerURL) {
        if (defined('MOD_DEBUG')) echo "Sending offer to $peerURL".PHP_EOL;
        
        $result = $this -> peers -> item($peerURL) -> setOffer(
            $this -> config['id'],
            $this -> config['APIurl']
        );
        
        return $result;
    }
    
    function peerSetContentBasicMetadata($peerURL,$contentID,$metadata) {
        if (defined('MOD_DEBUG')) echo "Sending SetContentBasicMetadata to $peerURL".PHP_EOL;
        
        $fields=array(
            'CDNid' => $this -> config['id'],
            'contentID'  => $contentID,
            'metadata' => $metadata
        );
        
        $result = $this -> peerCall($peerURL, "setContentBasicMetadata", $fields);
        
        return $result;       
    }
    
    /**
     * Send offers to all CDNi interfaces defined in config
     * Every interconnection is stored in database with actual status
     */
    function processOffers() {        
        if (isset($this -> config['i']['peers'])) {
            foreach ($this -> config['i']['peers'] as $peer) {
                echo "Processing: "; var_dump($peer);
                $comm = $this->db->createCommand();
                $comm->text = "SELECT * FROM interconnections WHERE peerURL = :peerURL";
                $comm->addParameter(":peerURL", $peer['APIurl']);
                $res = $comm->execute();
                if ($res ->  <= 0) {
                    $this->db->insertIgnore('interconnections',array("peerURL" => $peer['APIurl'],"localStatus"=>"offer"));
                } else {
                    $iterconn = $this->db->         
                }
                
                   
                    
                    $this -> addPeer($peer);
                    $peerObj = $this -> peerSetOffer ($peer['APIurl']);
                        
                    if (isset($peerObj) && !is_null($peerObj) && $peerObj !== false) {
                        $peer['id'] = $peerObj['CDNid'];
                        $this->addPeer($peer);

                        $this->db->insertIgnore("interconnections",
                                array (
                                    'CDNid' => $peer['id'],
                                    'peerURL' => $peer['APIurl'],
                                    'localStatus' => 'offer'     
                                )
                        );
                                
                        if ($this->db->errno()) echo $this->db->error () . PHP_EOL;
                    }
                }
            }
        }
        

        $this -> db -> select('interconnections', array('interconID', 'CDNid', 'peerURL', 'localStatus', 'peerStatus'), array('WHERE' => "localStatus='offer'"));

        if ($this -> db -> errno()) echo $this->db->error ();
        else {
            while ( $peer = $this -> db -> fetch_assoc() ) {
                $this -> addPeer($peer);                
                $this -> peerSetCapabilities($peer['peerURL']);
                $this -> peerSetFootprint   ($peer['peerURL']);
            }
        }
    }

    /**
     * Returns all intercooections
     * @return string[][]
     */
    function getAllInterconnections() {
        $intercons=array(); 
        $this -> db -> select(' interconnections','*',array('WHERE' => "peerStatus='complete'"));
        while ($intercon = $this->db->fetch_assoc()) {
            array_push($intercons,$intercon);
        }
        return $intercons;
    }
    
    /**
     * Process all content, and chooses items, which should be distributed
     */
    function processContentForTransfer() {
        $this -> addAllPeers();
        $intercons=$this->getAllInterconnections();
        
        $this -> db -> select('content');
        while($content = $this->db->fetch_assoc()) {
            foreach ($intercons as $intercon)
                $this -> distributeContent($content,$intercon);  
        }
    }

    /**
     * Process all content from local CDN, and chooses items, which should be distributed
     */
    function processLocalContentForTransfer() {
        $this -> addAllPeers();
        $intercons=$this->getAllInterconnections();
        
        if (!is_null($this->iCDN)) {
            $contents = $this->iCDN->getAllContent();

            foreach($contents as $content) {
                foreach ($intercons as $intercon) {
                    $this -> distributeContent($intercon,$content);
                }
            } 
        }
    }

    /**
     * Executes all necessary methods for processing
     * This method is core method, it can be equaled to main function
     */
    function cron() {
        header('Content-type: text/plain');
        $this -> processOffers();
        $this -> processContentForTransfer();
        $this -> processLocalContentForTransfer();
    }
	
    /**
     * Reset all interconnections in current instance
     * Should be used with patience
     */
    function reset() {
        $this -> db -> flushdatabase();
        $this -> onInterconnectionReset ();
    }
    
    /**
     * Calls methods from this instance by names and shows response in stdout
     * @param string[] $methods
     * @param mixed[] $data Parameters provided to function
     */
    function processAPI($methods,$data) {
        foreach ($methods as $method) {
            $result=call_user_func_array(array($this, $method), $data);
            if (is_array($result) && count($result)) echo http_build_query($result);
        }    
    }
    
    function setCapabilities ($CDNid,$capabilities) {
        $sql="SELECT interconID FROM interconnections WHERE CDNid='". $this->db->escape_string($CDNid) ."';";
        $this-> db ->query ($sql);

        if ($this-> db ->errno()) echo $this -> db->error () . PHP_EOL;
        else {
            $interconID = $this -> db -> result($qr,0);
            if (!is_numeric($interconID)) echo "interconID is not numeric".PHP_EOL;
            else {       
                $qr = $this->db->query("DELETE FROM peerCapabilities WHERE interconID=$interconID;");       
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL;        
        
                $qr = $this->db->query("INSERT INTO peerCapabilities SET interconID=$interconID, name='', value='".  $this->db->escape_string($capabilities)  ."';");       
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
                else {            
                    $this -> updateCompleteStatus($interconID);
                    return true;
                }
            }
        }

        return false;
    }
    
    function updateFootprint($interconID, $subnet) {
            list($subnetIP,$mask)=explode("/",$subnet);
            $subnetLong=  ip2long($subnetIP);
            $maskLong=0xFFFFFFFF-(pow(2, 32-$mask)-1);
            
            $sql="INSERT INTO peerFootprints SET interconID=$interconID". 
                                               ",subnet='".  mysql_escape_string($subnetLong) ."'".
                                               ",mask='".  mysql_escape_string($maskLong)  ."'".
                                               ",subnetIP='".  mysql_escape_string($subnetIP) ."'".
                                               ",maskNr='".  mysql_escape_string($mask)  ."'".
                                               ";";
            mysql_query($sql);
            if (mysql_errno()) echo mysql_error () . PHP_EOL;
    }
    
    function setFootprint($CDNid,$footPrint) {
        $sql="SELECT interconID FROM interconnections WHERE CDNid='". mysql_escape_string($CDNid) ."';";
        $this -> db -> query ($sql);

        if ($this -> db -> errno()) echo $this -> db -> error () . PHP_EOL;
        else {
            $interconID = $this -> db -> result();
//            echo "found interconnection: $interconID \n";
            if (!is_numeric($interconID)) echo "interconID is not numeric".PHP_EOL;
            else {
                $qr = $this -> db -> query("DELETE FROM peerFootprints WHERE interconID=$interconID;");
                if ($this -> db -> errno()) echo $this -> db -> error () . PHP_EOL;
                else {
                    $subnets=explode(",",$footPrint);
                    foreach ($subnets as $subnet) {
                        $this ->updateFootprint($interconID,$subnet);
                    }
            
                    $this -> updateCompleteStatus($interconID);
            
                    return true;
                }
            }
        }

        return false;
    }

    function setOffer($CDNid,$peerURL) {
        file_put_contents("debug",var_export($CDNid,true).var_export($peerURL,true));
        
        
        $this->db->insertUpdate("interconnections",
                array(
                    'CDNid' => $CDNid,
                    'peerURL' => $peerURL,
                    'peerStatus' => 'offer'
                )
        );
        if ($this->db->errno()) echo $this->db->error() . PHP_EOL;
        else {
            return array("CDNid" => $this -> config['id']);
        }
        
        return false;
    }
    
    function setOfferLocalStatus($CDNid,$status) {
        $this->db->query("UPDATE interconnections SET localStatus='".  $this->db->escape_string($status). "' WHERE CDNid = '". mysql_escape_string($CDNid) . "';");       
        if ($status == 'complete')
            $this->doInterconnectionComplete ($CDNid);

        if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
        else return true;
        
        return false;        
    }

    function distributeContent($intercon, $content) {
        if (!is_array($content)) {
            $this->db->select('content','*',array('WHERE' => "contentID=$contentID"));
            if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
            $content = $this->db->fetch_assoc();
        }
        
        if (!is_array($intercon)) {
            $this->db->select("interconnections","*",array('WHERE'=>"interconID=$interconID"));
            if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
            $intercon = $this->db->fetch_assoc();
        }
        
        $internalContentID = isset($content['internalContentID'])?$content['internalContentID']:0;
        $contentID = $content['contentID'];
        $interconID = $intercon['interconID'];

        if ($internalContentID) {
            $metadata=array_intersect_key($content, array('title'=>1,'description'=>1,'url'=>1));
            $this->db->select("contentMetadata","*",array("WHERE"=>"internalContentID=$internalContentID"));
            while ($row = $this->db->fetch_assoc()) {
                $metadata[$row['name']]=$row['value'];
            }
        } else {
            $metadata = array_diff_key($content,array('contentID'=>1));
        }
        
        //var_dump($this->config['id'],$contentID,$metadata);
        
        $this->peers($interconID)->setContentBasicMetadata($this->config['id'],$contentID,$metadata);
    }

    function setContentBasicMetadata($CDNid,$contentID,$metadata) {
        $this->db->select("interconnections","*",array('WHERE'=>"CDNid='".$this->db->escape_string($CDNid)."'"));
        if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
        else {
            $intercon = $this->db->fetch_assoc();
            $interconID = $intercon['interconID'];

            $params = array(
                'interconID' => $interconID,
                'contentID' => $contentID,
                'title' => $metadata['title'],
                'url' => $metadata['url']
            );
            
            if (isset($metadata['description']) && $metadata['description']) {
                $params['description'] = $metadata['description'];
            }
            
            unset($metadata['title']);
            unset($metadata['url']);
            unset($metadata['description']);
            
            $internalContentID = $this->db->insertUpdate("content",$params,true);
            
            foreach($metadata as $name=>$value) {
                $this->db->insertUpdate('contentMetadata', array(
                    'internalContentID' => $internalContentID,
                    'name' => $name,
                    'value' => $value
                ));
            }
            
            return true;
        }
        
        return false;                
    }

    function updateCompleteStatus($interconID) { 
        $this->db->query("SELECT COUNT(*) FROM peerFootprints WHERE interconID=$interconID;");
        if ($this->db->errno()) echo $this->db->error () . PHP_EOL;
        elseif ($this->db->num_rows($qr) && ($cnt=$this->db->result()) >0 ) {
            $qr = $this->db->query("SELECT COUNT(*) FROM peerCapabilities WHERE interconID=$interconID;");       
            if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
            elseif (  $this->db->num_rows($qr) && ($cnt=$this->db->result()) > 0  ) {
                $qr = $this->db->query("UPDATE interconnections SET peerStatus='complete' WHERE interconID=$interconID;");       
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
                else {
                    $this->peerSetOfferLocalStatus($interconID, 'complete');
                    return true;
                }
            }
        }
        
        return false;
    }
    
    function doInterconnectionComplete ($cdnID){
        $this->db->select("interconnections",'*',array("WHERE"=>"CDNid='$cdnID'"));        
        $this->onInterconnectionComplete ($this->db->fetch_assoc());
    }
    
    function onInterconnectionComplete ($interconnection) {
    }
    
    function setStaticData($interconID,$name,$value) {                    
        return $this->db->insertUpdate('staticData',array (
                'interconID' => $interconID,
                'Name' => $name,
                'Value' => $value
            ));
    }
    
    function getStaticData($interconID,$name) {
        $this->db->select('staticData',"Value",array('WHERE'=>"Name = '".$this->db->escape_string($name)."' AND interconID=$interconID"));
        return $this->db->fetch_result();
    }
}

?>
