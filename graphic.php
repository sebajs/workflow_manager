<?php

require_once './autoload.php';

$account_id = (isset($_GET['a'])) ? $_GET['a'] : '';
$refresh    = (isset($_GET['r'])) ? $_GET['r'] : 0;

$account = new Account($account_id);
$case    = new WorkflowCase('PrepaidLifecycleWorkflow', $account);
$flow    = $case->workflow;

$generator = new GfxGenerator();
$file      = $generator->generate($account_id, $case, $flow);

if ($refresh > 0) {
    header("refresh:{$refresh}");
}
header("Content-type: image/png");
header('Expires: 0');
header('Content-Length: '.filesize($file));
readfile($file);