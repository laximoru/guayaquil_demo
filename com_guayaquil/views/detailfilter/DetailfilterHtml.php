<?php

namespace guayaquil\views\detailfilter;

use guayaquil\View;
use GuayaquilLib\objects\oem\FilterObject;

/**
 * @property string oem
 * @property string brand
 * @property array from
 * @property string fromTask
 * @property string fromCatalogTask
 * @property FilterObject $filters
 */
class DetailfilterHtml extends View
{
    public function Display($tpl = 'detailfilter', $view = 'view')
    {
        $catalogCode = $this->input->getString('c');
        $ssd = $this->input->getString('ssd', '');
        $f = $this->input->getString('f');
        $vid = $this->input->getString('vid');
        $uid = $this->input->getString('uid');
        $did = $this->input->getString('did');

        $filters = $this->getOemService()->getFilterByPart($catalogCode, $vid, $ssd, $uid, $did, $f, $this->getLanguage()->getLocalization());

        $fromTask = $this->input->getString('fromTask');
        $fromCatalogTask = $this->input->getString('fromCatalogTask');

        $this->filters = $filters;
        $this->oem = $this->input->getString('oem');
        $this->brand = $this->input->getString('brand');
        $this->from = $this->input->getArray();
        $this->fromTask = $fromTask;
        $this->fromCatalogTask = $fromCatalogTask;

        parent::Display($tpl, $view);
    }

}