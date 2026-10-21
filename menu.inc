<?php
# One page, under Content Setup.
$menuEntries = array(
	array(
		'text' => 'Marquee',
		'type' => 'content',
		'page' => 'content.php',
		'wrap' => 1,
	),
);

foreach ($menuEntries as $entry) {
	if ($entry['type'] != $menu) {
		continue;
	}
	$nopage = (isset($entry['wrap']) && $entry['wrap'] == 0) ? '&nopage=1' : '';
	printf("<li><a href='plugin.php?plugin=%s&page=%s%s'>%s</a></li>\n", $plugin, $entry['page'], $nopage, $entry['text']);
}
?>
