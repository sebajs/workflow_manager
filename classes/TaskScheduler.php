<?php

class TaskScheduler extends Show
{
    public $tasks = array();
    
    public function __construct()
    {
        if (!$this->loadFromFile()) {
            $this->saveToFile();
        }  
    }
    
    public function putTask($time, $transition, $case_id)
    {
        
        $insert = true;
        foreach ($this->tasks as $task) {
            list ($stored_time, $stored_case_id, $stored_transition) = explode('|', $task);
        
            if ($transition == $stored_transition && $case_id == $stored_case_id) {
                $insert = false;
            }
        }
        
        if ($insert) {
            $this->tasks[] = $time.'|'.$case_id.'|'.$transition;

            sort($this->tasks);
            $this->saveToFile();
        }
    }
    
    public function getTask()
    {
        $task = array();
        
        if ($nextTask = array_shift($this->tasks)) {
            list($time, $case_id, $transition) = explode('|',$nextTask);
            if ($time <= date('YmdHis')) {
                $task['case_id']    = $case_id;
                $task['transition'] = $transition;
                
                $this->saveToFile();
            }
        }
        
        return $task;
    }

    public function clearTasks($place, $case_id)
    {        
        $new_tasks = array();
        foreach ($this->tasks as $key => $task) {
            list($stored_time, $stored_case_id, $stored_transition) = explode('|', $task);
            list($stored_place, $stored_task) = explode('.', $stored_transition);
            
            if ($place == $stored_place && $case_id == $stored_case_id) {
                unset($this->tasks[$key]);
            }
        }
        $this->saveToFile();
    }
    
    private function loadFromFile()
    {
        $filename = './data/scheduler.dat';
        if (file_exists($filename)) {
            $data = unserialize(file_get_contents($filename));
            
            $this->tasks = $data->tasks;
            return true;
        } else {
            return false;
        }
    }
    
    private function saveToFile()
    {
        $filename = './data/scheduler.dat';
        file_put_contents($filename, serialize($this));
    }
    
}
