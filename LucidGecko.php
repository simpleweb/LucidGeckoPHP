<?php
require_once 'Exceptions.php';

/**
 * LucidGecko Platform API
 * -----------------------
 * @author Tom Holder 
 */
class LucidGecko {

	const API_SERVER = "http://v1.simplewebmanagement.com/api/";
	//const API_SERVER = "http://swmstage.simpleweb-online.com/api/";
	//const API_SERVER = "http://swm/api/";
	
	private static $_instance;
	private $_appKey;
	private $_appSecret;
	public $installID;
	public $installSecret;
	
	public $parentCompany;
	public $company;
	public $website;
	public $user;
	public $postbackUrl;
	
	public function  __construct($appKey, $appSecret, $installID = null, $installSecret = null) {
		
		if(!isset($appKey)) throw new exception('No Application Key specified.');
		if(!isset($appSecret)) throw new exception('No Application Secret specified.');
	
		//Set the APP and API Keys
		$this->_appKey = $appKey;
		$this->_appSecret = $appSecret;
		$this->installID = $installID;
		$this->installSecret = $installSecret;

		$this->parentCompany['Key'] = '';
		$this->company['Key'] = '';		
		$this->website['Key'] = '';
		$this->website['Domain'] = '';	
		$this->user['ID'] = '';
		$this->user['Secret'] = '';
		$this->user['Username'] = '';
		$this->user['Forename'] = '';
		$this->user['Surname'] = '';
		$this->user['Name'] = '';
		$this->user['IsReseller'] = false;
		$this->user['IsCompanyAdmin'] = false;
					
		//If there was a post signature
		if(isset($_GET['LG_Signature'])) {
			
			$postSig = $_GET['LG_Signature'];
			unset($_GET['LG_Signature']);
			
			//Compare the API Key with the Post data to the Post sig to ensure the integrity of the posted data. Prevents spoofing.
			if(md5($this->_appSecret.implode('', $_GET)) != $postSig) {
				throw new LucidGeckoSecurityException('The integrity of the post data is bad. Please check your API , int.');	
			}
			
			if(isset($_GET['LG_InstallID']))	{
				$this->installID = $_GET['LG_InstallID'];
			}

			if(isset($_GET['LG_InstallSecret']))	{
				$this->installSecret = $_GET['LG_InstallSecret'];
			}
						
			//Set context information from Post data.
			if(isset($_GET['LG_ParentCompanyKey'])) {
				$this->parentCompany['Key'] = $_GET['LG_ParentCompanyKey'];
			}
			
			if(isset($_GET['LG_CompanyKey'])) {
				$this->company['Key'] = $_GET['LG_CompanyKey'];
			}

			if(isset($_GET['LG_WebsiteKey']))	{
				$this->website['Key'] = $_GET['LG_WebsiteKey'];
				$this->website['Domain'] = $_GET['LG_WebsiteDomain'];
			}	
			
			//Set user details/
			$this->user['ID'] = $_GET['LG_UserID'];
			$this->user['Secret'] = $_GET['LG_UserSecret'];
			$this->user['Username'] = $_GET['LG_Username'];
			$this->user['Forename'] = $_GET['LG_UserForename'];
			$this->user['Surname'] = $_GET['LG_UserSurname'];
			$this->user['Name'] = $this->getUserFullName($this->user['Forename'], $this->user['Surname']);
			$this->user['IsReseller'] = $_GET['LG_UserIsReseller'];
			$this->user['IsCompanyAdmin'] = $_GET['LG_UserIsCompanyAdmin'];
			
			$this->postbackUrl = $_GET['LG_PostbackUrl'];
		}
	}
	
	/**
	 * Returns instance of the LucidGecko object. This class uses a singleton pattern.
	 */
	public static function getInstance($appKey = null, $appSecret = null, $installID = null, $installSecret = null) {
		
		if(self::$_instance === null) self::$_instance = new self($appKey, $appSecret, $installID, $installSecret);
		
		return self::$_instance;
	}
	
	/**
	 * Posts an activity message.
	 */
	public function postActivity($messageKey, $extendedData = null) {
		
		$params['pluginMessageKey'] = $messageKey;
		$params['extendedData'] = $extendedData;
		
		$result = $this->postRequest('postActivity',$params);
		
		return $this->getRequestStatus($result);
	}
	
	/**
	 * Generates a one time only authentication key for the user.
	 */
	public function generateAuthKey() {
		$result = $this->postRequest('generateAuthKey');
		return $result['auth_key'];
	}
	
	/**
	 * Validates an authentication.
	 */
	public function validateAuthKey($authKey) {
		 
		$params['authKey'] = $authKey;
		
		$result = $this->postRequest('validateAuthKey', $params);
		
		return $result;
	}
	
	/**
	 * Gets companies belonging to this reseller.
	 */
	public function getCompanies() {
		
		$result = $this->postRequest('getCompanies');
		
		return $result;
				
	}
	
	/***
	 * Finds one or more companies by name.
	 * @return 
	 * @param object $searchString What to look for e.g. 'a' will return any company starting with a.
	 * @param object $fields The fields to return in comma delimited list. Illegal fields will be automatically stripped.
	 * @param object $startIndex The first record to return 0 will return the first record.
	 * @param object $limit How many to return.
	 */
	public function findCompaniesByName($searchString, $fields = '*', $startIndex = 0, $limit = 999) {
		
		$params['searchString'] = $searchString;
		$params['fields'] = $fields;
		$params['startIndex'] = $startIndex;
		$params['limit'] = $limit;
		
		return $this->postRequest('findCompaniesByName', $params);
		
	}
	
	/**
	 * Returns asset folders for a given path.
	 */
	public function getAssetFolders($parentFolder) {
		$params['parentFolder'] = $parentFolder;
		return $this->postRequest('getAssetFolders', $params);
	}

	/**
	 * Returns the assets for a folder.
	 */
	public function getAssetsForFolder($folder) {
		$params['folder'] = $folder;
		return $this->postRequest('getAssetsForFolder', $params);
	}
	
	/**
	 * Puts an asset in to SWIM.
	 * @param $folder string The folder to put the asset in to.
	 * @param $fileName string The name of the file for the asset.
	 * @param $file string Path to the asset to send.
	 */
	public function putAsset($folder, $fileName, $file) {

		$params['folder'] = $folder;
		$params['fileName'] = $fileName;
		$params['file'] = '@' . $file;
		
		$result = $this->postRequest('putAsset',$params);
		return $this->getRequestStatus($result);
	}
	
	/**
	 * Creates an asset folder
	 */
	 public function addAssetFolder($parentFolder, $folder) {
	 	
	 	$params['parentFolder'] = $parentFolder;
	 	$params['folder'] = $folder;
		
		$result = $this->postRequest('addAssetFolder',$params);
		return $this->getRequestStatus($result);
	 }
		 
	 /**
	  * Gets all asset details for a given folder/file.
	  */
	 public function getAssetDetails($folder, $file) {
	 	
	 	$params['folder'] = $folder;
	 	$params['file'] = $file;
		
		return $this->postRequest('getAssetDetails',$params);
	 }
	 
	/**
	 * Stores some data remotely against the context. $data can be an array of key value pairs.
	 */
	public function putData($dataGroup, $dataKey, $data) {
		
		$params['dataGroup'] = $dataGroup;
		$params['dataKey'] = $dataKey;
		$params['data'] = $data;
		
		$result = $this->postRequest('putData',$params);
		
		return $this->getRequestStatus($result);
		
	}
	
	/**
	 * Retrieves previously stored data by the data group.
	 */
	public function getDataByDataGroup($dataGroup) {

		$params['dataGroup'] = $dataGroup;
		
		$results = $this->postRequest('getDataByDataGroup',$params);

		if(is_array($results) && array_key_exists('results', $results)) {
			return $results['results'];
		} else {
			return false;
		}
	}
	
	/**
	 * Returns a random nonce string for web service calls. Each call must use a unique nonce.
	 */
	private function getNonce() {
		
		if (function_exists('com_create_guid')) { 
            return com_create_guid(); 
        } else {
        	//TODO - make this more unique.
        	return uniqid();
        }
        
	}
	
	/**
	 * Returns a security token, it basically consists of a hashed app secret and nonce.
	 */
	private function getSecurityToken($nonce) {
		return md5($this->_appSecret.$nonce);
	}
	
	/**
	 * Post request to API server.
	 */
	private function postRequest($method, $params = null) {
		
		if(!isset($params)) {
			$params = array();
		}
		
		$nonce = $this->getNonce();
		$securityToken = $this->getSecurityToken($nonce);
		
		$params['method'] = $method;		
		$params['pluginKey'] = $this->_appKey;
		$params['nonce'] = $nonce;
		$params['securityToken'] = $securityToken;
		
		$this->addContextParams($params);
		
		//Any items being posted that are arrays need to be converted in to the correct format originalKey[arraykey] = value
		foreach($params as $key => $value) {
			if(is_array($value)) {
				
				foreach($value as $subKey => $subValue) {
					$params[$key.'['.$subKey.']'] = $subValue;
				}
				
				unset($params[$key]);
			}
		}

		//$parmsQs = http_build_query($params);
		
		//print_r($params);
		//die();
		$rawResult = ''; //Will hold our result.
		
		if (function_exists('curl_init')) {
			
			// Use CURL if installed...
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, self::API_SERVER);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			
			//curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
			//We have a problem here! The above causes file uploads to fail, the below doesn't work for activity.
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Lucid Gecko API PHP5 Client 1.0 (curl) ' . phpversion());			
				
			//die(http_build_query($params));
			//http_build_query($params);
			$rawResult = curl_exec($ch);
		 
		} else {
		
			echo 'This version of LucidGecko does not yet work with FOPEN. Please make sure you have CURL available on the server.';
			die();
			
		  // Non-CURL based version...
		  //TODO - Test and implement this version.
		  $context =
		    array('http' =>
		          array('method' => 'POST',
		                'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
		                            'User-Agent: Lucid Gecko API PHP5 Client 1.0 (non-curl) '.phpversion()."\r\n".
		                            'Content-length: ' . strlen($parmsQs),
		                'content' => $parmsQs));
		  $contextid=stream_context_create($context);
		  $sock=fopen(self::API_SERVER, 'r', false, $contextid);
		  if ($sock) {
		    $result='';
		    while (!feof($sock)) {
		      $rawResult.=fgets($sock, 4096);
		    }
		    fclose($sock);
		  }
		  
		}

		try {
			$xmlOutput = simplexml_load_string(trim($rawResult));
			$result = $this->getRequestResult($method, $xmlOutput);
		} catch(Exception $e) {
			//Need to do something better with this error in future.
			echo '<pre>'.$rawResult.'</pre>';
		}
		
		
		if(empty($result)) {
			//Need to do something better with this error in future.;
			//echo '<pre>'.$rawResult.'</pre>';
			throw new Exception('An unknown error occurred. No response returned.',100);
		} else {

			if($result['error_code'] == 0) {
				return $result;			
			} else {
				if(array_key_exists('error_message', $result)) {
					throw new Exception($result['error_message'],$result['error_code']);
				} else {
					throw new Exception(print_r($result, true),101);
				}
			}	
			
		}

  	}

	/**
	 * Takes the context details and adds in parameters for an API request.
	 */
	private function addContextParams(&$params) {
		
		if((isset($this->installID) && isset($this->installSecret)) && (!empty($this->installID) && !empty($this->installSecret))) {
			$params['installID'] = $this->installID;
			$params['installSecret'] = $this->installSecret;
		} else {
			throw new LucidGeckoException('Missing install context information for the application. It is not possible to call an API method without an InstallID and InstallSecret specified.');
		}
		
		//User information does not always have to be passed.
		if(!array_key_exists('userID',$params) || !array_key_exists('userSecret',$params)) {
			if(isset($this->user)) {
				$params['userID'] = $this->user['ID'];
				$params['userSecret'] = $this->user['Secret'];
			}
		}

	}
	
 	/**
 	 * Takes simpleXML response, converts it to php array, extracts just the method related part.
 	 */
 	 private function getRequestResult($method, $sxml) {
 	 	$requestResult = $this->convertSimpleXmlToArray($sxml);
 	 	
 	 	if(isset($requestResult) && is_array($requestResult) && array_key_exists($method, $requestResult)) {
 	 		return $requestResult[$method];
 	 	} else {
 	 		return false;
 	 	}
 	 }
 	
  	/**
  	 * Determine if the API request was ok.
  	 */
 	private function getRequestStatus($requestResult) {
 		
 		if(isset($requestResult) && is_array($requestResult) && array_key_exists('status', $requestResult)) {
 			return (bool) $requestResult['status'];
 		} else {
 			return false;
 		}			
 	}
 	 	
 	/**
 	 * Loops through SimpleXML data and converts it to PHP array.
 	 */
	private function convertSimpleXmlToArray($sxml) {
    	$arr = array();
    	if ($sxml) {
			foreach ($sxml as $k => $v) {
				$arr[$k] = $this->convertSimpleXmlToArray($v);
      		}
		}
		if (sizeof($arr) > 0) {
		  return $arr;
		} else {
		  return (string)$sxml;
		}
	}
  
  	/**
	 * Returns a users full name if they have a surname specified.
	 */
	private function getUserFullName($forename, $surname) {
		
		$userFullName = $forename;
		if(!empty($surname)) {
			$userFullName .= ' ' . $surname;
		}
		
		return $userFullName;
	}

}
?>