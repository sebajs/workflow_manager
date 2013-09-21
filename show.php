<?php

require_once './autoload.php';

$file   = $_SERVER['argv'][1];
$params = (isset($_SERVER['argv'][2]) ? explode(',',$_SERVER['argv'][2]) : array());
$data   = unserialize(file_get_contents($file));

print_r($data->pick($params));
