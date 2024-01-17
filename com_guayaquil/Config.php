<?php

namespace guayaquil;

class Config
{
    public $imageSize = 250;

    public $catalog_data = 'ru_RU';
    public $defaultUserLogin = '';
    public $defaultUserKey = '';

    /* ws.oem web-service url */
    public $oemServiceUrl = 'ws.laximo.ru';

    /* aftermarket service url */
    public $amServiceUrl = 'aws.laximo.ru';

    /* show start page. Catalogs list will shown if false */
    public $showWelcomePage = true;

    /* show demo to guest */
    public $showToGuest = true;

    /* show request text and response xml-message */
    public $showRequest = true;

    /* show quick-groups tree to guest */
    public $showGroupsToGuest = true;

    /* show oem-numbers on unit page, quick-details page and in xml-response message */
    public $showOemsToGuest = true;

    /* show find by oem field, find all detail usage in modification and details-list in modification */
    public $showApplicability = true;

    /* show find part by name inside of vehicle form */
    public $showNameSearch = true;

    /* show find part by name inside of vehicle form */
    public $plateSearch = true;

    /* Url to page where you can see offers to current detail */
    public $backUrl = 'https://site.com/index.php?keyword={article}&brand={brand}';

    /* image placeholder */
    public $imagePlaceholder = 'com_guayaquil/assets/images/no-image.gif';

    /* columns on catalogs list page */
    public $catalogColumns = 3;

    /* added big letters to catalog names, so you can find your catalog easier */
    public $showCatalogsLetters = true;

    public $useWebserviceAuthorize = false;

    /* system find named css-file and apply it, now can be "guayaquil", "green" */
    public $theme = 'guayaquil';
    public $linkTarget = '_parent';

    public $VehiclesColumns = [
        'brand',
        'name',
        'date',
        'datefrom',
        'dateto',
        'model',
        'framecolor',
        'trimcolor',
        'modification',
        'grade',
        'frame',
        'engine',
        'engineno',
        'transmission',
        'doors',
        'manufactured',
        'options',
        'creationregion',
        'destinationregion',
        'description'
    ];

    public $oemExample;
    public $plateExample = 'А001БГ97';

    protected static $config = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @return null
     */
    public static function getConfig()
    {
        if (!self::$config) {
            self::$config = new Config();
        }

        return self::$config;
    }

    /**
     * @param null $config
     */
    public static function setConfig($config)
    {
        self::$config = $config;
    }
}
