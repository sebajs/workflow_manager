<?php

require_once './autoload.php';

$account_id = $_GET['a'];

$account = new Account($account_id);
$case = new WorkflowCase('PrepaidLifecycleWorkflow', $account);
$flow = $case->workflow;

$generator = new GfxGenerator();
$file = $generator->generate($account_id, $case, $flow);

header("Content-type: image/png");
header('Expires: 0');
header('Content-Length: '.filesize($file));
readfile($file);