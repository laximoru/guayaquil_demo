<?php

namespace guayaquil\views\unit;

use guayaquil\View;
use GuayaquilLib\objects\oem\PartObject;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\objects\oem\ImageMapObject;
use GuayaquilLib\objects\oem\PartListObject;
use GuayaquilLib\objects\oem\UnitObject;
use GuayaquilLib\objects\oem\VehicleObject;
use GuayaquilLib\Oem;


class UnitHtml extends View
{
    public function Display($tpl = 'unit', $view = 'view')
    {
        $catalogCode = $this->input->getString('c');
        $ssd = $this->input->getString('ssd', '');
        $uid = $this->input->getString('uid');
        $vid = $this->input->getString('vid');
        $skipped = $this->input->getString('skipped');

        /** @var UnitObject $unit */
        /** @var PartListObject $details */
        /** @var ImageMapObject $imageMap */
        /** @var CatalogObject $catalogInfo */
        /** @var VehicleObject $vehicle */
        list($unit, $details, $imageMap, $catalogInfo, $vehicle) = $this->getOemService()->queryButch([
            Oem::getUnitInfo($catalogCode, $ssd, $uid, $this->getLanguage()->getLocalization()),
            Oem::listPartsByUnit($catalogCode, $ssd, $uid, $this->getLanguage()->getLocalization()),
            Oem::listImageMapByUnit($catalogCode, $ssd, $uid),
            Oem::getCatalogInfo($catalogCode, $this->getLanguage()->getLocalization(), true),
            Oem::getVehicleInfo($catalogCode, $vid, $ssd, $this->getLanguage()->getLocalization()),
        ]);

        $detailCodes = [];

        if ($details->getParts()) {
            $detailCodes = array_map(function (PartObject $detail) {
                return $detail->getCodeOnImage();
            }, $details->getParts());
        }

        $fromCatalogTask = $this->input->getString('fromTask');

        $this->pathway->addItem($catalogInfo->getName(), $this->createUrl2($catalogInfo));
        $this->pathway->addItem($vehicle->getName(), !$skipped ? $this->createUrl($catalogInfo->getQuickGroupsFeature() != null ? 'qgroups' : 'vehicle', '', '', ['c' => $catalogCode, 'vid' => $vid, 'ssd' => $ssd]) : '');
        $this->pathway->addItem($unit->getName());

        $this->vehicle = $vehicle;
        $this->cataloginfo = $catalogInfo;
        $this->unit = $unit;
        $this->imagemap = $imageMap->getMapObjects();
        $this->detailCodes = $detailCodes;
        $this->details = $details->getParts();
        $this->catalog = $this->input->getString('c');
        $this->vid = $this->input->getString('vid', '');
        $this->gid = $this->input->getString('gid', '');
        $this->cid = $this->input->getString('cid', '');
        $this->selectedCoi = $this->input->getString('coi', '');
        $this->cois = $this->input->getString('coi') ? explode(', ',
            $this->input->getString('coi')) : '';
        $this->fromCatalogTask = $fromCatalogTask;
        $this->corrected = $this->input->getString('corrected');
        $this->useApplicability = $catalogInfo->getDetailApplicabilityFeature() != null;

        parent::Display($tpl, $view);
    }

}