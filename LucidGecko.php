<?php
/**
 * LucidGecko Platform API - now with webhook love!
 * v4.100726
 * -----------------------
 * @author Tom Holder & Luke Marsden
 */
class LucidGecko {
	
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
	public $contactCompany;
	public $workspace;
	public $user;
	public $person;
	public $postbackUrl;
	
	//If set to true, friendly error messages will be output including the API call details.
	public $friendlyErrors = false;

	//If set each API call will be output to the browser. Should only ever be set in development.
	public $outputCalls = false;
			
	public function  __construct($appKey, $appSecret, $installID = null, $installSecret = null, $apiServer = null) {
		
		if(!isset($appKey)) throw new LucidGeckoException('No Application Key specified.');
		if(!isset($appSecret)) throw new LucidGeckoException('No Application Secret specified.');
		
		//Set the APP and API Keys
		$this->_appKey = $appKey;
		$this->_appSecret = $appSecret;
		$this->installID = $installID;
		$this->installSecret = $installSecret;

		$this->parentCompany['GUID'] = '';
		$this->parentCompany['UrlKey'] = '';
		$this->company['GUID'] = '';
		$this->company['UrlKey'] = '';
		$this->contactCompany['GUID'] = '';

		$this->workspace['ID'] = '';

		$this->user['ID'] = '';
		$this->user['GUID'] = '';
		$this->user['IsReseller'] = false;
		$this->user['IsCompanyAdmin'] = false;
		
		$this->person['ID'] = '';
		$this->person['GUID'] = '';
		
		$this->rawResponse = '';
		
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
			if(isset($_GET['LG_ParentCompanyGUID'])) {
				$this->parentCompany['GUID'] = $_GET['LG_ParentCompanyGUID'];
			}
			
			if(isset($_GET['LG_ParentCompanyUrlKey'])) {
				$this->parentCompany['UrlKey'] = $_GET['LG_ParentCompanyUrlKey'];
			}
			
			
			if(isset($_GET['LG_CompanyGUID'])) {
				$this->company['GUID'] = $_GET['LG_CompanyGUID'];
			}

			if(isset($_GET['LG_CompanyUrlKey'])) {
				$this->company['UrlKey'] = $_GET['LG_CompanyUrlKey'];
			}
			
			//Contact company - will only exist for company/people apps.
			if(isset($_GET['LG_ContactCompanyGUID'])) {
				$this->contactCompany['GUID'] = $_GET['LG_ContactCompanyGUID'];
			}
			
			//Set the location company.
			if(!empty($this->company['GUID'])) {
				$this->locationCompany = $this->company;
			} else {
				$this->locationCompany = $this->parentCompany;
			}
			
			if(isset($_GET['LG_WorkspaceID']))	{
				$this->workspace['ID'] = $_GET['LG_WorkspaceID'];
			}	
			
			//Set user details/
			$this->user['ID'] = $_GET['LG_UserID'];
			$this->user['GUID'] = $_GET['LG_UserGUID'];
			$this->user['IsReseller'] = (bool) $_GET['LG_UserIsReseller'];
			$this->user['IsCompanyAdmin'] = (bool) $_GET['LG_UserIsCompanyAdmin'];
			
			//Contact person - will only exist for people apps.
			if(isset($_GET['LG_PersonUserID'])) {
				$this->person['ID'] = $_GET['LG_PersonUserID'];
			}
			
			if(isset($_GET['LG_PersonGUID'])) {
				$this->person['GUID'] = $_GET['LG_PersonGUID'];
			}

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
		
		$this->rawResponse = $this->postRequest('activity/add/',$params);
		
		return $this->getBooleanStatus($this->rawResponse);

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
		
		$this->rawResponse = $this->postRequest('email/email-users/',$params);
		
		return $this->getBooleanStatus($this->rawResponse);

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
		
		$this->rawResponse = $this->postRequest('email/email-company/',$params);
		
		return $this->getBooleanStatus($this->rawResponse);

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
		
		$this->rawResponse = $this->postRequest('assets/get-folders/', $params);
		return $this->rawResponse;
		
	}

	/**
	 * Gets asset folders.
	 * @param $secureDownloadUrlTimeOut The time (in seconds) secure download URLs will persist.
	 * @return 
	 */
	public function getAssetsForFolder($folder, $secureDownloadUrlTimeOut = 60) {
		
		$params['folder'] = $folder;
		
		$this->rawResponse = $this->postRequest('assets/get-assets-for-folder/', $params);
		return $this->rawResponse;
		
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
		
		$this->rawResponse = $this->postRequest('assets/get-asset-details/',$params);
		return $this->rawResponse;
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
		
	 	$this->rawResponse = $this->postRequest('assets/delete-asset/',$params);
		return $this->getBooleanStatus($this->rawResponse);
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
	* Returns one or more elements of profile data.
	* @return array of profile data
	**/
	public function getProfileData($guids, $dataType) {
		
		$params['profileDataType'] = $dataType;
		$params['guids'] = $guids;
		$results = $this->postRequest('data/get-profile-data/', $params);
		
		return $results;
	}
	
	/**
	 * Returns profile data for a given type of all a companies people.
	 * @param unknown_type $guid Must be a valid company guid.
	 * @param unknown_type $dataType
	 */
	public function getProfileDataOfCompanyPeople($companyGuid, $dataType) {
		
		$params['profileDataType'] = $dataType;
		$params['guid'] = $companyGuid;
		$results = $this->postRequest('data/get-profile-data-of-company-people/', $params);
		
		return $results;
	}
	
	/**
	* Puts a request in to call a webhook with the specified interface and parameters.
	* @return true/false depending on status.
	* @param integer $installID The install id of the application we're calling.
	* @param string $interfaceName The name of the interface we are calling. The application being called must implement this interface or an exception will be returned.
	* @param array $params Data expected by the webhook.
	**/
	public function requestWebhook($installID, $interfaceName, $interfaceParams, $scheduled = false, $interval = false) {
		
		$params['endpointInstallID'] = $installID;
		$params['interface'] = $interfaceName;
		$params['interfaceParams'] = $interfaceParams;

                if($scheduled) {
                    $params['scheduled'] = $scheduled;
                    $params['interval'] = $interval;
                }

		$results = $this->postRequest('webhook/request-webhook/', $params);
		
		return $results;
		
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
	private function postRequest($method, $params = null, $format = 'json') {
		
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
			
			$this->rawResponse = ''; //Will hold our result.
			
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
					
				$this->rawResponse = curl_exec($ch);
			 
			} else {
				
				die('The PHP version of LucidGecko requires CURL.');
			
			}
			
			/*Output debugging of response*/
			if($this->outputCalls) {
					
				echo '<div class="pane"><h3>Lucid Gecko API Response</h3>';
				echo '<dl><dt><strong>Method:</strong></dt><dd>' . $method . '</dd>';
				echo '<dt><strong>Call:</strong></dt><dd class="grid full"><textarea style="width:100%; height: 100px;">' . htmlentities($this->rawResponse) . '</textarea></dd></dl>';
				echo '</div>';	
					
			}
			
			$this->rawResponse = trim($this->rawResponse);
			
			if(empty($this->rawResponse)) {
				throw new LucidGeckoException('An unknown error occurred. No response returned.');
			}
			
			//throw new Exception('oops');
			
			try {
	
				$result = json_decode($this->rawResponse, true);
		
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
		
		if(isset($this->contactCompany) && array_key_exists('GUID', $this->contactCompany) && !empty($this->contactCompany['GUID'])) {
			$params['contactCompanyGUID'] = $this->contactCompany['GUID'];
		}
		
		if(isset($this->person) && array_key_exists('GUID', $this->person) && !empty($this->person['GUID'])) {
			$params['personGUID'] = $this->person['GUID'];
		}
				
		//User information does not always have to be passed.
		if(!array_key_exists('userID',$params) || !array_key_exists('userSecret',$params)) {
			if(isset($this->user)) {
				$params['userID'] = $this->user['ID'];
				$params['userGUID'] = $this->user['GUID'];
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
