<?php

/*
 * TODO: Hacer envío de mensajes en grace.
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
        $enough_credit = false;
        $withdrawal    = 0;

        switch ($this->_account->getStatus()) {
            case "idle":
                $enough_credit = ($this->_account->getBalance() >= $this->_account->service_package->cost);
                $withdrawal    = $this->_account->service_package->cost;
                break;
            case "active_with_msgs":
                $enough_credit = true;
                $withdrawal    = 0;
                break;
            case "grace":
                $enough_credit = ($this->_account->getBalance() >= $this->_account->service_package->cost);
                $withdrawal    = $this->_account->service_package->cost;
                break;
            case "passive_accum":
                $enough_credit = ($this->_account->getBalance() >= $this->_account->service_package->cost);
                $withdrawal    = $this->_account->service_package->cost;
                break;
            case "passive_not_accum":
                $enough_credit = ($this->_account->getBalance() >= $this->_account->service_package->cost);
                $withdrawal    = $this->_account->service_package->cost;
                break;
            case "expired":
                $enough_credit = ($this->_account->getBalance() >= $this->_account->service_package->cost);
                $withdrawal    = $this->_account->service_package->cost;
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
        
        $balance = $this->_account->getBalance();
        
        if ($balance > 0) {
            debug(__CLASS__.".".__FUNCTION__."() account's balance ({$balance}) will be expropiated."); 
            $this->_account->expropiateBalance();
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
    
    public function getTaskDate($period)
    {
        debug(__CLASS__.".".__FUNCTION__."() Executing. Period: '{$period}'"); 
        
        $res = null;
        
        switch ($period) {
            case 'active.setGrace':
                $res = strtotime("+".$this->_account->service_package->duration, time());
                break;
            case 'active.setActiveWithMsgs':
                $period = $this->_account->service_package->fromActiveToMessaging;
                if ($period !== null) {
                    $res = strtotime("+".$period, time());
                }
                break;
            case 'active_with_msgs.sendMessage':
                $res = strtotime("+".$this->_account->service_package->messagingPeriod, time());
                break;
            case 'active_with_msgs.setGrace':
                $res = strtotime("+".$this->_account->service_package->fromMessagingToGrace, time());
                break;
            case 'grace.setPassive':
                $res = strtotime("+".$this->_account->service_package->grace, time());
                break;
            case 'passive_accum.setPassiveNotAccum':
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
