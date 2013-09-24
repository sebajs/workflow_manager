<?php

/*
 * TODO: Cobrar cargo de reconexión.
 * TODO: Separar estados públicos e internos.
 * TODO: No renovar las fechas, usar pasaje a grace como base.
 * TODO: De grace a active mueve la fecha 1 período desde ese momento.
 */

class PrepaidLifecycleWorkflow extends Workflow
{
    private $_case_id;
    private $_account;

    public function __construct($case_id)
    {
        $this->_case_id = $case_id;
        $this->_account = Account::getAccount($case_id);

        $this->setConfig();
    }

    public function checkBalanceForActive($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Will check account's balance and account's service cost.");

        $res           = 'error';
        $withdrawal    = 0;
        $enough_credit = false;

        switch ($this->_account->getStatus()) {
            case "idle":
                $withdrawal    = $this->_account->service_package->cost;
                $enough_credit = ($this->_account->getBalance() >= $withdrawal);
                break;
            case "active_with_msgs":
                $withdrawal    = 0;
                $enough_credit = true;
                break;
            case "grace":
                $withdrawal    = $this->_account->service_package->cost;
                $enough_credit = ($this->_account->getBalance() >= $withdrawal);
                break;
            case "grace_with_msgs":
                $withdrawal    = $this->_account->service_package->cost;
                $enough_credit = ($this->_account->getBalance() >= $withdrawal);
                break;
            case "passive_accum":
                $withdrawal    = $this->_account->service_package->cost;
                $enough_credit = ($this->_account->getBalance() >= $withdrawal);
                break;
            case "passive_not_accum":
                $withdrawal    = $this->_account->service_package->cost;
                $enough_credit = ($this->_account->getBalance() >= $withdrawal);
                break;
            case "expired":
                $withdrawal    = $this->_account->service_package->cost;
                $enough_credit = ($this->_account->getBalance() >= $withdrawal);
                break;
        }

        if ($enough_credit) {
            debug(__CLASS__.".".__FUNCTION__."() account's balance ({$this->_account->getBalance()}) ".
                "is enough to cover account's reactivation.");

            debug(__CLASS__.".".__FUNCTION__."() Will withdraw {$withdrawal}.");
            if ($withdrawal > 0) {
                $this->_account->chargeService();
            }
            $this->setActive();
            $res = 'ok';
        } else {
            debug(__CLASS__.".".__FUNCTION__."() account's balance ({$this->_account->getBalance()}) ".
                "is NOT enough to cover account's reactivation.");
        }

        return $res;
    }

    public function checkDeposit($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");

        $transactions = $this->_account->getLastTransactions();

        $last_trans = array_pop($transactions);

        if ($last_trans['type'] == 'deposit') {
            $res = 'active';
        } else {
            $prev_trans = array_pop($transactions);
            while ($prev_trans['type'] != 'deposit') {
                $prev_trans = array_pop($transactions);
            }

            $res = $prev_trans['status'];
            $this->_account->setStatus($res);
        }

        return $res;
    }

    public function setActive($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");
        debug(__CLASS__.".".__FUNCTION__."() Setting status to ACTIVE!.");
        $this->_account->setStatus('active');

        $res = 'ok';
        return $res;
    }

    public function setGrace($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Will perform service cost payment.");

        if ($this->_account->getBalance() >= $this->_account->service_package->cost) {
            $this->_account->chargeService();
            $res = 'ok';
        } else {
            debug(__CLASS__.".".__FUNCTION__."() Setting status to GRACE!.");
            $this->_account->setStatus('grace');
            $res = 'error';
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
        
        $enough_credit = ($this->_account->getBalance() >= $this->_account->service_package->cost);
        
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

    public function setGraceWithMsgs($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");

        debug(__CLASS__.".".__FUNCTION__."() Setting status to GRACE_WITH_MSGS!.");
        $this->_account->setStatus('grace_with_msgs');
        $res = 'error';

        return $res;
    }
    
    public function sendMessage($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");
        
        $res = 'ok';
        return $res;
    }
    
    public function setPassive($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing.");

        if ($this->_account->service_package->debt_accum_limit > 0) {

            debug(__CLASS__.".".__FUNCTION__."() Setting status to PASSIVE_ACCUM!.");
            $this->_account->setStatus('passive_accum');
            $res = 'accumulate';

        } else {

            debug(__CLASS__.".".__FUNCTION__."() Setting status to PASSIVE_NOT_ACCUM!.");
            $this->_account->setStatus('passive_not_accum');
            $res = '!accumulate';

        }

        return $res;
    }
    
    public function setPassiveNotAccum($params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Checking if account accumulates debt.");
        
        if ($this->_account->getDebtAccumulations() < $this->_account->service_package->debt_accum_limit) {
            $this->_account->chargeService();
            $this->_account->incrementDebtAccumulations();
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

        $this->_account->expropiateBalance();
        
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
    
    public function getTaskDate($period)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Period: '{$period}'"); 
        
        $res = null;
        
        switch ($period) {
            case 'active.setActiveWithMsgs':
                // El tiempo debe contarse desde que se puso la cuenta en active.
                $time = $this->_account->getLastStatusSetting('active');
                $time = ($time !== null) ? strtotime($time) : time();
                $period = $this->_account->service_package->fromActiveToMessaging;
                if ($period !== null) {
                    $res = strtotime("+".$period, $time);
                }
                break;
            case 'active.setGrace':
                // El tiempo debe contarse desde que se puso la cuenta en active.
                $time = $this->_account->getLastStatusSetting('active');
                $time = ($time !== null) ? strtotime($time) : time();
                $res = strtotime("+".$this->_account->service_package->duration, $time);
                break;
            case 'active_with_msgs.sendMessage':
                // El tiempo debe contarse desde que se puso la cuenta en active_with_msgs.
                $time = $this->_account->getLastStatusSetting('active_with_msgs');
                $time = ($time !== null) ? strtotime($time) : time();
                $res = strtotime("+".$this->_account->service_package->activeMessagingPeriod, $time);
                break;
            case 'active_with_msgs.setGrace':
                // El tiempo debe contarse desde que se puso la cuenta en active.
                $time = $this->_account->getLastStatusSetting('active');
                $time = ($time !== null) ? strtotime($time) : time();
                $res = strtotime("+".$this->_account->service_package->duration, $time);
                break;
            case 'grace.setGraceWithMsgs':
                // El tiempo debe contarse desde que se puso la cuenta en grace.
                $time = $this->_account->getLastStatusSetting('grace');
                $time = ($time !== null) ? strtotime($time) : time();
                $period = $this->_account->service_package->fromGraceToMessaging;
                if ($period !== null) {
                    $res = strtotime("+".$period, $time);
                }
                break;
            case 'grace.setPassive':
                // El tiempo debe contarse desde que se puso la cuenta en grace.
                $time = $this->_account->getLastStatusSetting('grace');
                $time = ($time !== null) ? strtotime($time) : time();
                $res = strtotime("+".$this->_account->service_package->grace, $time);
                break;
            case 'grace_with_msgs.setPassive':
                // El tiempo debe contarse desde que se puso la cuenta en grace.
                $time = $this->_account->getLastStatusSetting('grace');
                $time = ($time !== null) ? strtotime($time) : time();
                $res = strtotime("+".$this->_account->service_package->grace, $time);
                break;
            case 'grace_with_msgs.sendMessage':
                // El tiempo debe contarse desde que se puso la cuenta en grace_with_msgs.
                $time = $this->_account->getLastStatusSetting('grace_with_msgs');
                $time = ($time !== null) ? strtotime($time) : time();
                $res = strtotime("+".$this->_account->service_package->graceMessagingPeriod, $time);
                break;
            case 'passive_accum.setPassiveNotAccum':
                // El tiempo debe contarse N veces desde que se puso la cuenta en grace.
                $time = $this->_account->getLastStatusSetting('grace');
                $time = ($time !== null) ? strtotime($time) : time();
                $i = 0;
                while ($i <= $this->_account->getDebtAccumulations()) {
                    $time = strtotime("+".$this->_account->service_package->duration, $time);
                    $i++;
                }
                $res = $time;
                break;
            case 'passive_not_accum.expropiateBalance':
                if (!$this->_account->isExpropiated()) {
                    // El tiempo debe contarse desde que se puso la cuenta en grace.
                    $time = $this->_account->getLastStatusSetting('grace');
                    $time = ($time !== null) ? strtotime($time) : time();
                    $res = strtotime("+".$this->_account->service_package->fromGraceToExpropiate, $time);
                }
                break;
            case 'passive_not_accum.setExpired':
                // El tiempo debe contarse desde que se puso la cuenta en grace.
                $time = $this->_account->getLastStatusSetting('grace');
                $time = ($time !== null) ? strtotime($time) : time();
                $res = strtotime("+".$this->_account->service_package->fromGraceToExpired, $time);
                break;
            case 'expired.setShutdown':
                // El tiempo debe contarse desde que se puso la cuenta en grace.
                $time = $this->_account->getLastStatusSetting('grace');
                $time = ($time !== null) ? strtotime($time) : time();
                $res = strtotime("+".$this->_account->service_package->fromExpiredToShutdown, $time);
                break;
        }

        return $res;
    }

    private function setConfig()
    {
        $this->places = array();
        $this->places['idle'] = array(
                                        'out_arcs' => array(
                                            'idle.checkBalanceForActive',
                                        ),
                                        'description' => array(
                                            'hasService' => false,
                                        ),
                                     );
        $this->places['active'] = array(
                                        'out_arcs' => array(
                                            'active.checkBalanceForActive',
                                            'active.setGrace',
                                            'active.setActiveWithMsgs',
                                        ),
                                        'description' => array(
                                            'hasService' => true,
                                        ),
                                     );
        $this->places['active_with_msgs'] = array(
                                        'out_arcs' => array(
                                            'active_with_msgs.sendMessage',
                                            'active_with_msgs.checkBalanceForActive',
                                            'active_with_msgs.setGrace',
                                        ),
                                        'description' => array(
                                            'hasService' => true,
                                        ),
                                     );
        $this->places['grace'] = array(
                                        'out_arcs' => array(
                                            'grace.checkBalanceForActive',
                                            'grace.setPassive',
                                            'grace.setGraceWithMsgs',
                                        ),
                                        'description' => array(
                                            'hasService' => true,
                                        ),
                                     );
        $this->places['grace_with_msgs'] = array(
                                        'out_arcs' => array(
                                            'grace_with_msgs.checkBalanceForActive',
                                            'grace_with_msgs.setPassive',
                                            'grace_with_msgs.sendMessage',
                                        ),
                                        'description' => array(
                                            'hasService' => true,
                                        ),
                                     );
        $this->places['passive_accum'] = array(
                                        'out_arcs' => array(
                                            'passive_accum.checkBalanceForActive',
                                            'passive_accum.setPassiveNotAccum',
                                        ),
                                        'description' => array(
                                            'hasService' => false,
                                        ),
                                     );
        $this->places['passive_not_accum'] = array(
                                        'out_arcs' => array(
                                            'passive_not_accum.checkBalanceForActive',
                                            'passive_not_accum.expropiateBalance',
                                            'passive_not_accum.setExpired',
                                        ),
                                        'description' => array(
                                            'hasService' => false,
                                        ),
                                     );
        $this->places['expired'] = array(
                                        'out_arcs' => array(
                                            'expired.checkBalanceForActive',
                                            'expired.setShutdown',
                                        ),
                                        'description' => array(
                                            'hasService' => false,
                                        ),
                                     );
        $this->places['shutdown'] = array(
                                        'out_arcs' => array(),
                                        'description' => array(
                                            'hasService' => false,
                                        ),
                                     );

        $this->transitions = array(
            'idle.checkBalanceForActive' => array(
                'trigger' => 'MSG',
                'message' => 'idle.deposit',
                'task' => 'checkBalanceForActive',
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
            'active.checkDeposit' => array(
                'trigger' => 'MSG',
                'message' => 'active.deposit',
                'task' => 'checkDeposit',
                'in_arcs' => array(
                    'active',
                ),
                'out_arcs' => array(
                    'active' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'active',
                    ),
                    'grace' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'grace',
                    ),
                    'passive_accum' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'passive_accum',
                    ),
                    'passive_not_accum' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'passive_not_accum',
                    ),
                    'expired' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'expired',
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
            'active_with_msgs.checkBalanceForActive' => array(
                'trigger' => 'MSG',
                'message' => 'active_with_msgs.deposit',
                'task' => 'checkBalanceForActive',
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
            'grace.setPassive' => array(
                'trigger' => 'TIME',
                'time_limit' => 'grace.setPassive',
                'task' => 'setPassive',
                'in_arcs' => array(
                    'grace',
                ),
                'out_arcs' => array(
                    'passive_accum' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'accumulate',
                    ),
                    'passive_not_accum' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => '!accumulate',
                    ),
                ),
            ),
            'grace.checkBalanceForActive' => array(
                'trigger' => 'MSG',
                'message' => 'grace.deposit',
                'task' => 'checkBalanceForActive',
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
            'grace.setGraceWithMsgs' => array(
                'trigger' => 'TIME',
                'time_limit' => 'grace.setGraceWithMsgs',
                'task' => 'setGraceWithMsgs',
                'in_arcs' => array(
                    'grace',
                ),
                'out_arcs' => array(
                    'grace_with_msgs' => array(
                        'type' => 'SEQ',
                    ),
                ),
            ),
            'grace_with_msgs.setPassive' => array(
                'trigger' => 'TIME',
                'time_limit' => 'grace_with_msgs.setPassive',
                'task' => 'setPassive',
                'in_arcs' => array(
                    'grace_with_msgs',
                ),
                'out_arcs' => array(
                    'passive_accum' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'accumulate',
                    ),
                    'passive_not_accum' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => '!accumulate',
                    ),
                ),
            ),
            'grace_with_msgs.sendMessage' => array(
                'trigger' => 'TIME',
                'time_limit' => 'grace_with_msgs.sendMessage',
                'task' => 'sendMessage',
                'in_arcs' => array(
                    'grace_with_msgs',
                ),
                'out_arcs' => array(
                    'grace_with_msgs' => array(
                        'type' => 'SEQ',
                    ),
                ),
            ),
            'grace_with_msgs.checkBalanceForActive' => array(
                'trigger' => 'MSG',
                'message' => 'grace_with_msgs.deposit',
                'task' => 'checkBalanceForActive',
                'in_arcs' => array(
                    'grace_with_msgs',
                ),
                'out_arcs' => array(
                    'active' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'ok',
                    ),
                    'grace_with_msgs' => array(
                        'type' => 'EXPLICIT_OR_SPLIT',
                        'condition' => 'error',
                    ),
                ),
            ),
            'passive_accum.checkBalanceForActive' => array(
                'trigger' => 'MSG',
                'message' => 'passive_accum.deposit',
                'task' => 'checkBalanceForActive',
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
            'passive_accum.setPassiveNotAccum' => array(
                'trigger' => 'TIME',
                'time_limit' => 'passive_accum.setPassiveNotAccum',
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
            'passive_not_accum.checkBalanceForActive' => array(
                'trigger' => 'MSG',
                'message' => 'passive_not_accum.deposit',
                'task' => 'checkBalanceForActive',
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
            'expired.checkBalanceForActive' => array(
                'trigger' => 'MSG',
                'message' => 'expired.deposit',
                'task' => 'checkBalanceForActive',
                'in_arcs' => array(
                    'expired',
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

    }
    
}
