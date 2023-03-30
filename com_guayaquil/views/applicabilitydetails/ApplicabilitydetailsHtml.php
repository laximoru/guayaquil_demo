<?php

namespace guayaquil\views\applicabilitydetails;

use guayaquil\View;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\objects\oem\QuickDetailListObject;
use GuayaquilLib\objects\oem\VehicleObject;
use GuayaquilLib\Oem;

class ApplicabilitydetailsHtml extends View
{
    public function Display($tpl = 'applicabilitydetails', $view = 'view')
    {
        $this->displayApplicabilityDetails();

        parent::Display('qdetails', 'view');
    }

    public function displayApplicabilityDetails()
    {
        $catalog = $this->input->getString('c', '');
        $ssd = $this->input->getString('ssd', '');
        $oem = $this->input->getString('oem', '');
        $vid = $this->input->getString('vid');
        $this->applicability = true;

        /** @var CatalogObject $cataloginfo */
        /** @var QuickDetailListObject $details */
        /** @var VehicleObject $vehicle */
        list($cataloginfo, $details, $vehicle) = $this->getOemService()->queryButch([
            Oem::getCatalogInfo($catalog, $this->getLanguage()->getLocalization(), true),
            Oem::findPartInVehicle($catalog, $ssd, $oem, $this->getLanguage()->getLocalization()),
            Oem::getVehicleInfo($catalog, $vid, $ssd, $this->getLanguage()->getLocalization()),
        ]);

        $this->pathway->addItem($cataloginfo->getName(), $this->createUrl2($cataloginfo));

        $vehicleLink = $this->createUrl('vehicle', '', '', [
            'c' => $cataloginfo->getCode(),
            'vid' => $vehicle->getVehicleId(),
            'ssd' => $vehicle->getSsd(),
            'checkQG' => true
        ]);

        foreach ($details->getCategories() as $category) {
            foreach ($category->getUnits() as $unit) {
                $groups = [];
                foreach ($unit->getParts() as $detail) {
                    if ($detail->getCodeOnImage() && $detail->getCodeOnImage() != '-') {
                        $groups['i' . $detail->getCodeOnImage()][] = $detail;
                    } else {
                        $groups['-'][] = $detail;
                    }
                }
                $unit->detailsByCode = $groups;
            }
        }

        $this->pathway->addItem($vehicle->getBrand() . ' ' . $vehicle->getName(), $vehicleLink);
        $this->pathway->addItem($oem);

        $this->gid = $this->input->getString('gid', '');
        $this->details = $details;
        $this->vehicle = $vehicle;
        $this->format = $this->input->getString('format', '');
        $this->oem = $oem;
    }

}