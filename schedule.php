<?php

require_once './autoload.php';

$task_scheduler = new TaskScheduler();

$task = $task_scheduler->getTask();

print_r($task);

if (is_array($task) && isset($task['case_id']) && isset($task['transition'])) {
    $workflow_engine = new WorkflowEngine($task_scheduler);    

    $account = new Account($task['case_id']);
    $workflow_case = new WorkflowCase('PrepaidLifecycleWorkflow', $account);
    $workflow_engine->setCase($workflow_case);
    $workflow_engine->execute($task['transition']);
}
