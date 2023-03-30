<?php

namespace guayaquil\views\catalog;

use guayaquil\View;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\Oem;

/**
 * @property CatalogObject $cataloginfo
 * @property array $wizardFields
 */
class CatalogHtml extends View
{
    public function Display($tpl = 'catalog', $view = 'view')
    {
        $catalog = $this->input->getString('c');
        $ssd = $this->input->getString('ssd', '');
        $spi2 = $this->input->getString('spi2', '');

        $requests = [
            Oem::getCatalogInfo($catalog, $this->getLanguage()->getLocalization(), true)
        ];

        if ($spi2 == 't') {
            $requests[] = Oem::getWizard2($catalog, $ssd, $this->getLanguage()->getLocalization());
        }

        $data = $this->getOemService()->queryButch($requests);
        /** @var CatalogObject $cataloginfo */
        $cataloginfo = $data[0];
        $wizardFields = isset($data[1]) ? $data[1]->getSteps() : [];

        $this->pathway->addItem($cataloginfo->getName(), $this->createUrl2($cataloginfo));

        $this->cataloginfo = $cataloginfo;
        $this->wizardFields = $wizardFields;

        parent::Display($tpl, $view);
    }

}



