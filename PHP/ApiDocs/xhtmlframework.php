<!--You can put the main app nav anywhere in your output and it will be stripped out and moved, just make sure it's in a div ID'd as appNav -->
<div id="appNav">
	<h4>App Nav Title</h4>
	<ul>
		<li><a href="/index.php">Home</li>
		<li class="on"><a href="/example.html">Example HTML</li>
	</ul>
</div>

<!-- Everything should site within a pane. You can have one ore more panes on a page. -->
<div class="pane">
	
	<h3>Heading within a pane should be an H3</h3>
	
	<!--To float stuff in a grid, just add class of grid and then small, medium, large-->
	<div class="grid medium">
		This is column 1 in a grid.
	</div>
	
	<div class="grid medium">
		This is column 2 in a grid.
	</div>
	
	<div class="grid medium">
		This is column 3 in a grid.
	</div>
	
</div>

<div class="pane">
	
	<h3>Form example</h3>
	
	<form action="" method="post">
		
		<!--As SWiM is strict XHTML hidden input elements need to be wrapped in a container. Either put them in a span or within the fieldset.-->
		<span><input type="hidden" name="some data" value="blah" /></span>
		
		<!--For errors should be output like this-->
		<div class="formErrors">
			<h4>Oops! There were errors:</h4>
			<ul>
				<li><label for="field1">Field 1 - Put some data in you idiot.</label></li>
			</ul>
		</div>
		
		<!--Forms should be marked up within fieldsets in a definition list.-->
		<fieldset>
			<!--Fieldset titles should be marked up as follows-->
			<legend><span>Fieldset title</span></legend>
			
			<dl>
				<dt><label for="field1">This is my label</label></dt>
				<dd><input type="text" id="field1" name="field1" /></dd>
				<dd class="formInfo">You can put a field example or help using a DD classed with formInfo</dd>
				
				<!--You can span a field to full width easily by adding class of full-->
				<dt><label for="field2">This is my second label</label></dt>
				<dd class="full"><input type="text" id="field2" name="field2" /></dd>
				<dd class="full formInfo">You can put a field example or help using a DD classed with formInfo</dd>
				
			</dl>
			
		</fieldset>	
		
		<p class="submitButton">
			<input type="submit" id="submit" value="Save" />  <a href="/index.php">cancel</a>
		</p>	
		
	</form>
	
</div>

<div class="pane">

	<h3>Tables and Pane Menu</h3>
	
	<!--You can add in a pane menu such as this-->
	<ul class="paneActions menu">
		<li class="on"><a href="/example.html">First Item</a></li>
		<li><a href="/example.html">Second Item</a></li>
		<!--Class the last item to remove the separator.-->
		<li class="last"><a href="/example.html">Last Item</a></li>
	</ul
	
	<!--Tables shouldnt need much adjustment-->
	<table>
		<tr>
			<th>Field 1</th>
			<th>Field 2</th>
			<th>Field 3</th>
			<th>Field 4</th>
		</tr>

		<tr>
			<td>Data Row 1 Field 1</td>
			<td>Data Row 2 Field 2</td>
			<td>Data Row 3 Field 3</td>
			<td>Data Row 4 Field 4</td>
		</tr>
	</table>
	
	
</div>
<?php
require_once('appnav.php');
?>