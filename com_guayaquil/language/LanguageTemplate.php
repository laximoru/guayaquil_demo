<?php

namespace guayaquil\language;


class LanguageTemplate
{
    protected $languageData = [];

    public function getTemplateData() : array
    {
        return $this->languageData;
    }
}