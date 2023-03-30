<?php

namespace guayaquil\views\fulltextsearch;

use guayaquil\View;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\objects\oem\PartShortListObject;
use GuayaquilLib\objects\oem\VehicleObject;
use GuayaquilLib\Oem;

class FulltextsearchHtml extends View
{
    public function Display($tpl = 'applicabilitydetails', $view = 'view')
    {
        $catalog = $this->input->getString('c', '');
        $ssd = $this->input->getString('ssd', '');
        $partName = $this->input->getString('partName', '');
        $vid = $this->input->getString('vid');

        /** @var CatalogObject $cataloginfo */
        /** @var PartShortListObject $details */
        /** @var VehicleObject $vehicle */
        list($cataloginfo, $details, $vehicle) = $this->getOemService()->queryButch([
            Oem::getCatalogInfo($catalog, $this->getLanguage()->getLocalization(), true),
            Oem::findPartInVehicleByName($catalog, $vid, $ssd, $partName, $this->getLanguage()->getLocalization()),
            Oem::getVehicleInfo($catalog, $vid, $ssd, $this->getLanguage()->getLocalization()),
        ]);

        $this->pathway->addItem($cataloginfo->getName(), $this->createUrl2($cataloginfo));

        $vehicleLink = $this->createUrl('vehicle', '', '', [
            'c' => $cataloginfo->getCode(),
            'vid' => $vehicle->getVehicleId(),
            'ssd' => $vehicle->getSsd(),
            'checkQG' => true
        ]);

        $this->pathway->addItem($vehicle->getBrand() . ' ' . $vehicle->getName(), $vehicleLink);
        $this->pathway->addItem(htmlspecialchars($partName));

        $this->catalog = $cataloginfo;
        $this->details = $details;
        $this->vehicle = $vehicle;
        $this->partName = $partName;
        $this->supportApplicability = $cataloginfo->getDetailApplicabilityFeature() != null;

        parent::Display('fulltextsearch', 'view');
    }
}