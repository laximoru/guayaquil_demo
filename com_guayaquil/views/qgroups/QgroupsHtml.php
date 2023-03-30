<?php

namespace guayaquil\views\qgroups;

use guayaquil\Config;
use guayaquil\modules\User;
use guayaquil\View;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\objects\oem\GroupObject;
use GuayaquilLib\objects\oem\VehicleObject;
use GuayaquilLib\Oem;

/**
 * @property VehicleObject vehicle
 * @property array groups
 * @property string ssd
 * @property string oem
 * @property int useApplicability
 * @property CatalogObject $cataloginfo
 * @property bool $usePartByNameSearch
 */
class QgroupsHtml extends View
{
    public function Display($tpl = 'qgroups', $view = 'view')
    {
        $catalogCode = $this->input->getString('c');
        $ssd = $this->input->getString('ssd', '');
        $oem = $this->input->getString('oem');
        $vid = $this->input->getString('vid', '');

        if ($oem) {
            $linkToQdetails = $this->createUrl('qdetails', '', '', [
                'c' => $catalogCode,
                'oem' => $oem,
                'vid' => $vid,
                'ssd' => $ssd
            ]);

            $this->redirect($linkToQdetails);
        }
        if (!User::getUser()->isLoggedIn() && !$this->config->showGroupsToGuest) {
            $this->redirect($this->createUrl('vehicle', '', '', [
                'c' => $catalogCode,
                'ssd' => $ssd,
                'vid' => $vid,
            ]));
        }

        /** @var CatalogObject $catalogInfo */
        /** @var VehicleObject $vehicle */
        /** @var GroupObject $groups */
//        try {
            list($catalogInfo, $vehicle, $groups) = $this->getOemService()->queryButch([
                Oem::getCatalogInfo($catalogCode, $this->getLanguage()->getLocalization(), true),
                Oem::getVehicleInfo($catalogCode, $vid, $ssd, $this->getLanguage()->getLocalization()),
                Oem::listQuickGroup($catalogCode, $vid, $ssd, $this->getLanguage()->getLocalization()),
            ]);
//        } catch (Exception $x) {
//            $vehicleLink = $this->createUrl('vehicle', '', '', [
//                'c' => $vehicle->catalog ?: $this->input->getString('c'),
//                'vid' => $vehicle->vehicleid ?: $this->input->getString('vid'),
//                'ssd' => $vehicle->ssd ?: $this->input->getString('ssd')
//            ]);
//
//            $this->redirect($vehicleLink);
//        }
        $this->pathway->addItem($catalogInfo->getName(), $this->createUrl2($catalogInfo));
        $this->pathway->addItem($vehicle->getBrand() . ' ' . $vehicle->getName());

        $this->vehicle = $vehicle;
        $this->groups = $groups->getChildGroups();
        $this->cataloginfo = $catalogInfo;
        $this->ssd = $this->input->getString('ssd', '');
        $this->oem = $oem;
        $this->useApplicability = $catalogInfo->getDetailApplicabilityFeature() != null;
        $this->usePartByNameSearch = $catalogInfo->getPartByNameSearchFeature() != null;

        parent::Display($tpl, $view);
    }

}