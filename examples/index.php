<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

echo '<h2> Drivers available: </h2>';
echo '<ul>';

foreach (glob("*.php") as $filename) {
    if (basename(__FILE__) !== $filename) {
        echo "<li><a href=\"$filename\">" . ucfirst(basename($filename, '.php')) . "</a></li>";
    }
}

echo '</ul>';

// Comment this "exit;" to display extensions 
// informations about missing/misconfigured drivers
exit;

echo '<h2> PHP extensions loaded:  </h2>';
echo '<ul>';

$extensions = array_map('ucfirst', get_loaded_extensions());
sort($extensions);

foreach ($extensions as $extension) {
    echo "<li>$extension</li>";
}

echo '</ul>';

echo '<h2> PHP info:  </h2>';

phpinfo();
