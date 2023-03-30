<?php

namespace guayaquil\views\vehicle;

use guayaquil\Config;
use guayaquil\View;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\objects\oem\CategoryListObject;
use GuayaquilLib\objects\oem\UnitListObject;
use GuayaquilLib\objects\oem\UnitObject;
use GuayaquilLib\objects\oem\VehicleObject;
use GuayaquilLib\Oem;

class VehicleHtml extends View
{
    public function Display($tpl = 'vehicle', $view = 'view')
    {
        $catalogCode = $this->input->getString('c');
        $ssd = $this->input->getString('ssd', '');
        $vid = $this->input->getString('vid');
        $cid = $this->input->getString('cid', -1);
        $linkedWithUnit = $this->input->getString('linkedWithUnit');

        /** @var CatalogObject $catalogInfo */
        /** @var VehicleObject $vehicle */
        /** @var CategoryListObject $categories */
        /** @var UnitListObject $units */
        list ($catalogInfo, $vehicle, $categories, $units) = $this->getOemService()->queryButch([
            Oem::getCatalogInfo($catalogCode, $this->getLanguage()->getLocalization(), true),
            Oem::getVehicleInfo($catalogCode, $vid, $ssd, $this->getLanguage()->getLocalization()),
            Oem::listCategories($catalogCode, $vid, $ssd, $cid, $this->getLanguage()->getLocalization()),
            Oem::listUnits($catalogCode, $vid, $ssd, $cid, $this->getLanguage()->getLocalization()),
        ]);

        if ($units && count($units->getUnits()) === 1 && $linkedWithUnit) {
            /** @var UnitObject $unit */
            $unit = $units->getUnits()[0];
            $this->redirect($this->createUrl2($unit, ['vid' => $vid, 'cid' => $cid, 'c' => $catalogCode]));
        }

        if ($this->input->getString('checkQG', false) && $catalogInfo->getQuickGroupsFeature() != null) {
            $link = $this->createUrl('qgroups', '', '', [
                'c' => $this->input->getString('c'),
                'vid' => $this->input->getString('vid'),
                'ssd' => $this->input->getString('ssd'),
                'path_data' => $this->input->getString('path_data')
            ]);

            $this->redirect($link);
        }

        $this->pathway->addItem($catalogInfo->getName(), $this->createUrl2($catalogInfo));

        $firstCategory = -1;
        if ($categories) {
            $toShift = $categories->getRoot();
            $firstCategory = array_shift($toShift);
        }

        $this->pathway->addItem($vehicle->getBrand() . ' ' . $vehicle->getName());

        $this->vin = $this->input->getString('vin', '');
        $this->frame = $this->input->getString('frame', '');
        $this->node_id = $this->input->getString('node_id', '');
        $this->cataloginfo = $catalogInfo;
        $this->vehicle = $vehicle;
        $this->categories = $categories->getRoot();
        $this->units = $units->getUnits();
        $this->cCid = $this->input->getString('cid', '');
        $this->firstCategory = !empty($firstCategory->categoryid) ? $firstCategory->categoryid : 0;
        $this->useApplicability = $catalogInfo ? $catalogInfo->getDetailApplicabilityFeature() != null : 0;
        $this->usePartByNameSearch = $catalogInfo ? $catalogInfo->getPartByNameSearchFeature() != null : 0;
        $this->partsList = isset($data[4]) ? $data[4]->oemParts : null;
        $this->totalParts = isset($data[4]) ? $this->total = count($data[4]->oemParts) : 0;
        $vehicleHtml = '\guayaquil\views\vehicle\VehicleHtml';
        $this->linkedWithUnit = $linkedWithUnit;

        parent::Display($tpl, $view);
    }
}




