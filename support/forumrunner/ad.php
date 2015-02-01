<?php
/*
 * Forum Runner
 *
 * Copyright (c) 2010-2011 to End of Time Studios, LLC
 *
 * This file may not be redistributed in whole or significant part.
 *
 * http://www.forumrunner.com
 */

define('MCWD', (($getcwd = getcwd()) ? $getcwd : '.'));

require_once(MCWD . '/include/general_vb.php');

chdir('../');

define('THIS_SCRIPT', 'showthread');
define('CSRF_PROTECTION', false);

$phrasegroups = array();
require_once('./global.php');

$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
if (strpos($agent, 'iphone') === false && strpos($agent, 'ipad') === false &&
    strpos($agent, 'ipod') === false && strpos($agent, 'android') === false)
{
    header('Location: ' . $vbulletin->options['bburl']);
    return;
}

$kw = $vbulletin->options['keywords'] . ' ' . $vbulletin->options['description'];
print <<<EOF
<html><head><style>* {margin:0; padding:0;}</style></head><body>
<span style="display:none">$kw</span>
<center>
EOF;
print $vbulletin->options['forumrunner_googleads_javascript'];
print <<<EOF
</center>
</body></html>
EOF;

?>
