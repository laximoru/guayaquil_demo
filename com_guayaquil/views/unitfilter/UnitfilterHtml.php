<?php

namespace guayaquil\views\unitfilter;

use guayaquil\View;
use GuayaquilLib\Oem;

/**
 * @property array filter_data
 * @property array from
 * @property string fromTask
 */
class UnitfilterHtml extends View
{
    public function Display($tpl = 'unitfilter', $view = 'view')
    {
        $catalogCode = $this->input->getString('c');
        $ssd = $this->input->getString('ssd');
        $f = $this->input->getString('f');
        $vid = $this->input->getString('vid');
        $uid = $this->input->getString('uid');

        list($filters, $unit) = $this->getOemService()->queryButch([
            Oem::getFilterByUnit($catalogCode, $vid, $ssd, $uid, $f, $this->getLanguage()->getLocalization()),
            Oem::getUnitInfo($catalogCode, $ssd, $uid, $this->getLanguage()->getLocalization()),
        ]);

        $this->filter_data = $filters;
        $this->unit = $unit;
        $this->from = $this->input->getArray();
        $this->fromTask = $this->input->getString('fromTask');

        parent::Display($tpl, $view);
    }
}