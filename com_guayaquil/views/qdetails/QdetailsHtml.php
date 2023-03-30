<?php

namespace guayaquil\views\qdetails;

use guayaquil\View;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\objects\oem\QuickDetailListObject;
use GuayaquilLib\objects\oem\VehicleObject;
use GuayaquilLib\Oem;

/**
 * @property string gid
 * @property array categories
 * @property string format
 * @property bool oem
 */
class QdetailsHtml extends View
{
    public function Display($tpl = 'qdetails', $view = 'view')
    {
        $catalogCode = $this->input->getString('c');
        $ssd = $this->input->getString('ssd', '');
        $format = $this->input->getString('format');
        $vid = $this->input->getString('vid');
//        $cid = $this->input->getString('cid', -1);
        $gid = $this->input->getString('gid');
        $oem = $this->input->getString('oem');

        /** @var CatalogObject $catalogInfo */
        /** @var VehicleObject $vehicle */
        /** @var QuickDetailListObject $details */
        list($catalogInfo, $vehicle, $details) = $this->getOemService()->queryButch([
            Oem::getCatalogInfo($catalogCode, $this->getLanguage()->getLocalization(), true),
            Oem::getVehicleInfo($catalogCode, $vid, $ssd, $this->getLanguage()->getLocalization()),
            $oem ?
                Oem::findPartInVehicle($catalogCode, $ssd, $oem, $this->getLanguage()->getLocalization()) :
                Oem::listQuickDetail($catalogCode, $vid, $ssd, $gid, $this->getLanguage()->getLocalization()),
        ]);

        $this->pathway->addItem($catalogInfo->getName(), $this->createUrl2($catalogInfo));
        $this->pathway->addItem($vehicle->getName(), $this->createUrl('qgroups', '', '', [
            'c' => $catalogInfo->getCode(),
            'vid' => $vehicle->getVehicleId(),
            'ssd' => $vehicle->getSsd()
        ]));

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

        $this->pathway->addItem($this->getLanguage()->t('detailsInGroup'));
        $this->applicability = (bool)$oem;
        $this->gid = $this->input->getString('gid', '');
        $this->details = $details;
        $this->vehicle = $vehicle;
        $this->format = $format;
        $this->oem = $oem;

        parent::Display($tpl, $view);
    }
}