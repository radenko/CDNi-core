<?

if (!defined("MODE_DEBUG")) define("MODE_DEBUG",true);

require_once("DB.php");

class InterconPeer {
    protected $_client=null;
    protected $_localStatus=null;
    protected $_peerStatus=null;
    protected $_interconID=null;
    
    function peerStatus() {
        return $this->_peerStatus;
    }

    function interconID() {
        return $this->_interconID;
    }

    function localStatus() {
        return $this->_localStatus;
    }
    
    function client() {
        return $this -> _client;
    }
    
    function __construct($params) {
        if (!isset($params['peerURL'])) {
            throw new Exception('peerURL param is missing'); //Checks whether peer URL is defined
        }

        $url = $params['peerURL'];        
        $this->_client = new SoapClient( //Create new soap client
                null,
                array('location' => $url, //URL of SOAP Server to send request to
                      'uri'      => $url, //Target namespace of SOAP service
                      'trace' => 1
                )
            );
        $this->setParams($params);
    }
    
    function setParams($params) {
        $params = array_change_key_case($params);
     
        if (isset($params['localStatus'])) $this -> _localStatus = $params['localstatus'];
        if (isset($params['peerStatus'])) $this -> _peerStatus = $params['peerstatus'];
        if (isset($params['interconID'])) $this -> _interconID = $params['interconid'];        
    }
    
    function __call($name, $arguments) {
        if (MODE_DEBUG) {
            echo "Calling '$name' with";
            print_r($arguments);
            echo "\n";
        }
        
        $res = $this->_client->__call($name,$arguments);
        
        if (MODE_DEBUG) {
            echo "Response:";
            var_dump($res);
            echo "\n";
        }
        
        return $res;
    }
}

class Interconnection {
    protected $config;
    protected $db = null;
    protected $clients = array();
    protected $dbConn=null;
    public $iCDN = null;
	public $addpeertimes = 0;
	public $addpeer2times = 0;

    function __construct($config) { //Creates Interconnection
        $this -> config = $config;  //Put all information from $config['CDN'] array of config.php into local $config variable     
        $this -> db = new DB($this->config['i']); //Put information from $config['CDN']['i'] array of config.php into new database DB
    }

    function addPeer($params,$replace=false) { //Checks whether id and url of peer are set and invokes addPeer2
		global $addpeertimes;
		echo "Entering function addPeer, for $addpeertimes time.\n";
		$addpeertimes = $addpeertimes + 1;
		
		$lparams = array_change_key_case($params); //Make index id's lower case
        $id = null;
        $url = null;
        
        if (isset($lparams['cdnid'])) //If run for second time: $config['CDN']['id']='CiscoCDN';
            $id = $lparams['cdnid'];
        if (isset($lparams['id']))
            $id = $lparams['id'];

        if (isset($lparams['peerurl']))
            $url = $lparams['peerurl']; 
        if (isset($lparams['url']))
            $url = $lparams['url'];
        if (isset($lparams['apiurl']))
            $url = $lparams['apiurl']; //If run for first time: $config['CDN']['i']['peers'][0]['APIurl']='http://147.175.15.42/CDNi/SOAP.php';
        
		echo "Aanroepen addPeer2 with (ID, URL, PARAMS):"; var_dump($id,$url,$params); echo "\n";
		
        $this -> addPeer2($id,$url,$params,$replace);
    }
    
    function addPeer2 ($id,$url,$params,$replace=false) {
        global $addpeer2times;
		echo "Entering addPeer2, for $addpeer2times time.\n";
		$addpeer2times = $addpeer2times + 1;
		
		//Checks whether peer variables are set and copies to local variables
		if (isset($params['interconID']))
            $interconID = $params['interconID'];
        if (isset($url) && !is_null($url) && $url && isset($this -> clients[$url])) //Not set when run for first time
			echo "clients dollar url  is set\n";
            $client = $this -> clients[$url]; //When run for second time, InterconPeer object created first time is loaded into $client
        if (isset($id)  && !is_null($id)  && $id  && isset($this -> clients[$id])) //Not set when run for first time
            echo "clients dollar id  is set\n";
			$client = $this -> clients[$id];
        if (isset($interconID)  && !is_null($interconID)  && $interconID  && isset($this -> clients[$interconID])) //Not set when run for first time
            echo "clients dollar interconID is set\n";
			$client = $this -> clients[$interconID];
        
		echo "Value of dollar client"; var_dump($client); echo "\n";
		
		//Invokes constructor of InterconPeer with variables set in addPeer function if $client not set
        if (!isset($client) || !is_object($client) || $replace) {
            $params['peerURL'] = $url; //Add extra entry peerURL with contents of apiurl
            if (isset($id)) //When run for first time is set to NULL
				echo "Adding extra CDNid to array params\n";
                $params['CDNid'] = $id;
            
			echo "Aanroepen the InterconPeer constructor with:"; var_dump($params); echo "\n";
			
            $client = new InterconPeer($params); //Calls constructor of InterconPeer class
        }
        
		//Fill global array clients when variables are not empty
        if (isset($url) && !is_null($url) && $url) //url is set when run for first time
			echo "Newly created InterconPeer object dollarclient is added to array clients on index [dollarurl]\n";
            $this -> clients[$url] = $client; //Newly created InterconPeer object $client is added to array clients on index [$url]
			echo "Contents of whole array clients:"; var_dump($this -> clients); echo"\n";
			echo "Contents of index url of array clients:"; var_dump($this -> clients[$url]); echo"\n";
        if (isset($id)  && !is_null($id)  && $id) //id not set when run for first time
			echo "InterconPeer created in first run is added to array clients on index [dollarid]\n";
            $this -> clients[$id] = $client; //When run for second time, $id is set, so InterconPeer created in first run is added to array clients on index [$id]
			//var_dump($this -> clients[$id]); echo"\n";
        if (isset($params['interconID']))
            $this -> clients[$params['interconID']] = $client;
    }
    
    function addAllPeers() {
	
		echo "Entering the addAllPeers function for:"; var_dump($this); echo "\n";
	
        $this -> db -> select('interconnections');
            
        while ($item = $this -> db -> fetch_assoc()) {
		
				echo "The following peer (item) will be added:"; var_dump($item); echo "\n";
		
                $this -> addPeer($item);
        }
    }
    
    function getPeer($CDNid) {
        if (isset($this->clients[$CDNid])) {
            $this -> $db -> select('interconnections','*',
                    array('WHERE' => "CDNid=$CDNid")
            );
            
            while ($item = $db -> fetch_assoc($result)) {
                $this -> addPeer($item['peerURL'],$item);
            }
        }
        
        return $this->clients[$CDNid];
    }
    
    function setConfig($config) {
        $this -> config = $config;
    }
        
    function peerCall($peerURL,$method,$params) {
        if (defined("MODE_DEBUG")) {echo "Calling $method: ".PHP_EOL; var_dump($params);}
        
        $response = http_post_fields($peerURL."/".$method, $params);
        $responseObj=http_parse_message($response);
        $body=$responseObj->body;
        if (defined("MODE_DEBUG")) echo "Result: $body".PHP_EOL;
        
        $result=array();
        parse_str($body,$result);
                
        return $result;
    }
    
    function peerSetCapabilities($peerURL) {
        echo "Entering peerSetCapabilities function.\n";
		echo "Sending capability to $peerURL".PHP_EOL;
		
		echo "Aanroepen peerSetCapabilities function for:"; var_dump($this -> clients[$peerURL]); echo "\n";
		$this->clients[$peerURL]->setCapabilities(
                $this -> config['id'],
                $this -> config['capabilities']
        );
    }
    
    function peerSetFootprint($peerURL) {
        echo "Sending footprint to $peerURL".PHP_EOL;
        $this->clients[$peerURL]->setFootprint(
                $this -> config['id'],
                implode(",",$this -> config['footprint'])
        );
    }
    
    function peerSetOfferLocalStatus($interconID,$status) {   
		echo "Entering function peerSetOfferLocalStatus \n";
        $this->db->query("SELECT CDNid, peerURL FROM interconnections WHERE interconID='". $this->db->escape_string($interconID) ."';");       
        if ($this->db->errno()) echo $this->db->error ();
        else { //If no error, $interconID is present so continue
            $peer = $this->db->fetch_assoc();
			echo "Calling the addPeer function again with:"; var_dump($peer); echo "\n";
            $this->addPeer($peer);
            
            $peerURL = $peer['peerURL'];
            if (defined('MOD_DEBUG')) echo "Sending local status ($status) to $peerURL".PHP_EOL;

            $this->clients[$peerURL]->setOfferLocalStatus($this -> config['id'],  $status);
        }        
    }

    function peerSetOffer($peerURL) {
        if (defined('MOD_DEBUG')) echo "Sending offer to $peerURL".PHP_EOL;
        echo "Entering peerSetOffer function\n";
		
		echo "Aanroepen setOffer function for:"; var_dump($this -> clients[$peerURL]); echo "\n";
		//Fill array $result with return value of setOffer function of client with index of $peerURL
        $result = $this -> clients[$peerURL] -> setOffer(
            $this -> config['id'], //$config['CDN']['id']='CiscoCDN' from config.php
            $this -> config['APIurl'] //$config['CDN']['APIurl']='http://147.175.15.41/CDNi/SOAP.php' from config.php
        );
        
		echo "This is the result:"; var_dump($result); echo "\n";
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
    
    function processLocalOffers() { 
		
		echo "Entering processLocalOffers\n";
        echo "Checks whether peers are configured for interconnection in config.php\n";
		
		if (isset($this -> config['i']['peers'])) { //Checks whether peers are configured for interconnection in config.php
            
			echo "Peers are set, process each pear \n";
			
			foreach ($this -> config['i']['peers'] as $peer) { //Processes all specified peers in config.php
                echo "Processing: "; var_dump($peer); echo "\n\n";
                
				echo "Checks whether peer is already in table interconnections. (Database created manually) \n";
                $this -> db -> select('interconnections','COUNT(*)',array('WHERE' => "peerURL='". $this->db->escape_string($peer['APIurl']). "'")); //Checks whether peer is already in table interconnections. (Database created manually)

                if ($this -> db -> errno()) echo $this -> db -> error (); //If already there, value = 1, so will be echoed.
                elseif ($this -> db -> result() <= 0) /*If not there, result is 0, so peer will be added. */ {
					echo "Peer not in database, so call the addPeer function with:"; var_dump($peer); echo "\n\n";
                    $this -> addPeer($peer); //Call addPeer with peer from $config['CDN']['i']['peers'][0] from config.php to create new SOAP client
                    
					echo "Peer is added to database, so call the peerSetOffer function with:"; var_dump($peer['APIurl']); echo "\n\n";
					$peerObj = $this -> peerSetOffer ($peer['APIurl']); //Call peerSetOffer with $config['CDN']['i']['peers'][0]['APIurl']='http://147.175.15.42/CDNi/SOAP.php';
                    
					//If peerSetOffer returned other than false, use statement below. False is returned when failed.
                    if (isset($peerObj) && !is_null($peerObj) && $peerObj !== false) {
                        echo "peerSetOffer function succeeded with result:"; var_dump($peerObj); echo "\n\n";
						echo "variable peer contains:"; var_dump($peer); echo "\n\n";
						
						$peer['id'] = $peerObj['CDNid'];
						
						echo "As peerSetOffer succeeded, call the addPeer function again, with:"; var_dump($peer); echo "\n\n";
                        $this->addPeer($peer); //Call addPeer with peer from $config['CDN']['i']['peers'][0] from config.php and also $config['CDN']['id']='CiscoCDN';

                        echo "As peerSetOffer succeeded, call db insertIgnore with: \n\n"; var_dump($peer['id'],$peer['APIurl']); echo "\n\n";
						$this->db->insertIgnore("interconnections", //Insert same peer again in database, ignore when already there
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
        
		echo "Select all rows in table interconnections with status Offer so that they can be processed\n";
		//Select all rows in table interconnections with status "Offer" so that they can be processed.
        $this -> db -> select('interconnections', array('interconID', 'CDNid', 'peerURL', 'localStatus', 'peerStatus'), array('WHERE' => "localStatus='offer'"));

        if ($this -> db -> errno()) echo $this->db->error ();
        else {
			echo "Processing all peers with state Offer\n";
            while ( $peer = $this -> db -> fetch_assoc() ) {
			echo "\n\n\n\n\nAanroepen the addPeer function again, with:"; var_dump($peer); echo "\n\n\n\n\n";
                $this -> addPeer($peer);    
			echo "\n\n\n\n\nAanroepen the peerSetCapabilities function with:"; var_dump($peer['peerURL']); echo "\n\n\n\n\n";
                $this -> peerSetCapabilities($peer['peerURL']);
			echo "\n\n\n\n\nAanroepen the peerSetFootprint function with:"; var_dump($peer['peerURL']); echo "\n\n\n\n\n";
                $this -> peerSetFootprint   ($peer['peerURL']);
            }
        }
    }

    function getAllInterconnections() {
        $intercons=array(); 
        
		echo "Entering the getAllInterconnections function \n";
		
		$this -> db -> select(' interconnections','*',array('WHERE' => "peerStatus='complete'"));
        while ($intercon = $this->db->fetch_assoc()) {
            array_push($intercons,$intercon);
        }
		
		echo "The array intercons has been filled with:\n"; var_dump($intercons); echo "\n";
		
        return $intercons;
    }
    
    function processContentForTransfer() {
	
		echo "Entering the processContentForTransfer function \n";
	
        $this -> addAllPeers();
        $intercons=$this->getAllInterconnections();
        
        $this -> db -> select('content');
        while($content = $this->db->fetch_assoc()) {
		
			echo "Entering the while statement of processContentForTransfer for dollarcontent:"; var_dump($content); echo "\n";
		
            foreach ($intercons as $intercon)
			
				echo "Calling the distributeContent function for:"; var_dump($content,$intercon); echo "\n";
			
                $this -> distributeContent($content,$intercon);  
        }
    }

    function processLocalContentForTransfer() {
	
		echo "Entering the processLocalContentForTransfer function \n";
	
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

    function cron () { //Called via the cron.php file
        header('Content-type: text/plain');
		echo "Function cron is called! \n";
		echo "Starting with processLocalOffers\n";
        $this -> processLocalOffers(); //Runs local function
        echo "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\nStarting processContentForTransfer\n";
		$this -> processContentForTransfer();
		echo "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\nStarting processLocalContentForTransfer\n";
        $this -> processLocalContentForTransfer();
    }
	
	function reset() {
	$this -> db -> flushdatabase();
	}
    
    function processAPI($methods,$data) {
        $result=call_user_func_array(array($this, $methods[0]), $data);
        
        if (is_array($result) && count($result)) echo http_build_query($result);
    }
    
    function setCapabilities ($CDNid,$capabilities) {
        //echo "Entering the setCapabilities function for $CDNid. \n";
		$sql="SELECT interconID FROM interconnections WHERE CDNid='". $this->db->escape_string($CDNid) ."';";
        $this-> db ->query ($sql);

        if ($this-> db ->errno()) echo $this -> db->error () . PHP_EOL; //If query returns error, interconID does not exist, return false
        else {
            $interconID = $this -> db -> result($qr,0); //$qr is result of running the query
            if (!is_numeric($interconID)) echo "interconID is not numeric".PHP_EOL; //Check whether interconID is numeric
            else {       
                $qr = $this->db->query("DELETE FROM peerCapabilities WHERE interconID=$interconID;"); //Delete capabilities currently stored in database       
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL; //If failed, echo error message       
        
                $qr = $this->db->query("INSERT INTO peerCapabilities SET interconID=$interconID, name='', value='".  $this->db->escape_string($capabilities)  ."';"); //Add new capabilities in database      
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL; //If failed, echo error message
                else {
					echo "Capabilities inserted into database for $interconID, calling function updateCompleteStatus. \n";
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

    function setOffer($CDNid,$peerURL) { //Add peer to database, function is called from external peer
        file_put_contents("debug",var_export($CDNid,true).var_export($peerURL,true));
        
		//echo "Entering setOffer function with:"; var_dump($CDNid, $peerURL); echo "\n";
        
        $this->db->insertUpdate("interconnections", //Insert into table interconnections
                array(
                    'CDNid' => $CDNid,
                    'peerURL' => $peerURL,
                    'peerStatus' => 'offer'
                )
        );
        if ($this->db->errno()) echo $this->db->error() . PHP_EOL; //If insert returned error, print error and return false
        else {
			//echo "Entering else statement:"; var_dump($config['id']); echo "\n";
            return array("CDNid" => $this -> config['id']); //If not failed return value of $config['CDN']['id'];
        }
        
        return false;
    }
    
    function setOfferLocalStatus($CDNid,$status) {        
        $this->db->query("UPDATE interconnections SET localStatus='".  $this->db->escape_string($status). "' WHERE CDNid = '". mysql_escape_string($CDNid) . "';", $this -> dbConn);       
        if ($status == 'complete')
            $this->doInterconnectionComplete ($CDNid);

        if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
        else return true;
        
        return false;        
    }

    function distributeContent($intercon, $content) {
	
		echo "Entering the distributeContent function \n";
	
        if (!is_array($content)) { //Check if $content is empty, if empty fill
            $this->db->select('content','*',array('WHERE' => "contentID=$contentID"));
            if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
            $content = $this->db->fetch_assoc();
        }
        
        if (!is_array($intercon)) { //Check if $intercon is empyt, if empty fill
            $this->db->select("interconnections","*",array('WHERE'=>"interconID=$interconID"));
            if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
            $intercon = $this->db->fetch_assoc();
        }
        
		//Set variables for content check
        $internalContentID = isset($content['internalContentID'])?$content['internalContentID']:0; //If set, $internalContentID = $content['internalContentID'], else 0
        $contentID = $content['contentID'];
        $interconID = $intercon['interconID'];

        if ($internalContentID) { //Not 0, enter if statement
            $metadata=array_intersect_key($content, array('title'=>1,'description'=>1,'url'=>1)); //Checks which keys/indexes of $content are also present in the array and returns those values
            $this->db->select("contentMetadata","*",array("WHERE"=>"internalContentID=$internalContentID")); //Select all content metadat for certain internalContentID
            while ($row = $this->db->fetch_assoc()) { //Retrieve rows out of database
                $metadata[$row['name']]=$row['value']; //Put content of $row[vlue] into array $metadata[$row[name]]
            }
        } else {
            $metadata = array_diff_key($content,array('contentID'=>1)); //Checks which keys/indexes of $content are not present in the array and returns those values
        }
        
        //var_dump($this->config['id'],$contentID,$metadata);
        
		echo "Calling the setContentBasicMetadata function for:"; var_dump($this->config['id']); echo "\n";
        $this->clients[$interconID]->setContentBasicMetadata($this->config['id'],$contentID,$metadata);
    }

    
    function setContentBasicMetadata($CDNid,$contentID,$metadata) {
        
		echo "Entering the setContentBasicMetadata function for:"; var_dump($CDNid); echo "\n";
		
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
            
            $internalContentID = $this->db->insertUpdate("content",$params,true); //Table, values, returnID
            
			echo "The foreach function of setContentBasicMetadata. \n";
			
            foreach($metadata as $name=>$value) {

				echo "Contents of metadata:"; var_dump($metadata); echo "\n";
            
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
            elseif (  $this->db->num_rows($qr) && ($cnt=$this->db->result()) > 0  ) { //If amount of returned rows > 0, capabilities/footprint available so return complete.
                $qr = $this->db->query("UPDATE interconnections SET peerStatus='complete' WHERE interconID=$interconID;");       
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
                else {
					echo "Footprint and/or capabilities stored, calling peerSetOfferLocalStatus with:\n"; var_dump($interconID); echo "\n";
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
