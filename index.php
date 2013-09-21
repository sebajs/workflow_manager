<?php

require_once './WorkflowEngine.php';
require_once './WorkflowCase.php';
require_once './TaskScheduler.php';
//require_once './prepaid_lifecycle.workflow.php';

function debug($data, $level = 0){
    echo '<pre style="margin-left:',$level * 40,'px;">';
    print_r($data);
    echo '</pre>';
}


//$task_scheduler = new TaskScheduler();
$task_scheduler = WorkflowEngine::getTaskScheduler();



//$task_scheduler->putTask(1, 'a.uno');
//$task_scheduler->putTask(2, 'b.dos');
//$task_scheduler->putTask(3, 'a.tres');
//$task_scheduler->putTask(4, 'c.cuatro');
//
//debug($task_scheduler);
//
//$task_scheduler->clearTasks('a');
//debug($task_scheduler);
//
//$transition = $task_scheduler->getTask();
//debug($transition);
//debug($task_scheduler);
//
//die();

$workflow_engine = new WorkflowEngine();



$case_id = 123;
$workflow_case = new WorkflowCase('PrepaidLifecycleWorkflow', $case_id);


$workflow_engine->setCase($workflow_case);


$workflow_engine->sendMessage('payment_notice', 10);


debug($task_scheduler);

while(!$transition = $task_scheduler->getTask()){
    sleep(1);
}
$workflow_engine->execute($transition);

debug($workflow_case);
debug($task_scheduler);

while(!$transition = $task_scheduler->getTask()){
    sleep(1);
}
$workflow_engine->execute($transition);

debug($workflow_case);
debug($task_scheduler);

while(!$transition = $task_scheduler->getTask()){
    sleep(1);
}
$workflow_engine->execute($transition);

debug($workflow_case);
debug($task_scheduler);

while(!$transition = $task_scheduler->getTask()){
    sleep(1);
}
$workflow_engine->execute($transition);

debug($workflow_case);
debug($task_scheduler);

