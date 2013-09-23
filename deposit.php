<?php

require_once './autoload.php';

$account_id = $_SERVER['argv'][1];
$amount     = $_SERVER['argv'][2];

$task_scheduler = new TaskScheduler();
$workflow_engine = new WorkflowEngine($task_scheduler);

$account = new Account($account_id);

if ($amount >= 0) {
    $account->deposit($amount);
} else {
    $account->withdraw(abs($amount));
}

$workflow_case = new WorkflowCase('PrepaidLifecycleWorkflow', $account);
$workflow_engine->setCase($workflow_case);
$workflow_engine->sendMessage($account->getStatus().'.deposit');
