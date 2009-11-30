<?php

/**
 * LucidGecko3 Platform API - now with added Workspace support!
 * TODO - depracate website support
 * -----------------------
 * @author Tom Holder & Luke Marsden
 */
class LucidGecko3 {
	
	const API_PATH = 'api/2.0/'; //Added due to issue 216
	
	private $apiServer;

	private static $_instance;
	private $_appKey;
	private $_appSecret;
	public $installID;
	public $installSecret;
	
	public $parentCompany;
	public $company;
	public $locationCompany;
	public $website;
	public $workspace;
	public $user;
	public $postbackUrl;
	
	//If set to true, friendly error messages will be output including the API call details.
	public $friendlyErrors = false;

	//If set each API call will be output to the browser. Should only ever be set in development.
	public $outputCalls = false;
	
	//Holds details of the number of rows returned.
	public $recordCount = array();
			
	public function  __construct($appKey, $appSecret, $installID = null, $installSecret = null, $apiServer = null) {
		
		if(!isset($appKey)) throw new LucidGeckoException('No Application Key specified.');
		if(!isset($appSecret)) throw new LucidGeckoException('No Application Secret specified.');
	
		//Set the APP and API Keys
		$this->_appKey = $appKey;
		$this->_appSecret = $appSecret;
		$this->installID = $installID;
		$this->installSecret = $installSecret;

		$this->parentCompany['Key'] = '';
		$this->parentCompany['Name'] = '';
		$this->company['Key'] = '';
		$this->company['Name'] = '';
		$this->website['Key'] = '';
		$this->website['Domain'] = '';

		$this->workspace['ID'] = '';
		$this->workspace['Name'] = '';

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

			if(isset($_GET['LG_ParentCompanyName'])) {
				$this->parentCompany['Name'] = $_GET['LG_ParentCompanyName'];
			}
			
			if(isset($_GET['LG_CompanyKey'])) {
				$this->company['Key'] = $_GET['LG_CompanyKey'];
			}
			
			if(isset($_GET['LG_CompanyName'])) {
				$this->company['Name'] = $_GET['LG_CompanyName'];
			}
			
			//Set the location company.
			if(!empty($this->company['Key'])) {
				$this->locationCompany = $this->company;
			} else {
				$this->locationCompany = $this->parentCompany;
			}
			
			if(isset($_GET['LG_WorkspaceID']))	{
				$this->workspace['ID'] = $_GET['LG_WorkspaceID'];
				$this->workspace['Name'] = $_GET['LG_WorkspaceName'];
			}	
			
			//Set user details/
			$this->user['ID'] = $_GET['LG_UserID'];
			$this->user['Secret'] = $_GET['LG_UserSecret'];
			
			if(isset($_GET['LG_Username'])) {
				$this->user['Username'] = $_GET['LG_Username'];
			}
			
			$this->user['Forename'] = $_GET['LG_UserForename'];
			$this->user['Surname'] = $_GET['LG_UserSurname'];
			$this->user['Name'] = $this->getUserFullName($this->user['Forename'], $this->user['Surname']);
			$this->user['IsReseller'] = (bool) $_GET['LG_UserIsReseller'];
			$this->user['IsCompanyAdmin'] = (bool) $_GET['LG_UserIsCompanyAdmin'];
			
			$this->postbackUrl = $_GET['LG_PostbackUrl'];
			
			//Set the API server URL. Without this, LG can't communicate with SWM.
			$this->apiServer = $_GET['LG_SwmUrl'] . self::API_PATH;
			
		} else {
			
			//We'll hit this when LG is used in an app that isn't being directly called by SWM, hence, an InstallID, InstallSecret and API Server are required.
			if(!isset($installID)) throw new LucidGeckoException('No InstallID specified.');
			if(!isset($installSecret)) throw new LucidGeckoException('No InstallSecret specified.');
			if(!isset($apiServer)) throw new LucidGeckoException('No ApiServer specified.');
		
			$this->apiServer = $apiServer . self::API_PATH;
		}
	}
	
	/**
	 * Returns instance of the LucidGecko object. This class uses a singleton pattern.
	 */
	public static function getInstance($appKey = null, $appSecret = null, $installID = null, $installSecret = null, $apiServer = null) {
		
		if(self::$_instance === null) self::$_instance = new self($appKey, $appSecret, $installID, $installSecret, $apiServer);
		
		return self::$_instance;
	}
	
	/**
	 * Posts an activity message.
	 */
	public function postActivity($messageKey, $extendedData = null) {
		
		$params['messageKey'] = $messageKey;
		$params['extendedData'] = $extendedData;
		
		$result = $this->postRequest('activity/add/',$params);
		
		return $this->getBooleanStatus($result);

	}

	/**
	 * Emails a collection of users.
	 * @return bool
	 * @param object $emailKey The emailkey of the template to email.
	 * @param object $userIDs IDs of users to email, passed in as an array.
	 * @param object $extendedData[optional] Should be key/value array of data to put in to the template.
	 * @param pbject $callback[optional] Page to callback when user replies to email. Leave blank and email will be sent from no-reply@simplewebmanagement.com
	 * @param object $excludeCaller[optional] By default the email app will not email the user calling the method. Pass false for this to force it to email the caller as well.
	 */
	public function emailUsers($emailKey, $userIDs, $extendedEmailData = null, $callback = null, $extendedCallbackData = null, $excludeCaller = true) {
		
		$params['emailKey'] = $emailKey;
		$params['userIDs'] = $userIDs;
		$params['extendedEmailData'] = $extendedEmailData;
		$params['callback'] = $callback;
		$params['extendedCallbackData'] = $extendedCallbackData;
		$params['excludeCaller'] = $excludeCaller;
		
		$result = $this->postRequest('email/email-users/',$params);
		
		return $this->getBooleanStatus($result);

	}

	/**
	 * Emails a collection of users.
	 * @return bool
	 * @param object $emailKey The emailkey of the template to email.
	 * @param object $companyKey The company key to email. Must belong to the calling parent company. If set to null, the parent company will be emailed.
	 * @param object $extendedData[optional] Should be key/value array of data to put in to the template.
	 * @param pbject $callback[optional] Page to callback when user replies to email. Leave blank and email will be sent from no-reply@simplewebmanagement.com
	 * @param object $excludeCaller[optional] By default the email app will not email the user calling the method. Pass false for this to force it to email the caller as well.
	 */
	public function emailCompany($emailKey, $companyKey = null, $extendedEmailData = null, $callback = null, $extendedCallbackData = null, $excludeCaller = true) {
		
		$params['emailKey'] = $emailKey;
		$params['companyKey'] = $companyKey;
		$params['extendedEmailData'] = $extendedEmailData;
		$params['callback'] = $callback;
		$params['extendedCallbackData'] = $extendedCallbackData;
		$params['excludeCaller'] = $excludeCaller;
		
		$result = $this->postRequest('email/email-company/',$params);
		
		return $this->getBooleanStatus($result);

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
		
		$results = $this->postRequest('companies/find-by-name/', $params);
		return $results;
	}

	// TODO - add listWorkspaces() - to return workspaces for the current company context

	/**
	 * Lists all websites.
	 * @return 
	 */
	public function listWebsites($startIndex = 0, $limit = 999) {
		
		$params['startIndex'] = $startIndex;
		$params['limit'] = $limit;
		
		$results = $this->postRequest('websites/list/', $params);
		return $results;
	}

	/**
	 * Lists all users.
	 * @return 
	 */
	public function listUsers($startIndex = 0, $limit = 999) {
		
		$params['startIndex'] = $startIndex;
		$params['limit'] = $limit;
		
		$results = $this->postRequest('users/list/', $params);
		return $results;
	}
			
	/**
	 * Lists all countries.
	 * @return 
	 */
	public function listCountries() {
		$results = $this->postRequest('countries/list/');
		return $results;
	}
	
	/***
	 * Adds a company.
	 * @return 
	 * @param object $companyName
	 */
	public function addCompany($companyName, $addressLine1 = '', 
		$addressLine2 = '', $addressLine3 = '', $addressLine4 = '', 
		$postcode = '', $countryCode = '', $tel = '', $fax = '', $logo = '') {
		
		$params['CompanyName'] = $companyName;
		$params['AddressLine1'] = $addressLine1;
		$params['AddressLine2'] = $addressLine2;
		$params['AddressLine3'] = $addressLine3;
		$params['AddressLine4'] = $addressLine4;
		$params['Postcode'] = $postcode;
		$params['CountryCode'] = $countryCode;
		$params['Tel'] = $tel;
		$params['Fax'] = $fax;
		$params['Logo'] = $logo;
		
		return $this->getBooleanStatus($this->postRequest('companies/add/', $params));
	}

	/**
	 * Puts an asset in to SWIM.
	 * @param $folder string The folder to put the asset in to.
	 * @param $fileName string The name of the file for the asset.
	 * @param $file string Path to the asset to send.
	 */
	public function putAsset($folder, $fileName, $file, $public = true) {

		$params['folder'] = $folder;
		$params['fileName'] = $fileName;
		$params['file'] = '@' . $file;
		$params['public'] = $public;
		
		return $this->getBooleanStatus($this->postRequest('assets/put/', $params));
	}
	
	/**
	 * Gets asset folders.
	 * @return 
	 */
	public function getAssetFolders($folder) {
		
		$params['folder'] = $folder;
		
		$results = $this->postRequest('assets/get-folders/', $params);
		return $results;
		
	}

	/**
	 * Gets asset folders.
	 * @param $secureDownloadUrlTimeOut The time (in seconds) secure download URLs will persist.
	 * @return 
	 */
	public function getAssetsForFolder($folder, $secureDownloadUrlTimeOut = 60) {
		
		$params['folder'] = $folder;
		
		$results = $this->postRequest('assets/get-assets-for-folder/', $params);
		return $results;
		
	}

	 /**
	  * Gets all asset details for a given folder/file.
	  * @param $secureDownloadUrlTimeOut The time (in seconds) secure download URLs will persist.
	  */
	 public function getAssetDetails($folder, $file, $forceParentCompany = false, $secureDownloadUrlTimeOut = 60) {
	 	
	 	$params['folder'] = $folder;
	 	$params['file'] = $file;
		
		//If set, this will ignore the company key (if any) and always get asset details using the parent company key.
		if($forceParentCompany) {
			$params['forceParentCompany'] = true;
		}
		
		return $this->postRequest('assets/get-asset-details/',$params);
	 }
	 
	 /**
	  * Deletes an asset.
	  * @return 
	  * @param object $folder
	  * @param object $file
	  */
	 public function deleteAsset($folder, $file) {
	 	
	 	$params['folder'] = $folder;
	 	$params['file'] = $file;
		
		return $this->getBooleanStatus($this->postRequest('assets/delete-asset/',$params));
	 }


	 	 
	/**
	 * Add asset folder.
	 * @return 
	 */
	public function addAssetFolder($parentFolder, $folder) {
	 	
	 	$params['parentFolder'] = $parentFolder;
	 	$params['folder'] = $folder;
		
		return $this->getBooleanStatus($this->postRequest('assets/add-asset-folder/', $params));
		
	}

	/**
	 * Adds a user in to the system.
	 * @return 
	 * @param object $forename
	 */
	public function addUser($params) {
		return $this->getBooleanStatus($this->postRequest('users/add/', $params));
	}

	/***
	 * Updates a user.
	 */
	public function updateUser($params) {			
		return $this->getBooleanStatus($this->postRequest('users/update/', $params));
	}
	
		
	/***
	 * Adds a website.
	 * @return 
	 * @param object $domain
	 */
	public function addWebsite($domain) {
		$params['Domain'] = $domain;
		return $this->getBooleanStatus($this->postRequest('websites/add/', $params));
	}
	
	/***
	 * Updates the current company.
	 */
	public function updateCompany($params) {			
		return $this->getBooleanStatus($this->postRequest('companies/update/', $params));
	}
	
	/***
	 * Gets details of the current company.
	 * @return 
	 * @param object $companyName
	 */
	public function getCompanyDetails() {
		return $this->postRequest('companies/get-details/');
	}
	
	/***
	 * Returns details of a user.
	 * @return 
	 * @param object $userID
	 */
	public function getUserDetails($userID) {
		$params['UserID'] = $userID;
		return $this->postRequest('users/get-details/',$params);
	}
	 
	/**
	 * Stores some data remotely against the context. $data can be an array of key value pairs.
	 */
	public function putData($dataGroup, $dataKey, $data) {
		
		if(empty($data)) {
			return false;
		}
		
		$params['dataGroup'] = $dataGroup;
		$params['dataKey'] = $dataKey;
		$params['data'] = $data;
		
		return $this->getBooleanStatus($this->postRequest('putData',$params));
		
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
	private function postRequest($method, $params = null, $format = 'xml') {
		
		try {
		
			if(!isset($params)) {
				$params = array();
			}
			
			$params['appKey'] = $this->_appKey;		
			$this->addContextParams($params);
			
			if($this->outputCalls) {
			
				$nonce = $this->getNonce();
				$securityToken = $this->getSecurityToken($nonce);
				
				$params['nonce'] = $nonce;
				$params['securityToken'] = $securityToken;
					
				echo '<div class="pane"><h3>Lucid Gecko API Call</h3>';
				echo '<dl><dt><strong>Method:</strong></dt><dd>' . $method . '</dd>';
				echo '<dt><strong>Call:</strong></dt><dd class="grid full"><textarea style="width:100%; height: 100px;">' . $this->apiServer.$format.'/'.$method.'?'.http_build_query($params) . '</textarea></dd></dl>';
				echo '</div>';	
					
			}
		
			//Signing paramters
			$nonce = $this->getNonce();
			$securityToken = $this->getSecurityToken($nonce);
			$params['nonce'] = $nonce;
			$params['securityToken'] = $securityToken;

						
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
			
			$rawResult = ''; //Will hold our result.
			
			if (function_exists('curl_init')) {
				
				// Use CURL if installed...
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->apiServer.$format.'/'.$method);
				//curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
				//Some servers (like Lighttpd) will not process the curl request without this header and will return error code 417 instead. 
				//Apache does not need it, but it is safe to use it there as well.
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
				
				//curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				//We have a problem here! The above causes file uploads to fail, the below doesn't work for activity.
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Lucid Gecko 2.0 API PHP5 Client 1.0 (curl) ' . phpversion());			
					
				//die(http_build_query($params));
				//http_build_query($params);
				$rawResult = curl_exec($ch);
			 
			} else {
				
				// Non-CURL based version...
				//TODO - Test and implement this version.
				/*$context =
				array('http' =>
				      array('method' => 'POST',
				            'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
				                        'User-Agent: Lucid Gecko API PHP5 Client 1.0 (non-curl) '.phpversion()."\r\n".
				                        'Content-length: ' . strlen($parmsQs),
				            'content' => $parmsQs));
				$contextid=stream_context_create($context);
				$sock=fopen($this->apiServer, 'r', false, $contextid);
				if ($sock) {
					$result='';
					while (!feof($sock)) {
					  $rawResult.=fgets($sock, 4096);
					}
					fclose($sock);
				}*/
			
			}
			
			/*Output debugging of response*/
			if($this->outputCalls) {
					
				echo '<div class="pane"><h3>Lucid Gecko API Response</h3>';
				echo '<dl><dt><strong>Method:</strong></dt><dd>' . $method . '</dd>';
				echo '<dt><strong>Call:</strong></dt><dd class="grid full"><textarea style="width:100%; height: 100px;">' . htmlentities($rawResult) . '</textarea></dd></dl>';
				echo '</div>';	
					
			}
			
			$rawResult = trim($rawResult);
			
			if(empty($rawResult)) {
				throw new LucidGeckoException('An unknown error occurred. No response returned.');
			}
			
			//throw new Exception('oops');
			
			try {
	
				$xmlOutput = simplexml_load_string($rawResult);
				$result = $this->convertSimpleXmlToArray($xmlOutput);
				
				//If this is a multi record result set.
				if($xmlOutput['records']) {
					$this->recordCount['records'] = (int)$xmlOutput['records'];
					$this->recordCount['total_records'] = (int)$xmlOutput['total_records'];
				} else {
					unset($this->recordCount);
				}
		
				if(is_array($result)) {
					
					if(array_key_exists('error_code', $result)) {
						throw new LucidGeckoException($result['error_message'],$result['error_code']);
					} else {
						return $result;
					}

				} else {
					//No results returned.
					return false;
				}
				
			} catch(Exception $e) {
				//Need to do something better with this error in future.
				throw new LucidGeckoException($e->getMessage(), $e->getCode());
			}	
			
		} catch (Exception $e) {
			
			if($this->friendlyErrors) {
				//This outer catch deals with friendly error output.
				echo '<div class="pane"><h3>Lucid Gecko Error</h3>';
				echo '<dl><dt><strong>Number:</strong></dt><dd>' . $e->getCode() . '</dd>';
				echo '<dt><strong>Message:</strong></dt><dd>' . $e->getMessage() . '</dd>';
				echo '<dt><strong>Call:</strong></dt><dd class="grid full"><textarea style="width:100%; height: 100px;">' . $this->apiServer.$format.'/'.$method.'?'.http_build_query($params) . '</textarea></dd></dl>';
				echo '</div>';				
			} else {
				throw $e;
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
 	 * Loops through SimpleXML data and converts it to PHP array.
 	 */
	private function convertSimpleXmlToArray($sxml) {
    	$arr = array();
    	if ($sxml) {
			foreach ($sxml as $k => $v) {
				if ($sxml['list']) {
		          $arr[] = $this->convertSimpleXmlToArray($v);
		        } else {
		          $arr[$k] = $this->convertSimpleXmlToArray($v);
		        }
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

	/**
	 * Takes an array that hopefully looks like array('response'=>'success') and returns true/false depending on the status.
	 * @return 
	 * @param object $result
	 */
	private function getBooleanStatus($result) {
		
		if(is_array($result) && array_key_exists('response',$result) && strcasecmp($result['response'], 'success') == 0){
			return true;
		} else {
			return false;
		}
		
	}
}

/**
 * This is a custom exception object thrown by various parts of the LG lib.
 */
class LucidGeckoException extends Exception
{}

/**
 * Tampering has gone on if we have to throw one of these, nasty.
 */
class LucidGeckoSecurityException extends Exception
{}
?>
