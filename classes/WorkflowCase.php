<?php

class WorkflowCase extends Show
{
    private $_workflow_id;
    private $_case_id;
    
    public $workflow;
    public $tokens = array();

    public function __construct($workflow_id, $account)
    {
        $this->_workflow_id = $workflow_id;
        $this->_case_id = $account->getId();
        
        $this->workflow = new $workflow_id($this->_case_id);
        
        if (!$this->loadFromFile()) {
            $this->tokens[$account->getStatus()] = date('YmdHis');
            $this->saveToFile();
        }        
    }
    
    public function getId()
    {
        return $this->_case_id;
    }
    
    public function save()
    {
        $this->saveToFile();
    }
    
    private function loadFromFile()
    {
        $filename = './data/'.$this->_case_id.'.case';
        if (file_exists($filename)) {
            $data = unserialize(file_get_contents($filename));
            
            $this->_workflow_id = $data->_workflow_id;
            $this->_case_id     = $data->_case_id;
            $this->tokens       = $data->tokens;
            return true;
        } else {
            return false;
        }
    }
    
    private function saveToFile()
    {
        $filename = './data/'.$this->_case_id.'.case';
        file_put_contents($filename, serialize($this));
    }
    
}
