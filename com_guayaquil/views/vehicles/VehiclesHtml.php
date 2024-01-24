<?php

namespace guayaquil\views\vehicles;

use Exception;
use guayaquil\Config;
use guayaquil\View;
use GuayaquilLib\Am;
use GuayaquilLib\exceptions\StandardPartException;
use GuayaquilLib\objects\am\PartCrossObject;
use GuayaquilLib\objects\am\PartObject;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\objects\oem\PartReferencesListObject;
use GuayaquilLib\objects\oem\VehicleListObject;
use GuayaquilLib\Oem;
use stdClass;

/**
 * @property string notFoundReason
 */
class VehiclesHtml extends View
{
    public function Display($tpl = 'vehicles', $view = 'view')
    {
        if ($this->input->getString('view') === 'checkDetailApplicability') {
            $this->checkDetailApplicability();
        }

        $vin = $this->input->getString('vin', '');
        $frameNo = $this->input->getString('frameNo', '');
        $oem = $this->input->getString('oem', false);
        $operation = $this->input->getString('operation', '');
        $catalogCode = $this->input->getString('c');
        $ssd = $this->input->getString('ssd', '');
        $request = new stdClass();

        $findType = $this->input->getString('ft');
        $typeValue = '';
        $notFoundData = [];
        $ident = '';
        $requests = [];

        switch ($findType) {
            case 'findByVIN':
                $type = [
                    'name' => 'VIN',
                    'value' => $vin
                ];
                $typeValue = $vin;

                $requests[] = Oem::findVehicleByVin($vin, $this->getLanguage()->getLocalization());

                break;
            case 'findByFrame':
                $type = [
                    'name' => 'Frame',
                    'value' => $frameNo
                ];

                $typeValue = $frameNo;

                $requests[] = Oem::findVehicleByFrameNo($frameNo, $this->getLanguage()->getLocalization());

                break;
            case 'execCustomOperation':
                $notFoundData = $this->input->get('data');
                $msg = implode('-', $notFoundData);
                $type = [
                    'name' => $this->getLanguage()->t($operation),
                    'value' => $msg
                ];

                $typeValue = $msg;

                $requests[] = Oem::execCustomOperation($catalogCode, $operation, $this->input->get('data'), $this->getLanguage()->getLocalization());

                break;
            case 'findByWizard2':
                $type = [
                    'name' => $this->getLanguage()->t('by' . $findType),
                    'value' => ''
                ];

                $requests[] = Oem::findVehicleByWizard2($catalogCode, $ssd, $this->getLanguage()->getLocalization());

                break;
            case 'FindVehicle':
                $ident = $this->input->getString('identString', '');

                $requests[] = Oem::findVehicle($ident, $this->getLanguage()->getLocalization());

                $type = [
                    'name' => $this->getLanguage()->t('by' . strtolower($findType)),
                    'value' => $ident
                ];

                $typeValue = $ident;
                break;

            case 'findByOEM':
                if (!$catalogCode) {
                    $brand = $this->input->getString('brand');

                    $this->catalogNames = [];
                    $this->catalogsCodes = [];

                    $catalogsList = $this->getOemService()->listCatalogs();
                    foreach ($catalogsList->getCatalogs() as $catalog) {
                        $this->catalogsCodes[$catalog->getBrand()] = $catalog->getCode();
                        $this->catalogNames[$catalog->getCode()] = $catalog->getName();
                    }

                    try {
                        $catalogs = $this->getOemService()->findCatalogsByOem($oem);
                    } catch (StandardPartException $ex) {
                        throw new Exception($this->getLanguage()->t('E_STANDARD_PART_SEARCH'));
                    }

                    $this->searchBy = $findType;

                    if ($catalogs->getReferences() && count($catalogs->getReferences())) {
                        $originals = $catalogs->getReferences();

                        if (!$brand) {
                            if ($originals) {
                                $this->displayVehicleBrands($originals);
                            }
                        }
                    } else {
                        $amDetails = $this->getAmService()->findOem($oem, '', [Am::optionsCrosses]);

                        if (!empty($amDetails->getOems())) {
                            $brands = $this->getDetailBrands($amDetails->getOems());
                            if ($brands && is_array($brands) && count($brands)) {
                                $this->displayDetailBrand($brands);
                            }
                        }
                    }

                    parent::Display('vehicles', 'nothingFound');
                    die();
                }

                $type = [
                    'name' => $this->getLanguage()->t('by' . strtolower($findType)),
                    'value' => $oem
                ];

                $requests[] = Oem::findVehicleByOem($catalogCode, $oem, $this->getLanguage()->getLocalization());

                break;

            case 'findByPlate':
                $ident = $this->input->getString('plate', '');

                $requests[] = Oem::findVehicleByPlateNumber($ident, $this->getLanguage()->getLocalization());

                $type = [
                    'name' => $this->getLanguage()->t('by' . strtolower($findType)),
                    'value' => $ident
                ];

                $typeValue = $ident;
                break;

            default:
                parent::Display($tpl, $view);
                return;
        }

        if ($catalogCode) {
            $requests[] = Oem::getCatalogInfo($catalogCode, $this->getLanguage()->getLocalization(), true);
        }

        $data = $this->getOemService()->queryButch($requests);

        /** * @var VehicleListObject $vehicles */
        $vehicles = $data[0];
        $catalogInfo = $catalogCode && isset($data[1]) ? $data[1] : null;
        $this->groupVehicles($vehicles, $catalogInfo);

        /** @var CatalogObject $catalogInfo */

        if ($catalogInfo) {
            $this->pathway->addItem($catalogInfo->getName(), $this->createUrl2($catalogInfo));
        }

        $this->pathway->addItem($this->getLanguage()->t('vehiclesFind'));
        if (isset($typeValue) && !empty($typeValue)) {
            $this->pathway->addItem($typeValue);
        }

        $this->vin = $vin;
        $this->frameNo = $frameNo;
        $this->type = $type;
        $this->cataloginfo = $catalogInfo;
        $this->useApplicability = $catalogInfo ? $catalogInfo->getDetailApplicabilityFeature() != null : 0;
        $this->vehicles = $vehicles ? $vehicles->getVehicles() : [];
        $this->brandName = $catalogInfo ? $catalogInfo->getName() : '';
        $this->searchBy = $findType;
        $this->columns = $this->headers;
        $this->oem = $this->input->getString('oem');
        $this->customOperationValue = $notFoundData;
        $this->ident = $ident;
        $this->oemExample = !empty($this->config->oemExample) ? $this->config->oemExample : '0913128000';
        if (count($this->columns) <= 2) {
            $this->showGrouppedVehicle = false;
        } else {
            $this->showGrouppedVehicle = true;
            unset($this->columns['brand']);
            unset($this->columns['name']);
        }

        parent::Display($tpl, $view);
    }

    protected function groupVehicles(VehicleListObject $vehicles/*, CatalogObject $catalog*/)
    {
        $columnValues = [];
        $tableHeader = ['brand' => 'Brand', 'name' => 'Name'];
        $tableColumns = [];
        $commonColumns = [];

        foreach ($vehicles->getVehicles() as $vehicle) {
            $columnValues['brand'][$vehicle->getBrand()] = 1;
            $columnValues['name'][$vehicle->getName()] = 1;

            if ($vehicle->getAttributes()) {
                foreach ($vehicle->getAttributes() as $column => $attribute) {
                    @$columnValues[$column][$attribute->getValue()]++;
                    $tableHeader[$column] = $attribute->getName();
                }
            }

//            if ($catalog && $catalog->getQuickGroupsFeature()) {
//                $vehicle->link = $this->createUrl('qgroups', '', '', $addParams);
//            }
        }

        foreach ($columnValues as $column => $values) {
            if (count($values) > 1) {
                $tableColumns[] = $column;
            } else {
                foreach ($values as $value => $count) {
                    if ($count == count($vehicles->getVehicles())) {
                        $attributeObject = [
                            'key' => $column,
                            'name' => $tableHeader[$column],
                            'value' => $value
                        ];

                        $commonColumns[] = $attributeObject;
                    } else {
                        $tableColumns[] = $column;
                    }
                }
            }

        }

        $groupedByName = [];
        foreach ($vehicles->getVehicles() as $vehicle) {
            if (!isset($groupedByName[$vehicle->getName()])) {
                $groupedByName[$vehicle->getName()] = $vehicle;
            } else {
                if ($groupedByName[$vehicle->getName()] !== $vehicle) {
                    $groupedByName[$vehicle->getName()]->children[] = $vehicle;
                }
            }
        }

        $this->headers = $tableHeader;
        $this->groupedVehicles = $groupedByName;
    }

    public function checkDetailApplicability()
    {
        $data = $this->input->formData();
        $details = json_decode($data['details'], true);
        $catalog = $data['catalog'];
        $detailsChecked = [];
        $detailsToShow = [];
        $toCheck = 5;

        while (count($detailsToShow) < 5 && count($details)) {
            $stack = [];

            while (count($stack) < $toCheck && count($details)) {
                $stack[] = array_shift($details);
            }

            $detailsWithApplicability = $this->checkDetails($stack, $catalog);

            $toCheck = $toCheck - count($detailsWithApplicability);

            $detailsChecked = array_merge($detailsChecked, $stack);
            $detailsToShow = array_merge($detailsToShow, $detailsWithApplicability);
        }

        header('Content-Type: application/json');
        echo json_encode(['detailsChecked' => $detailsChecked, 'detailsToShow' => $detailsToShow]);
        die();
    }

    private function checkDetails($details, $catalog)
    {
        $commands = [];
        foreach ($details as $detail) {
            $commands[] = Oem::findCatalogsByOem($detail['oem']);
        }

        $result = $this->getOemService()->queryButch($commands);

        $checkedDetails = [];

        /** @var PartReferencesListObject $res */
        foreach ($result as $key => $res) {
            $catalogReferences = [];

            if (!empty($res->getReferences())) {
                $catalogReferences = array_filter($res->getReferences(), function ($ref) use ($catalog) {
                    return $ref->getCode() === $catalog;
                });
            }

            if (!empty($res->getReferences()) && !empty($catalogReferences)) {
                $checkedDetails[] = $details[$key];
            }
        }

        return $checkedDetails;
    }

    public function displayVehicleBrands($originals)
    {
        $this->originals = $originals;
        $this->oem = $this->input->getString('oem');

        parent::Display('vehicles', 'selectVehicleBrand');
        die();
    }

    /**
     * @param PartObject[] $details
     * @return array
     */
    private function getDetailBrands(array $details)
    {
        $amManufacturers = $this->getAmService()->listManufacturer();
        $catalogs = $this->getOemService()->listCatalogs();
        $catalogNames = [];
        foreach ($catalogs->getCatalogs() as $catalog) {
            $catalogNames[$catalog->getBrand()] = $catalog->getBrand();
            foreach ($amManufacturers->getManufacturers() as $manufacturer) {
                foreach ($manufacturer->getAliases() as $manufacturerAlias) {
                    if ($manufacturerAlias == $catalog->getBrand()) {
                        $catalogNames[$manufacturer->getName()] = $manufacturer->getName();
                        break;
                    }
                }
            }
        }

        $replacements = [];

        if (!empty($details)) {
            foreach ($details as $detail) {
                if (!empty($detail->getReplacements())) {
                    $filteredDetails = array_values(array_filter($detail->getReplacements(), function (PartCrossObject $replacement) use ($catalogNames) {
                        return in_array($replacement->getPart()->getManufacturer(), $catalogNames);
                    }));

                    $filteredGroupedDetails = [];

                    /** @var PartCrossObject $filteredDetail */
                    foreach ($filteredDetails as $filteredDetail) {
                        $filteredGroupedDetails[$filteredDetail->getPart()->getManufacturer()][] = [
                            'manufacturer' => $filteredDetail->getPart()->getManufacturer(),
                            'name' => $filteredDetail->getPart()->getName(),
                            'oem' => $filteredDetail->getPart()->getOem(),
                        ];
                    }

                    $replacement = new stdClass();
                    $replacement->details = $filteredGroupedDetails;
                    $replacement->oem = $detail->getOem();
                    $replacement->name = $detail->getName();
                    $replacement->formatted_name = $detail->getManufacturer() . ': ' . $detail->getOem() . ' ' . $detail->getName();
                    $replacement->detail_id = $detail->getPartId();

                    $replacements[] = $replacement;
                }
            }
        }

        return $replacements;
    }

    public function displayDetailBrand($brands)
    {
        $this->brands = $brands;
        $this->oem = $this->input->getString('oem');

        parent::Display('vehicles', 'selectDetailBrand');
        die();
    }
}
