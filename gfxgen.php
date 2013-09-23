<?php

require_once './autoload.php';

$account_id = $_SERVER['argv'][1];

$account = new Account($account_id);
$case = new WorkflowCase('PrepaidLifecycleWorkflow', $account);
$flow = $case->workflow;

$generator = new GfxGenerator();
$generator->generate($account_id, $case, $flow);