<?php

namespace guayaquil;

use Exception;
use guayaquil\modules\Input;
use guayaquil\modules\pathway\Pathway;
use guayaquil\modules\User;
use GuayaquilLib\objects\oem\CatalogObject;
use GuayaquilLib\objects\oem\UnitObject;
use GuayaquilLib\ServiceAm;
use GuayaquilLib\ServiceOem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class View
{
    /**
     * @var bool
     */
    public $rawFormat = false;

    /**
     * @var Input
     */
    public $input;

    /**
     * @var Pathway
     */
    public $pathway;

    /**
     * @var int
     */
    public $lastExecutionTime;

    /**
     * @var string[]
     */
    public $lastExecutionCommand = [];

    /**
     * @var string[]
     */
    public $lastExecutionResponse = [];
    
    /**
     * @var Config
     */
    public $config;

    /**
     * @var String
     */
    public $page_title = 'Guayaqul demo 2';

    public function __construct()
    {
        $this->input = new Input();
        $this->pathway = new Pathway();
        $this->rawFormat = $this->input->getString('format', '') == 'raw';
        $this->config = Config::getConfig();

        if ($this->config->showWelcomePage) {
            $this->pathway->addItem($this->getLanguage()->t('laximoHome'), '/');

            if ($this->input->getString('task') == 'aftermarket') {
                $this->pathway->addItem($this->getLanguage()->t('laximoAm'), $this->createUrl('aftermarket', '', '', []));
            } else {
                $this->pathway->addItem($this->getLanguage()->t('laximoOem'), $this->createUrl('catalogs', '', '', []));
            }
        }
    }

    public function appendLastXmlResponse(string $xmlString)
    {
        $this->lastExecutionResponse[] = $xmlString;
    }

    public function appendLastExecutionCommand(array $commands)
    {
        $this->lastExecutionCommand = array_merge($this->lastExecutionCommand, $commands);
    }

    public function setLastExecutionTime(float $time)
    {
        $this->lastExecutionTime += $time;
    }

    protected function getLanguage(): Language
    {
        static $language = null;
        if (!$language) {
            $language = new Language($this->config);
        }

        return $language;
    }

    protected function getOemService(): ServiceOem
    {
        static $oem = null;
        if (!$oem) {
            $user = User::getUser();
            if ($user->isServiceAvailable('oem')) {
                $login = $user->getUserName();
                $password = $user->getPassword();
            } else {
                $login = $this->config->defaultUserLogin;
                $password = $this->config->defaultUserKey;
            }

            if (!$login || !$user) {
                throw new UnauthorisedException('oem');
            }

            $oem = new ServiceOemProxy($this, $login, $password, $this->config->oemServiceUrl);
        }

        return $oem;
    }

    protected function getAmService(): ServiceAm
    {
        static $am = null;
        if (!$am) {
            $user = User::getUser();
            if ($user->isServiceAvailable('am')) {
                $login = $user->getUserName();
                $password = $user->getPassword();
            } else {
                $login = $this->config->defaultUserLogin;
                $password = $this->config->defaultUserKey;
            }

            if (!$login || !$user) {
                throw new UnauthorisedException('am');
            }

            $am = new ServiceAmProxy($this, $login, $password, $this->config->amServiceUrl);
        }

        return $am;
    }

    public function Display($tpl = 'catalogs/tmpl', $view = 'view.twig')
    {
        $this->render($tpl . '/tmpl', $view . '.twig', [], $this->rawFormat);
    }

    public function createUrl($task = null, $view = null, $format = null, array $params = [])
    {
        $paths = [];

        if ($task) {
            if (is_array($task)) {
                $paths = array_merge($paths, $task);
            } else {
                $paths['task'] = $task;
            }
        }

        if ($view) {
            if (is_array($view)) {
                $paths = array_merge($paths, $view);
            } else {
                $paths['view'] = $view;
            }
        }

        if ($format) {
            if (is_array($format)) {
                $paths = array_merge($paths, $format);
            } else {
                $paths['format'] = $format;
            }
        }

        foreach ($params as $key => $param) {
            $params[$key] = trim($param??'');
        }

        if ($params) {
            $paths = array_merge($paths, $params);
        }

        $baseUrl = $_SERVER['HTTP_HOST'] . '/';

        if ($paths) {
            $url = ('index.php?' . http_build_query($paths));
            if (strpos($url, $baseUrl) === false) {
                $url = 'index.php?' . http_build_query($paths);
            }
        } else {
            $url = $baseUrl;
        }

        return urldecode($url);
    }

    public function createUrl2($object, $params = []): string
    {
        if (is_a($object, CatalogObject::class)) {
            /** @var CatalogObject $catalog */
            $catalog = $object;
            return $this->createUrl('catalog', '', '', [
                'c' => $catalog->getCode(),
                'ssd' => @$params['ssd'],
                'spi2' => $catalog->getWizard2Feature() != null ? 't' : ''
            ]);
        }

        if (is_a($object, UnitObject::class)) {
            /** @var UnitObject $catalog */
            $unit = $object;
            return $this->createUrl('unit', '', '', [
                'c' => @$params['c'],
                'vid' => @$params['vid'],
                'uid' => $unit->getUnitId(),
                'cid' => @$params['cid'],
                'ssd' => $unit->getSsd(),
            ]);
        }

        throw new Exception('Object type ' . get_class($object) . ' is not supported');
    }

    public function render(string $tpl = 'tmpl', string $view = '', array $vars = [], bool $raw = false)
    {
        if (!$this->config->showToGuest) {
            http_response_code(401);
            $this->renderPage('error/tmpl', 'unauthorized.twig', [
                'type' => 'unauthorized',
            ]);
            return;
        }

        $this->renderPage($tpl, $view, array_merge((array)$this, $vars), $raw);
    }

    public function renderPage(string $tpl = 'tmpl', string $view = '', array $vars = [], bool $raw = false)
    {
        $additionalVars = [
            'languages' => $this->getLanguage()->getLocalizationsList(),
            'current' => $this->getLanguage()->getLocalization(),
            'task' => $this->input->getString('task', ''),
            'user' => User::getUser(),
        ];

        $twig = $this->getTwig();
        $loader = new FilesystemLoader([
            realpath(__DIR__) . '/views/' . $tpl . '/',
            realpath(__DIR__),
        ]);
        $twig->setLoader($loader);
        $vars['renderTemplate'] = 'views/' . $tpl . '/' . $view;

        if ($raw) {
            echo $twig->render('layouts/raw.twig', array_merge($vars, $additionalVars));
        } else {
            echo $twig->render('layouts/index.twig', array_merge($vars, $additionalVars));
        }
    }

    public function getTwig(): Environment
    {
        static $twig = null;

        if (!$twig) {
            $twig = new Environment(new FilesystemLoader([realpath(__DIR__)]), [
                'cache' => false,
                'auto_reload' => true,
            ]);

            $twig->addFunction(new TwigFunction('createUrl', [$this, 'createUrl']));
            $twig->addFunction(new TwigFunction('url', [$this, 'createUrl2']));
            $twig->addFilter(new TwigFilter('dump', 'var_dump'));
            $twig->addFilter(new TwigFilter('t', [$this->getLanguage(), 't']));
            $twig->addFilter(new TwigFilter('noSpaces', [$this->getLanguage(), 'noSpaces']));
            $twig->addFilter(new TwigFilter('printr', 'print_r'));
        }

        return $twig;
    }

    public function redirect($link)
    {
        header("Location: " . $link);
        exit();
    }
}