<?php

namespace guayaquil\modules\pathway;


use stdClass;

class Pathway
{
    protected $pathway = array();

    public function getPathway()
    {
        return $this->pathway;
    }

    public function addItem(string $name, string $link = '')
    {
        $this->pathway[] = $this->createItem($name, $link);
    }

    protected function createItem(string $name, string $link)
    {
        $item         = new stdClass;
        $item -> name = html_entity_decode($name, ENT_COMPAT, 'UTF-8');
        $item -> link = $link;

        return $item;
    }
}