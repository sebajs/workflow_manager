<?php

class PrepaidLifecycleWorkflow
{

    public $places = array(
        'idle' => array(
            'out_arcs' => array(
                'idle.setActive',
            ),
        ),
        'active' => array(
            'out_arcs' => array(
                'active.setGrace',
                'active.setActiveWithMsgs',
            ),
        ),
        'active_with_msgs' => array(
            'out_arcs' => array(
                'active_with_msgs.sendMessage',
                'active_with_msgs.setActive',
                'active_with_msgs.setGrace',
            ),
        ),
        'grace' => array(
            'out_arcs' => array(
                'grace.setActive',
                'grace.setPassiveAccum',
            ),
        ),
        'passive_accum' => array(
            'out_arcs' => array(
                'passive_accum.setActive',
                'passive_accum.accumulatePeriodDebt',
                'passive_accum.setPassiveNotAccum',
            ),
        ),
        'passive_not_accum' => array(
            'out_arcs' => array(
                'passive_not_accum.setActive',
                'passive_not_accum.expropiateBalance',
                'passive_not_accum.setExpired',
            ),
        ),
        'expired' => array(
            'out_arcs' => array(
                'expired.setActive',
                'expired.setShutdown',
            ),
        ),
        'shutdown' => array(
            'out_arcs' => array(),
        ),
    );

    public $transitions = array(
        'idle.setActive' => array(
            'trigger' => 'MSG',
            'message' => 'idle.deposit',
            'task' => 'setActive',
            'in_arcs' => array(
                'idle',
            ),
            'out_arcs' => array(
                'active' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'idle' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),
        'active.setGrace' => array(
            'trigger' => 'TIME',
            'time_limit' => 'active.setGrace',
            'task' => 'setGrace',
            'in_arcs' => array(
                'active',
            ),
            'out_arcs' => array(
                'active' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'grace' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),
        'active.setActiveWithMsgs' => array(
            'trigger' => 'TIME',
            'time_limit' => 'active.setActiveWithMsgs',
            'task' => 'setActiveWithMsgs',
            'in_arcs' => array(
                'active',
            ),
            'out_arcs' => array(
                'active' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'active_with_msgs' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),
        'active_with_msgs.setActive' => array(
            'trigger' => 'MSG',
            'message' => 'active_with_msgs.deposit',
            'task' => 'setActive',
            'in_arcs' => array(
                'active_with_msgs',
            ),
            'out_arcs' => array(
                'active' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'active_with_msgs' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),
        'active_with_msgs.sendMessage' => array(
            'trigger' => 'TIME',
            'time_limit' => 'active_with_msgs.sendMessage',
            'task' => 'sendMessage',
            'in_arcs' => array(
                'active_with_msgs',
            ),
            'out_arcs' => array(
                'active_with_msgs' => array(
                    'type' => 'SEQ',
                ),
            ),
        ),
        'active_with_msgs.setGrace' => array(
            'trigger' => 'TIME',
            'time_limit' => 'active_with_msgs.setGrace',
            'task' => 'setGraceNoPayment',
            'in_arcs' => array(
                'active_with_msgs',
            ),
            'out_arcs' => array(
                'grace' => array(
                    'type' => 'SEQ',
                ),
            ),
        ),
        'grace.setActive' => array(
            'trigger' => 'MSG',
            'message' => 'grace.deposit',
            'task' => 'setActive',
            'in_arcs' => array(
                'grace',
            ),
            'out_arcs' => array(
                'active' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'grace' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),       
        'grace.setPassiveAccum' => array(
            'trigger' => 'TIME',
            'time_limit' => 'grace.setPassiveAccum',
            'task' => 'setPassiveAccum',
            'in_arcs' => array(
                'grace',
            ),
            'out_arcs' => array(
                'passive_accum' => array(
                    'type' => 'SEQ',
                ),
            ),
        ), 
        'passive_accum.setActive' => array(
            'trigger' => 'MSG',
            'message' => 'passive_accum.deposit',
            'task' => 'setActive',
            'in_arcs' => array(
                'passive_accum',
            ),
            'out_arcs' => array(
                'active' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'passive_accum' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),   
        'passive_accum.accumulatePeriodDebt' => array(
            'trigger' => 'TIME',
            'time_limit' => 'passive_accum.accumulatePeriodDebt',
            'task' => 'accumulatePeriodDebt',
            'in_arcs' => array(
                'passive_accum',
            ),
            'out_arcs' => array(
                'passive_accum' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'passive_not_accum' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),   
        'passive_accum.setPassiveNotAccum' => array(
            'trigger' => 'AUTO',
            'task' => 'setPassiveNotAccum',
            'in_arcs' => array(
                'passive_accum',
            ),
            'out_arcs' => array(
                'passive_not_accum' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'passive_accum' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),    
        'passive_not_accum.setActive' => array(
            'trigger' => 'MSG',
            'message' => 'passive_not_accum.deposit',
            'task' => 'setActive',
            'in_arcs' => array(
                'passive_not_accum',
            ),
            'out_arcs' => array(
                'active' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'passive_not_accum' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),   
        'passive_not_accum.expropiateBalance' => array(
            'trigger' => 'TIME',
            'time_limit' => 'passive_not_accum.expropiateBalance',
            'task' => 'expropiateBalance',
            'in_arcs' => array(
                'passive_not_accum',
            ),
            'out_arcs' => array(
                'passive_not_accum' => array(
                    'type' => 'SEQ',
                ),
            ),
        ), 
        'passive_not_accum.setExpired' => array(
            'trigger' => 'TIME',
            'time_limit' => 'passive_not_accum.setExpired',
            'task' => 'setExpired',
            'in_arcs' => array(
                'passive_not_accum',
            ),
            'out_arcs' => array(
                'expired' => array(
                    'type' => 'SEQ',
                ),
            ),
        ), 
        'expired.setActive' => array(
            'trigger' => 'MSG',
            'message' => 'expired.deposit',
            'task' => 'setActive',
            'in_arcs' => array(
                'passive_not_accum',
            ),
            'out_arcs' => array(
                'active' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'ok',
                ),
                'expired' => array(
                    'type' => 'EXPLICIT_OR_SPLIT',
                    'condition' => 'error',
                ),
            ),
        ),   
        'expired.setShutdown' => array(
            'trigger' => 'TIME',
            'time_limit' => 'expired.setShutdown',
            'task' => 'setShutdown',
            'in_arcs' => array(
                'expired',
            ),
            'out_arcs' => array(
                'shutdown' => array(
                    'type' => 'SEQ',
                ),
            ),
        ),
    );

    private $_service_cost;
    private $_grace_periods;
    public $time_until_reconnection;
    private $_reconnection_cost;
    private $_case_id;
    private $_account;
    private $_accumulated_grace_periods;

    public function __construct($case_id)
    {
        $this->_case_id = $case_id;
        $this->_account = Account::getAccount($case_id);
        
        $this->_service_cost              = $this->_account->service_package->cost;
        $this->_grace_periods             = $this->_account->service_package->grace;
        $this->time_until_reconnection    = $this->_account->service_package->reconnection_time;
        $this->_reconnection_cost         = $this->_account->service_package->reconnection_cost;
        $this->_accumulated_grace_periods = $this->_account->service_package->debt_accum_limit;
    }

    public function setActive($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Will check account's balance and account's service cost."); 
        
        $enough_credit = false;
        
        switch ($this->_account->getStatus()) {
            case "idle":
                $enough_credit = ($this->_account->getBalance() >= $this->_service_cost);
                $withdrawal = $this->_service_cost;
                break;
            case "active_with_msgs":
                $enough_credit = true;
                $withdrawal = 0;
                break;
            case "grace":
                $enough_credit = ($this->_account->getBalance() >= $this->_service_cost);
                $withdrawal = $this->_service_cost;
                break;
            case "passive_accum":
                $enough_credit = ($this->_account->getBalance() >= 0);
                $withdrawal = 0;
                break;
            case "passive_not_accum":
                $enough_credit = ($this->_account->getBalance() >= 0);
                $withdrawal = $this->_service_cost;
                break;
            case "expired":
                $enough_credit = ($this->_account->getBalance() >= 0);
                $withdrawal = $this->_service_cost;
                break;
        }
        
        if ($enough_credit) {
            debug(__CLASS__.".".__FUNCTION__."() account's balance ({$this->_account->getBalance()}) ".
                                                    "is enough to cover account's reactivation."); 
            
            if ($withdrawal > 0) {
                debug(__CLASS__.".".__FUNCTION__."() Will withdraw {$withdrawal} and change status to ACTIVE!"); 
                $this->_account->withdraw($withdrawal, 'service charge');
            }
            $this->_account->setStatus('active');
            $ret = 'ok';
        } else {
            debug(__CLASS__.".".__FUNCTION__."() account's balance ({$this->_account->getBalance()}) ".
                                                    "is NOT enough to cover account's reactivation."); 
            $ret = 'error';
        }
        
        return $ret;
    }

    public function setGrace($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Will use setActive to perform service cost payment."); 
        
        $res = $this->setActive();
        
        if ($res == 'error') {
            debug(__CLASS__.".".__FUNCTION__."() Setting status to GRACE!."); 
            $this->_account->setStatus('grace');
        }
        
        return $res;
    }

    public function setGraceNoPayment($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing."); 
        debug(__CLASS__.".".__FUNCTION__."() Setting status to GRACE!."); 
        $this->_account->setStatus('grace');
        
        $res = 'ok';
        return $res;
    }

    public function setActiveWithMsgs($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");
        
        $enough_credit = ($this->_account->getBalance() >= $this->_service_cost);
        
        if ($enough_credit) {
            debug(__CLASS__.".".__FUNCTION__."() Account has enough credit, wil not send messages!."); 
            $res = 'ok';
        } else {
            debug(__CLASS__.".".__FUNCTION__."() Setting status to ACTIVE_WITH_MSGS!."); 
            $this->_account->setStatus('active_with_msgs');
            $res = 'error';
        }        
        
        return $res;
    }
    
    public function sendMessage($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");
        
        $res = 'ok';
        return $res;
    }
    
    public function setPassiveAccum($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");
        debug(__CLASS__.".".__FUNCTION__."() Setting status to PASSIVE_ACCUM!."); 
        $this->_account->setStatus('passive_accum');
        
        $res = 'ok';
        return $res;
    }
    
    public function accumulatePeriodDebt($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Withdrawing ({$this->_service_cost}) from Account");
        $this->_account->withdraw($this->_service_cost, 'service charge');
        
        $res = 'ok';
        return $res;
    }
    
    public function setPassiveNotAccum($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Checking if account accumulates debt.");
        
        if ($this->_accumulated_grace_periods > 0) {
            $res = 'error';                      
        } else {
            debug(__CLASS__.".".__FUNCTION__."() Setting status to PASSIVE_NOT_ACCUM!."); 
            $this->_account->setStatus('passive_not_accum');
            $res = 'ok';            
        }
        
        return $res;
    }
    
    public function expropiateBalance($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");
        
        $res = 'ok';
        
        $balance = $this->_account->getBalance();
        
        if ($balance > 0) {
            debug(__CLASS__.".".__FUNCTION__."() account's balance ({$balance}) will be expropiated."); 
            $this->_account->withdraw($balance, 'balance expropiation');
        } else {
            debug(__CLASS__.".".__FUNCTION__."() account's balance ({$balance}) is not expropiatable."); 
        }
        
        return $res;
    }
    
    public function setExpired($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");
        debug(__CLASS__.".".__FUNCTION__."() Setting status to EXPIRED!."); 
        $this->_account->setStatus('expired');
        
        $res = 'ok';
        return $res;
    }
    
    public function setShutdown($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");
        debug(__CLASS__.".".__FUNCTION__."() Setting status to SHUTDOWN!."); 
        $this->_account->setStatus('shutdown');
        
        $res = 'ok';
        return $res;
    }
    
    //---
    
    public function getTaskDate($period)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Period: '{$period}'"); 
        
        $res = null;
        
        switch ($period) {
            case 'active.setGrace':
                $res = strtotime("+".$this->_account->service_package->duration, time());
                break;
            case 'active.setActiveWithMsgs':
                $res = strtotime("+".$this->_account->service_package->fromActiveToMessaging, time());
                break;
            case 'active_with_msgs.sendMessage':
                $res = strtotime("+".$this->_account->service_package->messagingPeriod, time());
                break;
            case 'active_with_msgs.setGrace':
                $res = strtotime("+".$this->_account->service_package->fromMessagingToGrace, time());
                break;
            case 'grace.setPassiveAccum':
                $res = strtotime("+".$this->_account->service_package->grace, time());
                break;
            case 'passive_accum.accumulatePeriodDebt':
                $res = strtotime("+".$this->_account->service_package->duration, time());
                break;
            case 'passive_not_accum.expropiateBalance':
                $res = strtotime("+".$this->_account->service_package->fromPassiveToExpropiate, time());
                break;
            case 'passive_not_accum.setExpired':
                $res = strtotime("+".$this->_account->service_package->fromPassiveToExpired, time());
                break;
            case 'expired.setShutdown':
                $res = strtotime("+".$this->_account->service_package->fromExpiredToShutdown, time());
                break;
        }
        
        return $res;
    }
    
}
