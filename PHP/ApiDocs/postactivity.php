<div class="pane"><h3>Post Activity</h3>
<p>It's easy to post a status activity message to SWiM.</p>
<p>The first step is to define the messages in the developer section for the application. Each message has a key with a message. Using the API, you tell it to add a particular message using the key. You do not post the message itself.</p>
<p>It's possible to put variable data in to a message using placeholder tags such as {DATA} in each message.</p>
<p>The code to post a message looks like this (you do not need to specify extended data).</p>
<p>
	<code>$lucidGecko->postActivity('testmessage', array('DATA' => 'Data item1', 'DATA2', => 'Data item2'));</code>
</p>
<?php
//Let's get cracking. Include the Swim API lib. This is known as LucidGecko.
require_once('LucidGecko/LucidGecko.php');

//Include a config file with our api details.
require_once('config.php');

//Create an instance of the API Lib. This is like this with getInstance because the class is implemented as a singleton.
$lucidGecko = LucidGecko::getInstance(PLUGIN_KEY, PLUGIN_SECRET);

$lucidGecko->postActivity('testmessage', array('Name' => 'Tom', 'Dob' => '15-03-1980'));
?>
</p>
</div>
<?php
require_once('appnav.php');
?>