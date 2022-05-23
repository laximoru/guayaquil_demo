<?php
namespace guayaquil\views\error;
use guayaquil\View;

class ErrorHtml extends View
{
    public function Display($tpl = 'error', $view = 'error') {
        $type = $this->input->getString('type', 'error');

        parent::Display($tpl, $type);
    }
}