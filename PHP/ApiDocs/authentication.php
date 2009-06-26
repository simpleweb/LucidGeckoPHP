<div class="pane"><h3>External Authentication</h3>
<p>When you need to authenticate against SWiM in an external application you can use the API lib to generate a one time only unique authentication key.</p>
<p>You can then pass this authentication key to your application (normally in the query string) and on the external application, use the API lib to validate it.</p>
<p>Authentication keys are unique to the application installation so you will need to manually provide the necessary context information within the third party application.</p>
<p>Once an authentication key has been used it is useless and will not authenticate a second time.</p>
<h4>To Generate an Authentication Key</h4>
<p>Please remember that only one auth key can exist at a time for a given user/app context.<p>
<p>Therefore, each subsequent call to this function will invalidate any previous API call.</p>

<code>$lucidGecko->generateAuthKey();</code>

<?php
//Let's get cracking. Include the Swim API lib. This is known as LucidGecko.
require_once('LucidGecko/LucidGecko.php');

//Include a config file with our api details.
require_once('config.php');

//Create an instance of the API Lib. This is like this with getInstance because the class is implemented as a singleton.
$lucidGecko = LucidGecko::getInstance(PLUGIN_KEY, PLUGIN_SECRET);

$authKey = $lucidGecko->generateAuthKey();
echo '<p><strong>Auth key is:</strong>' . $authKey . '</p>';
?>
<h4>To validate an authentication key in your external application.</h4>
<p>
Because the application is external to SWiM the API Lib will not automatically determine the context information necessary to perform an authentication.</p>
<p>For this reason, it is up to the app developer to record the information and manually provide it to the API. The context information required is:</p>
<p>
	<ul>
		<li>companyKey</li>
		<li>companyGuid</li>
		<li>websiteKey</li>
		<li>websiteGuid</li>
	</ul>
</p>
<p>
	The code to perform the authentication looks like this:	
</p>
<p><code>$lucidGecko->validateAuthKey($companyKey,$companyGuid,$websiteKey,$websiteGuid,$authKey);</code></p>
<?php

try {
	$authenticatedUser = $lucidGecko->validateAuthKey($authKey);
} catch(Exception $e) {
	echo '<strong>Failed to authenticate.</strong> ' . $e->getMessage();
}
?>
<p>Providing no exception is thrown, an array containing user details will be returned as follows:</p>
<p><pre><code>Array
(
	[error_code] => 0
	[user_id] => <?php echo $authenticatedUser['user_id']?>

	[username] => <?php echo $authenticatedUser['username']?>

	[user_guid] =><?php echo $authenticatedUser['user_guid']?>

	[user_forename] => <?php echo $authenticatedUser['user_forename']?>

	[user_surname] => <?php echo $authenticatedUser['user_surname']?>

	[status] => success
)</code></pre></p>

</div>
<?php
require_once('appnav.php');
?>