<?php

class Show
{
    public function pick($params)
    {
        $pick = new stdClass();
        
        if (is_array($params)) {
            foreach ($params as $param) {
                $pick->$param = $this->$param;
            }
        }
        
        return $pick;
    }
}
