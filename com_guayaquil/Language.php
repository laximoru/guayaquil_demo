<?php

namespace guayaquil;

use guayaquil\language\LanguageTemplateEn;
use guayaquil\language\LanguageTemplateRu;

class Language
{
    protected $defaultlanguageCode;

    public function __construct(Config $config)
    {
        $this->defaultlanguageCode = $config->catalog_data;
        $this->getLocalizations();
    }

    /**
     * @return array
     */
    public function getLocalizationsList(): array
    {
        return [
            'Русский' => 'ru_RU',
            'English (USA)' => 'en_US',
            'Chinese' => 'zh_CN',
            'Turkish' => 'tr_TR',
            'French' => 'fr_FR',
            'German' => 'de_DE',
            'Hindi' => 'hi_IN',
            'Spanish' => 'es_ES',
            'Japanese' => 'ja_JP',
            'Dutch' => 'nl_NL',
            'English (UK)' => 'en_GB',
            'Greek' => 'el_GR',
            'Italian' => 'it_IT',
            'Korean' => 'ko_KR',
            'Polish' => 'pl_PL',
            'Português' => 'pt_PT',
            'Svenska' => 'sv_SE',
            'Thai' => 'th_TH',
            'Traditional Chinese' => 'zh_TW',
            'Czech' => 'cs_CZ',
            'Danish' => 'da_DK',
            'Finnish' => 'fi_FI',
            'Hungarian' => 'hu_HU',
            'Romanian' => 'ro_RO',
            'Croatian' => 'hr_HR',
            'Estonian' => 'et_EE',
            'Latvian' => 'lv_LV',
            'Lithuanian' => 'lt_LT',
            'Български' => 'bg_BG',
            'Slovak' => 'sk_SK',
        ];
    }

    public function setLocalization($code)
    {
        setcookie('interface_language', $code);
    }

    public function getLocalization()
    {
        if (!isset($_COOKIE['interface_language'])) {
            $this->setLocalization($this->defaultlanguageCode);
            return $this->defaultlanguageCode;
        }

        return $_COOKIE['interface_language'];
    }

    protected function getLocalizations(string $locale = 'en_US')
    {
        static $localizations = false;

        if (!$localizations) {

            $localization = $this->getLocalization();
            $localizations = $this->loadClass($localization);

            if (!$localizations) {
                $localizations = $this->loadClass($locale);
            }

            if (!$localizations) {
                $localizations = $this->loadClass('en_US');
            }
        }

        return $localizations;
    }

    public function t($name, ...$args)
    {
        $name = (string)$name;

        $localizations = $this->getLocalizations();

        if (array_key_exists($name, $localizations) && $localizations[$name]) {
            $string = (string)$localizations[$name];

            return sprintf($string, ...$args);
        } else {
            return $name;
        }
    }

    public function noSpaces($name)
    {
        $name = (string)$name;

        $name = preg_replace('/\s+/', ' ', $name);

        return $name;

    }

    /**
     * @param $language
     */
    protected function loadClass($localization) : array
    {
        list($language) = explode('_', $localization);
        $className = 'guayaquil\language\LanguageTemplate' . ucfirst($language);
        if (class_exists($className)) {
            $data = new $className();
            return $data->getTemplateData();
        }

        return [];
    }
}