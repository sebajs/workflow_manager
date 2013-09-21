<?php

spl_autoload_register('wfAutoload');

function wfAutoload($sClassName)
{
    //class directories
    $aClassPath = array('./classes/');

    //for each directory
    foreach($aClassPath as $sDir) {
        //see if the file exists
        if(file_exists($sDir.$sClassName.'.php')) {
            require_once($sDir.$sClassName.'.php');
            return;
        }
    }
}


function debug($data, $level = 0){
    echo date("ymd H:i:s")." : ".$data."\n";
}
