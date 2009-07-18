<swm:nav>
	<div id="appNav">
		<ul>
			<li<?= strtolower($_SERVER['SCRIPT_NAME']) == '/index.php' ? ' class="on"' : ''; ?>><a href="//index.php">Intro</a></li>
			<li<?= strtolower($_SERVER['SCRIPT_NAME']) == '/context.php' ? ' class="on"' : ''; ?>><a href="//context.php">Context Information</a></li>
			<li<?= strtolower($_SERVER['SCRIPT_NAME']) == '/postactivity.php' ? ' class="on"' : ''; ?>><a href="//postactivity.php">Posting To Activity Feed</a></li>
			<li<?= strtolower($_SERVER['SCRIPT_NAME']) == '/activityfeed.php' ? ' class="on"' : ''; ?>><a href="//activityfeed.php">Example Activity Feed</a></li>
			<li<?= strtolower($_SERVER['SCRIPT_NAME']) == '/xhtmlframework.php' ? ' class="on"' : ''; ?>><a href="//xhtmlframework.php">XHTML Framework</a></li>
			<li<?= strtolower($_SERVER['SCRIPT_NAME']) == '/typography.php' ? ' class="on"' : ''; ?>><a href="//typography.php">Example Typography</a></li>
			<li<?= strtolower($_SERVER['SCRIPT_NAME']) == '/layout.php' ? ' class="on"' : ''; ?>><a href="//layout.php">Example Layout</a></li>
			<li<?= strtolower($_SERVER['SCRIPT_NAME']) == '/swmml.php' ? ' class="on"' : ''; ?>><a href="//swmml.php">SWMML</a></li>
		</ul>
	</div>
</swm:nav>