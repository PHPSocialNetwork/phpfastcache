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

require_once(MCWD . '/support/utils.php');
require_once(MCWD . '/support/Snoopy.class.php');

$args = process_input(
    array(
        'url' => STRING,
        'w' => INTEGER,
        'h' => INTEGER,
    )
);

if (isset($args['w']) && ($args['w'] > 1024 || $args['w'] <= 0)) {
    $args['w'] = 75;
}
if (isset($args['h']) && ($args['h'] > 1024 || $args['h'] <= 0)) {
    $args['h'] = 75;
}

if (!$args['url']) {
    return;
}

if (!extension_loaded('gd') && !extension_loaded('gd2')) {
    trigger_error("GD is not loaded", E_USER_ERROR);
    exit;
}

$snoopy = new snoopy();

$snoopy->cookies = $_COOKIE;

$args['url'] = trim(str_replace(' ', '%20', $args['url']));

if ($snoopy->fetch($args['url'])) {
    $image = @imagecreatefromstring($snoopy->results);

    if ($image) {
        if (isset($args['w']) && isset($args['h'])) {
            $oldwidth = imagesx($image);
            $oldheight = imagesy($image);
            $newwidth = $oldwidth;
            $newheight = $oldheight;
            if ($oldwidth > $oldheight) {
                $newwidth = $args['w'];
                $newheight = ((float)$newwidth / (float)$oldwidth) * $oldheight;
            } else {
                $newheight = $args['h'];
                $newwidth = ((float)$newheight / (float)$oldheight) * $oldwidth;
            }
            $new_image = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($new_image, $image, 0, 0, 0, 0, $newwidth, $newheight, imagesx($image), imagesy($image));
            header('Content-type: image/jpeg');
            imagejpeg($new_image);
            exit;
        } else {
            header('Content-type: image/jpeg');
            imagejpeg($image);
            exit;
        }
    }
}
