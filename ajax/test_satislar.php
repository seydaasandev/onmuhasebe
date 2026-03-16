<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Test AJAX satislar.php
$_POST = [
    'draw' => 1,
    'start' => 0,
    'length' => 10,
    'search' => ['value' => ''],
    'start_date' => '',
    'end_date' => '',
    'order' => [['column' => 0, 'dir' => 'desc']]
];

require "satislar.php";
?>
