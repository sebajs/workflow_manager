<?php

class WorkflowCase extends Show
{
    private $_workflow_id;
    private $_case_id;

    /**
     * @var Workflow
     */
    public $workflow;
    public $tokens = array();

    /**
     * @param int     $workflow_id
     * @param Account $account
     */
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

    public function getTokens()
    {
        return $this->tokens;
    }

    public function getTokenAtPlace($place)
    {
        return ($this->isTokenAtPlace($place)) ? $this->tokens[$place] : null;
    }

    public function isTokenAtPlace($place)
    {
        return !empty($this->tokens[$place]);
    }

    public function setTokenAtPlace($place, $token)
    {
        $this->tokens[$place] = $token;
    }

    public function clearTokenAtPlace($place)
    {
        unset($this->tokens[$place]);
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
        chmod($filename, 0666);
    }
    
}
