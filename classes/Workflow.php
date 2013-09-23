<?php

class Workflow
{
    protected $places = array();
    protected $transitions = array();

    public function getPlaces()
    {
        return $this->places;
    }

    public function getTransitions()
    {
        return $this->transitions;
    }


}
