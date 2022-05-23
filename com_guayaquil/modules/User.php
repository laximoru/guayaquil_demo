<?php

namespace guayaquil\modules;

use Exception;
use guayaquil\Config;
use guayaquil\ServiceAmProxy;
use guayaquil\ServiceOemProxy;

class User
{
    /** @var User */
    static $user = null;

    /** @var string */
    protected $userName = '';

    protected $password = '';

    protected $services = [];

    public function __construct($storedData = '')
    {
        if ($storedData) {
            $data = json_decode($storedData, true);
            $this->userName = $data['login'];
            $this->password = $data['password'];
            $this->services = $data['services'];
        }
    }

    /**
     * @return User
     */
    public static function getUser(): User
    {
        return self::$user;
    }

    /**
     * @param User $user
     */
    public static function setUser(User $user)
    {
        self::$user = $user;
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return string
     */
    public function getPassword() : string
    {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isLoggedIn() : bool
    {
        return count($this->services) > 0;
    }

    /**
     * @return bool
     */
    public function isServiceAvailable(string $service) : bool
    {
        return array_key_exists($service, $this->services);
    }

    public static function login(string $user, string $pass)
    {
        $services = [];

        try {
            $am = new ServiceAmProxy(null, $user, $pass, Config::getConfig()->amServiceUrl);
            $am->findOem('c110');
            $services['am'] = 'am';
        } catch (Exception $ex) {
        }

        try {
            $oem = new ServiceOemProxy(null, $user, $pass, Config::getConfig()->oemServiceUrl);
            $oem->listCatalogs();
            $services['oem'] = 'oem';
        } catch (Exception $ex) {
        }

        if (count($services)) {
            $user = new User(json_encode([
                'login' => $user,
                'password' => $pass,
                'services' => $services,
            ]));
            User::setUser($user);
            $_SESSION['userData'] = $user->toString();
        } else {
            User::logout();
        }
    }

    public static function logout()
    {
        unset($_SESSION['userData']);
        User::setUser(new User());
    }

    public function toString()
    {
        return json_encode([
            'login' => $this->userName,
            'password' => $this->password,
            'services' => $this->services,
        ]);
    }
}

User::setUser(new User(@$_SESSION['userData']));
