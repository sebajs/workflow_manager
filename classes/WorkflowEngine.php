<?php

class WorkflowEngine
{
    /**
     * @var TaskScheduler
     */
    private $_task_scheduler;


    /**
     * @var WorkflowCase
     */
    private $_case;

    /**
     * @param TaskScheduler $task_scheduler
     */
    public function __construct($task_scheduler)
    {
        $this->_task_scheduler = $task_scheduler;
    }

    /**
     * @param WorkflowCase $case
     */
    public function setCase($case)
    {
        $this->_case = $case;
    }

    /**
     * @param string $message
     * @param array null $task_params
     */
    public function sendMessage($message, $task_params = null)
    {
        debug(__CLASS__.".".__FUNCTION__."() Received Message '{$message}'. Will check if CASE allows it.");
        foreach ($this->_case->workflow->getTransitions() as $transition_name => $transition) {
            
            if ($transition['trigger'] == 'MSG' && $message == $transition['message']) {
                debug(__CLASS__.".".__FUNCTION__."() Message '{$message}' allowed in this PLACE. Will execute transition '{$transition_name}'.");
                $result = $this->execute($transition_name, $task_params);
                if ($result) {
                    break;
                }
            } else {
                debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' does not apply.");
            }
            
        }
        debug(__CLASS__.".".__FUNCTION__."() Ended.");
    }

    /**
     * @param string $transition_name
     * @param array null $transition_params
     * @param int $level
     *
     * @return bool
     */
    public function execute($transition_name, $transition_params = null, $level = 1)
    {
        $transition = $this->_case->workflow->getTransitions()[$transition_name];
        
        debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' checking IN_ARC(s). Level: {$level}");
        // Se verifica que la "transition" esté habilitada para ejecutarse. Esto es, que tenga un "token" en cada "place" de entrada.
        $original_places = array();
        $enabled = true;
        foreach ($transition['in_arcs'] as $place) {
            if ($this->_case->isTokenAtPlace($place)) {
                $original_places[] = $place;
            } else {
                $enabled = false;
            }
        }
        
        // Si la "transition" está habilitada, entonces se ejecuta la "task" configurada y 
        // se asignan "tokens" a los "places" que correspondan.
        if ($enabled) {
            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' has all prerequisites to run. Level: {$level}");
            
            // Se ejecuta la "task".
            $result = null;
            
            if (!empty($transition['task'])) {
                debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' has task '{$transition['task']}' to run. Level: {$level}");
                $result = $this->_case->workflow->{$transition['task']}($transition_params);
            } else {
                debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' has NO task to run. Level: {$level}");                
            }
            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' task result: '{$result}'. Level: {$level}");     
            
            // Se eliminan los "tokens" de los "places" de entrada.
            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' cleaning all IN_ARC(s) token(s). Level: {$level}"); 
            foreach ($transition['in_arcs'] as $place) {
                $this->_case->clearTokenAtPlace($place);
            }

            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' finding OUT_ARC(s) where to set the token(s). Level: {$level}");
            // Se determinan los "places" a donde van a asignarse los nuevos "tokens".
            $new_tokens = array();
            $time = date('YmdHis');
            foreach ($transition['out_arcs'] as $destination_place => $condition) {
                debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' OUT_ARC '{$destination_place}' of type '{$condition['type']}'. Level: {$level}"); 
                
                switch ($condition['type']) {
                    case 'EXPLICIT_OR_SPLIT':
                        if ($condition['condition'] == $result) {
                            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' OUT_ARC '{$destination_place}' applies! Level: {$level}"); 
                            
                            if (!$this->_case->isTokenAtPlace($destination_place)) {
                                debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' setting token in '{$destination_place}' PLACE. Level: {$level}");
                                $this->_case->setTokenAtPlace($destination_place, $time);
                            }
                            
                            $new_tokens[$destination_place] = $this->_case->getTokenAtPlace($destination_place);
                        } else {
                            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' OUT_ARC '{$destination_place}' does not apply. Level: {$level}"); 
                        }
                        break;
                    case 'AND_SPLIT':
                    case 'SEQ':
                    default:
                        debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' OUT_ARC '{$destination_place}' type always applies! Level: {$level}"); 
                            
                        if (!$this->_case->isTokenAtPlace($destination_place)) {
                            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' setting token in '{$destination_place}' PLACE. Level: {$level}"); 
                            $this->_case->setTokenAtPlace($destination_place, $time);
                        }
                        $new_tokens[$destination_place] = $this->_case->getTokenAtPlace($destination_place);
                        break;
                }
                
            }
                        
            // Se ejecutan las "tasks" automáticas que dependen de los nuevos "places" con "tokens" y 
            // se programan las "tasks" disparadas por tiempo.
            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' Will now execute AUTO & TIME tasks . Level: {$level}");
            foreach ($new_tokens as $place_name => $time) {
                $place = $this->_case->workflow->getPlaces()[$place_name];
                
                if (is_array($place['out_arcs'])) {
                    foreach ($place['out_arcs'] as $next_transition_name) {
                        $transition = $this->_case->workflow->getTransitions()[$next_transition_name];
                        
                        switch($transition['trigger']){
                            // Se ejecutan las "tasks" automáticas de forma recursiva.
                            case 'AUTO':
                                debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' found AUTO task '{$next_transition_name}'. Level: {$level}");
                                $this->execute($next_transition_name, $transition['params'], $level+1);
                                break;
                            // Se programan las nuevas "transitions" disparadas por tiempo.
                            // Aclaración: No se reprograman "transitions" que aún estuvieran programadas y no se hayan ejecutado todavía
                            //             (eso lo maneja el task_scheduler).
                            case 'TIME':
                                debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' Found TIME task '{$next_transition_name}'. Level: {$level}");

                                $time_limit = $this->_case->workflow->getTaskDate($transition['time_limit']);
                                if ($time_limit !== null) {
                                    $this->_task_scheduler->putTask(date('YmdHis', $time_limit),  $next_transition_name, $this->_case->getId());
                                }
                                break;
                        }
                    }
                }
            }
            
            // Se desprograman todas las "transitions" de "places" que quedaron sin "tokens" luego de la ejecución 
            // porque ya no tienen sentido de existir.
            foreach($original_places as $orig_place){
                if(empty($new_tokens[$orig_place])){
                    $this->_task_scheduler->clearTasks($orig_place, $this->_case->getId());
                }
            }

        } else {
            
            debug(__CLASS__.".".__FUNCTION__."() Transition '{$transition_name}' is disabled. Level: {$level}");
            
        }
        
        $this->_case->save();
        
        // Se devuelve el estado de ejecución de la "transition".
        return $enabled;
    }
}
