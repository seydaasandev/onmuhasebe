<?php
// Test AJAX satislar.php
$_POST = [
    'draw' => 1,
    'start' => 0,
    'length' => 10,
    'search' => ['value' => ''],
];

require "satislar.php";
?>
