<?php

class a {

    function __construct() {
        return new b();
    }

    function x() {
        echo " -> X";
    }
}

class b {

    function x() {
        echo " --> X2 ";
    }

    function y() {
        echo " --> Y";
    }
}

$a = new a();
$a->x();


