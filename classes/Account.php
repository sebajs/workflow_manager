<?php

class Account extends Show
{
    private   $id;
    protected $balance            = 0;
    private   $debt_accumulations = 0;
    protected $status             = 'idle';
    public    $service_package;
    private   $transactions       = array();
    
    public function __construct($account_id)
    {
        $this->id = $account_id;
        $this->debt_accumulations = 0;

        $this->service_package                    = new stdClass();
        $this->service_package->duration          = "30 seconds";
        $this->service_package->grace             = "10 seconds";
        
        $this->service_package->fromActiveToMessaging   = null;//"20 seconds";
        $this->service_package->fromMessagingToGrace    = null;//"10 seconds";
        $this->service_package->messagingPeriod         = "3 seconds";
        $this->service_package->fromPassiveToExpropiate = "20 seconds";
        $this->service_package->fromPassiveToExpired    = "30 seconds";
        $this->service_package->fromExpiredToShutdown   = "30 seconds";
    
        $this->service_package->cost              = 50;
        $this->service_package->reconnection_time = 90;
        $this->service_package->reconnection_cost = 25;
        $this->service_package->debt_accum_limit  = 2;

        $this->loadFromFile();
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getBalance()
    {
        return $this->balance;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->saveToFile();
    }
    
    public function getDebtAccumulations()
    {
        return $this->debt_accumulations;
    }
    
    public function incrementDebtAccumulations()
    {
        $this->debt_accumulations++;
        $this->saveToFile();
    }

    public function chargeService()
    {
        debug(__CLASS__.".".__FUNCTION__."() Withdrawing ({$this->service_package->cost}) from Account");
        $this->withdraw($this->service_package->cost, 'Service Charge');
    }

    public function expropiateBalance()
    {
        debug(__CLASS__.".".__FUNCTION__."() Withdrawing ({$this->balance}) from Account");
        $this->withdraw($this->balance, 'Balance Expropiation');
    }

    public function deposit($amount, $comment = null)
    {
        $this->balance += $amount;
        $this->addTransaction('deposit', $amount, $comment);
        $this->saveToFile();
    }
    
    public function withdraw($amount, $comment = null)
    {
        $this->balance -= $amount;
        $this->addTransaction('withdraw', $amount, $comment);
        $this->saveToFile();
    }

    private function addTransaction($type, $amount, $comment)
    {
        $this->transactions[] = array(
            'date'    => date('Y-m-d H:i:s'),
            'type'    => $type,
            'amount'  => $amount,
            'comment' => $comment,
            'status'  => $this->status,
        );
    }

    public function getLastTransactions()
    {
        return $this->transactions;
    }
    
    private function loadFromFile()
    {
        $filename = './data/'.$this->id.'.acc';
        if (file_exists($filename)) {
            $data = unserialize(file_get_contents($filename));
            
            $this->balance      = $data->balance;
            $this->status       = $data->status;
            $this->transactions = $data->transactions;
            
            $this->service_package->duration          = $data->service_package->duration;
            $this->service_package->cost              = $data->service_package->cost;
            $this->service_package->grace             = $data->service_package->grace;
            $this->service_package->reconnection_time = $data->service_package->reconnection_time;
            $this->service_package->reconnection_cost = $data->service_package->reconnection_cost;
            $this->service_package->debt_accum_limit  = $data->service_package->debt_accum_limit;
            $this->debt_accumulations                 = $data->debt_accumulations;
            return true;
        } else {
            return false;
        }
    }
    
    private function saveToFile()
    {
        $filename = './data/'.$this->id.'.acc';
        file_put_contents($filename, serialize($this));
        chmod($filename, 0666);
    }
    
    public static function getAccount($account_id)
    {
        $account = new Account($account_id);
        
        $account->loadFromFile();
        
        return $account;
    }
    
}
