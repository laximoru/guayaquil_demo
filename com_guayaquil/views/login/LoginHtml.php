<?php

namespace guayaquil\views\login;

use guayaquil\modules\User;
use guayaquil\View;


class LoginHtml extends View
{
    public function Display($tpl = 'login', $view = 'view')
    {
        $view = $this->input->getString('view');
        switch ($view) {
            case 'login':
                $this->login();
                break;
            case 'logout':
                $user = $this->input->formData()['user'];
                $url = parse_url($user['backurl']);
                parse_str($url['query'], $backurlParams);

                User::logout();
                $this->redirect($user['backurl']);
                break;
        }
    }

    public function login()
    {
        $user = $this->input->formData()['user'];

        if (!$user) {
            return;
        }

        $login = trim($user['login']??'');
        $key = $user['password'];

        $url = parse_url($user['backurl']);
        parse_str($url['query'], $backurlParams);

        User::login($login, $key);
        if (User::getUser()->isLoggedIn()) {
            $this->redirect($user['backurl'] . '&auth=true');
        } else {
            $this->redirect($user['backurl'] . '&auth=false');
        }
    }
}