<?php

namespace guayaquil;

use Exception;

class router
{
    public static function start()
    {
        session_start();

        $view = new View();
        try {
            $route = self::parse($_SERVER['REQUEST_URI']);

            $task = @$route['task'];
            if ($task) {
                $task = preg_replace('/[^\w\d]*/', '', $task);
                $namespace = 'guayaquil\views\\' . $task . '\\';
                $viewName = $namespace . ucfirst($task) . 'Html';

                /** @var $view View */
                $view = new $viewName();
                $view->Display();
            } else {
                if (Config::getConfig()->showWelcomePage) {
                    $view->render('tmpl', 'index.twig');
                } else {
                    $view->redirect($view->createUrl('catalogs'));
                }
            }
        } catch (UnauthorisedException $ex) {
            $view->service = $ex->service;
            $view->render('error/tmpl', 'unauthorized.twig');
        } catch (Exception $ex) {
            try {
                $view->render('error/tmpl', 'default.twig', ['message' => $ex->getMessage()]);
            } catch (Exception $ex) {
                print_r($ex);
            }
        }
    }

    public static function parse($segments)
    {
        $url = parse_url($segments);
        if (isset($url['query'])) {
            $query = $url['query'];
        }

        if (!empty($query)) {
            $values = explode('&', $query);
            foreach ($values as $key => $value) {
                if ($value === '') {
                    unset($values[$key]);
                }
                $parameter = explode('=', $value);

                if (!empty($parameter)) {
                    $parameters[$parameter[0]] = isset($parameter[1]) ? $parameter[1] : '';
                }


            }
            reset($values);

            $params = [];
            foreach ($values as $value) {
                $key = explode('=', $value);

                if (isset($key[0]) && isset($key[1])) {

                    $params[$key[0]] = $key[1];

                }

            }

            return [
                'task' => isset($parameters['task']) ? $parameters['task'] : '',
                'params' => $params
            ];
        }

        return [];
    }

}
