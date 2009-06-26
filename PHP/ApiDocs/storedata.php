<div class="pane"><h3>Put/Get Data</h3>
<p>
SWiM has a simple data storage system for putting and getting key/value pairs of data that are grouped. When data is stored it is created if it does not previously exist and updated if it does.
</p>
<?php
//Let's get cracking. Include the Swim API lib. This is known as LucidGecko.
require_once('LucidGecko/LucidGecko.php');

//Include a config file with our api details.
require_once('config.php');

//Create an instance of the API Lib. This is like this with getInstance because the class is implemented as a singleton.
$lucidGecko = LucidGecko::getInstance(PLUGIN_KEY, PLUGIN_SECRET);

/*
Store some data.
----------------
*/
?>
<div class="grid large">
<p>To put data:</p>
<code>
$lucidGecko->putData(Group, Field, Data);
</code>
</div>

<div class="grid large">
<p>To retrieve data:</p>
<code>
$lucidGecko->getDataByDataGroup(Group);
</code>
</div>

<p>Example (see code):</p>
<?php

$lucidGecko->putData('settings', 'field1', 'Data value 1 ' . time());
$lucidGecko->putData('settings', 'field2', 'Data value 2 ' . time());

//Get back some data I've stored in to an array.
$settings = $lucidGecko->getDataByDataGroup('settings');

echo 'Stored field1: ' . $settings['field1'] . '<br />';
echo 'Stored field2: ' . $settings['field2'] . '<br />';
?>
</div>
<?php
require_once('appnav.php');
?>