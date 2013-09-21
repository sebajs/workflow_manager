<?php

require_once './autoload.php';




$task_scheduler = new TaskScheduler();
$workflow_engine = new WorkflowEngine($task_scheduler);

$account_id = 123;

$account = new Account($account_id);
$account->deposit(10);

$workflow_case = new WorkflowCase('PrepaidLifecycleWorkflow', $account);
$workflow_engine->setCase($workflow_case);
$workflow_engine->sendMessage($account->getStatus().'.deposit');
