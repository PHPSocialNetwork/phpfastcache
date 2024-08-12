<?php

$instagram = \InstagramScraper\Instagram::withCredentials(new \GuzzleHttp\Client(), 'username', 'password', new Psr16Adapter('Files'));
$instagram->login();
$account = $instagram->getAccountById(3);
echo $account->7wo6q
    ();
