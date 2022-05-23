<?php

namespace guayaquil\views\catalogs;

use guayaquil\Config;
use guayaquil\View;

/**
 * @property string frameExample
 * @property string vinExample
 * @property array catalogs
 * @property int columns
 * @property float elemInRow
 * @property int elemCount
 * @property int rest
 * @property string oemExample
 */
class CatalogsHtml extends View
{

    public function Display($tpl = 'catalogs', $view = 'view')
    {
        $catalogs = $this->getOemService()->listCatalogs();
        $examples = $catalogs->getExamples();

        $columns = $this->config->catalogColumns;
        $elemCount = count($catalogs->getCatalogs() ?: []);
        $elemInRow = floor(($elemCount) / $columns);
        $rest = $elemCount % $columns;

        $this->frameExample = $examples[1];
        $this->vinExample = $examples[0];
        $this->catalogs = $catalogs->getCatalogs();
        $this->columns = $columns;
        $this->elemInRow = $elemInRow;
        $this->elemCount = $elemCount;
        $this->rest = $rest;
        $this->oemExample = !empty($this->config->oemExample) ? $this->config->oemExample : '0913128000';

        parent::Display($tpl, $view);
    }
}




