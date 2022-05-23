<?php

namespace guayaquil\modules;


class Input
{
    public function getString($arg, $default = false): string
    {
        return isset($_GET[$arg]) ? (string)$_GET[$arg] : $default;
    }

    public function getInt($arg, $default = false): int
    {
        return isset($_GET[$arg]) ? (int)$_GET[$arg] : $default;
    }

    public function get($arg)
    {
        return @$_GET[$arg];
    }

    public function getArray(): array
    {
        return $_GET;
    }

    public function formData(): array
    {
        return $_POST;
    }

}