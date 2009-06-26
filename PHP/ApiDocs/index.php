<div class="pane"><h3>Context Information</h3>
<p>
	
<?php
//Let's get cracking. Include the Swim API lib. This is known as LucidGecko.
require_once('LucidGecko/LucidGecko.php');

//Include a config file with our api details.
require_once('config.php');

//Create an instance of the API Lib. This is like this with getInstance because the class is implemented as a singleton.
$lucidGecko = LucidGecko::getInstance(PLUGIN_KEY, PLUGIN_SECRET);

//Information for the logged in user.
?>
It's easy to get details of the currently logged in user:
<?php
echo '<p><strong>Forename:</strong> ' . $lucidGecko->user['Forename'] . '<br/>';
echo '<strong>Surname:</strong> ' . $lucidGecko->user['Surname'] . '<br/>';
echo '<strong>Full Name:</strong> ' . $lucidGecko->user['Name'] . '<br/>';
echo '<strong>Username:</strong> ' . $lucidGecko->user['Username'] . '<br/></p>';

?>
<p>
User permissions have intentionally been kept simple and it's up to the app to determine what type of actions a user can perform.
There are only really 3 types of users:
</p>
<ul>
	<li>Reseller: E.g. The Web People</li>
	<li>Company Admin: E.g. The boss or someone techy at one of our clients.</li>
	<li>Basic Company User: E.g. A staff member at one of our clients.</li>
</ul>
<p>
Elements such as app settings etc should only really be accessible to Resellers or possibly Company Admins.</p>
<?php
echo '<p><strong>Reseller:</strong> ' . $lucidGecko->user['IsReseller'] . '<br/>';
echo '<strong>CompanyAdmin:</strong> ' . $lucidGecko->user['IsCompanyAdmin'] . '<br/></p>';
?>
<p>At the moment, apps are passed limited company information as follows in two arrays.</p>

<p>If you are storing your own data you can use the key as unique company keys. No Reseller or Company can share the same key. If you use the putData method of the API lib, the storage of the information will be taken care of in the correct context location.</p>

<p>For the reseller this is:</p>

<?php
echo '<p><strong>Reseller Key:</strong> ' . $lucidGecko->parentCompany['Key'] . '<br/>';
?>

<p>For the company this is: (this could be missing if the app is installed at a reseller level).</p>
<?php
echo '<p><strong>Company Key:</strong> ' . $lucidGecko->company['Key'] . '<br/>';
?>

<p>For a plugin installed at a website level, the following website details are also available.
The website key is unique for each website and is therefore suitable for storing data.</p>

<?php
echo '<p><strong>Website Key:</strong> ' . $lucidGecko->website['Key'] . '<br/>';
echo '<strong>Domain (this is the primary domain):</strong> ' . $lucidGecko->website['Domain'] . '<br/></p>';
?>

<p>
You also have access to the URL of swim for postbacks.
</p>
<p>
In your markup, if you have a link such as &lt;a href=&quot;/page.htm&quot;&gt; the '/' will automatically be replaced with this full postback url.
</p>
<?php
echo '<p><strong>Postback URL:</strong> ' .$lucidGecko->postbackUrl . '<br /></p>';
?>

<h3>Notes:</h3>
<p>
	<ul>
		<li>At the moment, you can only place app files in the root of your application.</li>
		<li>The PHP lib uses CURL for communicating with the server.</li>
	</ul>
</p>

</p></div>	

<!--You can put the main app nav anywhere in your output and it will be stripped out and moved, just make sure it's in a div ID'd as appNav -->
<?php
require_once('appnav.php');
?>