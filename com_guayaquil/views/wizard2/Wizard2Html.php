<?php

namespace guayaquil\views\wizard2;

use guayaquil\View;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\Oem;


class Wizard2Html extends View
{
    public function Display($tpl = 'wizard2', $view = 'view')
    {
        $catalog = $this->input->getString('c', '');
        $ssd = $this->input->getString('ssd', '');

        $data = $this->getOemService()->queryButch([
            Oem::getCatalogInfo($catalog, $this->getLanguage()->getLocalization(), true),
            Oem::getWizard2($catalog, $ssd, $this->getLanguage()->getLocalization()),
        ]);

        if ($data) {
            $wizard = $data[1]->getSteps();
            /** @var CatalogObject $catalogInfo */
            $catalogInfo = $data[0];

            $this->pathway->addItem($catalogInfo->getName(), $this->createUrl2($catalogInfo));
            $this->pathway->addItem($this->getLanguage()->t('findByWizard2'));

            $this->ssd = $ssd;
            $this->wizard = $wizard;
            $this->cataloginfo = $catalogInfo;
            $this->c = $catalogInfo->getCode();
        }

        parent::Display($tpl, $view);
    }
}
