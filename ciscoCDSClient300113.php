<?php
    class ciscoCDSClient {
        protected $config;
        
        private function makeRequest($taskAPI,$action,$params) {
            $params['action']=$action;
            
            $urlString = "https://{$this -> config ['host']}:{$this -> config ['port']}/servlet/$taskAPI?"  .  http_build_query($params);// + "&param=" + channelId_;

            //echo $urlString.'<br/>';
            $response = http_get(
                    $urlString,
                    array (
                            'httpauth' => $this -> config ['userName'] . ':' . $this -> config ['password'],
                            'httpauthtype' => HTTP_AUTH_BASIC
                    ));
            $responseObj=http_parse_message($response);// var_dump($responseObj);

            if ($responseObj -> responseCode != 200 ) return false;

            $body=$responseObj->body;
            
            file_put_contents(__DIR__."/$action.resp.xml",$body);

            $xml=new SimpleXMLElement($body);
            
            return $xml;
        }

        public function createDeliveryService($name, $contentOriginID, $params = array()) {
         /*
            [&weakCert=<true | false>][&skipEncrypt= <true | false>][&priority=<high | medium | low>]
            [&failoverIntvl=<20 | 30 | 40 | 50 | 60 | 70 | 80 | 90 | 100 | 110 | 120>][&never=<true | false>]
            [&qos=<system|0-63>][&desc=<description>][&live=<true | false>]) {
         */
            $params = array ();
            $params ['deliveryService'] = $name;
            $params ['contentOrigin'] = $contentOriginID;
            $params ['quota'] = 20000 ;
            
            //$resXML = new SimpleXMLElement(file_get_contents(__DIR__.'/createDeliveryService.resp.xml'));
            $resXML = $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'createDeliveryService', $params);
            $attribs = $resXML->record->attributes();
            
            $res=array();
            foreach ($attribs as $a => $b) {
                $res[$a]=(string)$b;
            } 
            
            return $res;
        }
		
		public function deleteDeliveryService($deliveryServiceID) {
         /*
            https://<cdsmIpAddress>:8443/servlet/com.cisco.unicorn.ui.ChannelApiServlet?action=deleteDeliveryServices
			&deliveryService=all | <deliveryService_ID>, <deliveryService_ID>, ... 
         */
            $params = array ();
            $params ['deliveryService'] = $deliveryServiceID;
                        
            $resXML = $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'deleteDeliveryServices', $params);
            $attribs = $resXML->record->attributes();
            
            $res=array();
            foreach ($attribs as $a => $b) {
                $res[$a]=(string)$b;
            } 
            
            return $res;
        }
		
		public function assignSEtoDS($name, $deliveryServiceID, $params = array()) {
         /*
            [&weakCert=<true | false>][&skipEncrypt= <true | false>][&priority=<high | medium | low>]
            [&failoverIntvl=<20 | 30 | 40 | 50 | 60 | 70 | 80 | 90 | 100 | 110 | 120>][&never=<true | false>]
            [&qos=<system|0-63>][&desc=<description>][&live=<true | false>]) {
			
			action=assignSEs&deliveryService=Channel_335&contentAcquirer=CeConfig_189&se=CeConfig_189
         */
            $params = array ();
            $params ['deliveryService'] = $deliveryServiceID;
			$params ['contentAcquirer'] = 'CeConfig_189';
			$params ['se'] = 'CeConfig_189';
           
            
            //$resXML = new SimpleXMLElement(file_get_contents(__DIR__.'/createDeliveryService.resp.xml'));
            $resXML = $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'assignSEs', $params);
            $attribs = $resXML->record->attributes();
            
            $res=array();
            foreach ($attribs as $a => $b) {
                $res[$a]=(string)$b;
            } 
            
            return $res;
        }

		public function unassignSEtoDS($deliveryServiceID, $params = array()) {
         /*
            https://<cdsmIpAddress>:8443/servlet/com.cisco.unicorn.ui.ChannelApiServlet?action=unassignSEs
			&deliveryService=<deliveryService_ID>[&se=all | <SE_ID>, <SE_ID>, ...][&cluster=all | <Cluster_ID>, 
			<Cluster_ID>, ...] 
         */
            $params = array ();
            $params ['deliveryService'] = $deliveryServiceID;
			$params ['se'] = 'CeConfig_189';
                   
            $resXML = $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'unassignSEs', $params);
            $attribs = $resXML->record->attributes();
            
            $res=array();
            foreach ($attribs as $a => $b) {
                $res[$a]=(string)$b;
            } 
            
            return $res;
        }
				
		public function listServiceEngines() {
        /* 
            https://<cdsmIpAddress>:8443/servlet/com.cisco.unicorn.ui.ListApiServlet?action=getSEs&param=
			all | [name=]<se_name>, <se_name>, ... | deliveryService=<deliveryService_ID> | location=<location_ID> 
		
			com.cisco.unicorn.ui.ListApiServlet?action=getSEs&param=all
        */ 
            $params = array ();
            $params ['param'] = 'all';           
            
            $resXML = $this->makeRequest('com.cisco.unicorn.ui.ListApiServlet', 'getSEs', $params);
            $attribs = $resXML->record->attributes();
            
            $res=array();
            foreach ($attribs as $a => $b) {
                $res[$a]=(string)$b;
            } 
            
            return $res;
        }
				
        public function createContentOrigin($name, $origin_server_IP_or_domain, $fqdn, $params = array()) {
		/*        
        https://<cdsmIpAddress>:8443/servlet/com.cisco.unicorn.ui.ChannelApiServlet?action=
		createContentOrigin&name=<contentorigin_name>&origin=<origin_server_IP_or_domain>
		&fqdn=<fqdn>[&contentBasedRouting=<true | false>][&nasFile=<FileInfo_id | n
		*/          
            $params ['name'] = $name;
            $params ['origin'] = $origin_server_IP_or_domain;
            $params ['fqdn'] = $fqdn;
           
            $resXML = $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'createContentOrigin', $params);
            //$resXML = new SimpleXMLElement(file_get_contents(__DIR__.'/createContentOrigin.resp.xml'));
            //var_dump($resXML);
            $attribs = $resXML->record->attributes();
            
            $res=array();
            foreach ($attribs as $a => $b) {
                $res[$a]=(string)$b;
            } 
            
            return $res;
        }
		
        public function deleteContentOrigin($contentOriginID) {
		/*        
        https://<cdsmIpAddress>:8443/servlet/com.cisco.unicorn.ui.ChannelApiServlet?action=
		deleteContentOrigins&contentOrigin=all | <contentorigin_ID>, <contentorigin_ID>, ... 
		*/          
            $params ['contentOrigin'] = $contentOriginID;
			           
            $resXML = $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'deleteContentOrigins', $params);
            $attribs = $resXML->record->attributes();
            
            $res=array();
            foreach ($attribs as $a => $b) {
                $res[$a]=(string)$b;
            } 
            
            return $res;
        }
        
        public function addContent($intercon,$content){
            
        }
        
        public function addManifest($deliveryServiceId,$manifestURL , $quota, $ttl, $params = array()) {
            /*
            deliveryService=<deliveryService_ID>&manifest=<manifest_URL>&quota=<quota>&ttl=<ttl>
            [&user=<user_name>][&password=<password>][&userDomainName=<user_domain_name>]
            [&notBasicAuth=<true|false>][&noProxy=<true | false>][&proxyIpHostname=<proxy_ip_hostname>]
            [&proxyPort=<proxy_port>][&proxyUser=<proxy_user>][&proxyPassword=<proxy_password>]
            [&proxyNtlmUserDomainName=<proxy_ntlm_user_domain_name>][&proxyNotBasicAuth=
            <true|false>]
            */
            
            $params['deliveryService'] = $deliveryServiceId;
            $params['manifest'] = $manifestURL;
            $params['quota'] = $quota;
            $params['ttl'] = $ttl;

            $resXML = $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'addManifest', $params);
            
            return $resXML;
        }        
        
        public function __construct ($config) {            
            $this -> config = $config;
        }
        
        
    }

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
