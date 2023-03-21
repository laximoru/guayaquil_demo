<?php

namespace guayaquil\views\aftermarket;


use guayaquil\View;
use GuayaquilLib\objects\am\PartListObject;

/**
 * @property string $oem
 * @property string $brand
 * @property false|string[] $options
 * @property array|mixed $replacementtypes
 * @property PartListObject[] $details
 */
class AftermarketHtml extends View
{
    public function Display($tpl = 'aftermarket', $view = 'view')
    {
        $view = $this->input->getString('view', 'view');

        switch ($view) {
            case 'view':
                $this->displayAftermarket();
                parent::Display($tpl, $view);
                break;
            case 'manufacturerinfo':
                $manufacturerid = $this->input->getInt('manufacturerid');
                $this->manufacturerInfo = $this->getAmService()->getManufacturerInfo($manufacturerid, $this->getLanguage()->getLocalization());
                $this->rawFormat = true;
                parent::Display('aftermarket', 'manufacturerInfo');
                break;
            case 'findOem':
                $this->displayAftermarket();
                parent::Display($tpl, 'view');
                break;
        }
    }

    public function displayAftermarket()
    {
        $oem = $this->input->getString('oem');
        $brand = $this->input->getString('brand');
        $detailId = $this->input->getString('detail_id');
        $input = $this->input->getArray();
        $options = isset($input['options']) && is_array($input['options'])  ? $input['options'] : ['crosses','weights','names','properties','images'];
        $replacementtypes = isset($input['replacementtypes']) && is_array($input['replacementtypes']) ? $input['replacementtypes'] : ['synonym', 'PartOfTheWhole', 'Replacement', 'Duplicate', 'Bidirectional', 'Tuning'];
        $data = [];

        if ($oem || $detailId) {
            if ($detailId) {
                $data = $this->getAmService()->findPart($detailId, $options, $replacementtypes, $this->getLanguage()->getLocalization());
                $oem = $data->getOems()[0]->getOem();
                $brand = $data->getOems()[0]->getManufacturer();
            } else {
                $data = $this->getAmService()->findOem($oem, $brand, $options, $replacementtypes, $this->getLanguage()->getLocalization());
            }
        }

        $this->pathway->addItem($this->getLanguage()->t('amOemSearch'), '');

        $this->oem = $oem;
        $this->brand = $brand;
        $this->options = $options;
        $this->replacementtypes = $replacementtypes;
        $this->details = $data;
    }
}