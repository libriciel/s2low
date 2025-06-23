<?php

namespace S2lowLegacy\Controller;

use Exception;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\MessageAdminSQL;

class Controller
{
    /**
     * @var User
     */
    protected $me;

    private $viewParameter;

    /**
     * @var ObjectInstancier
     */
    private $objectInstancier;


    private $files;

    public function __construct(
        ObjectInstancier $objectInstancier,
    ) {
        $this->objectInstancier = $objectInstancier;
        $this->viewParameter = array();
    }

    public function __get($key)
    {
        return $this->getViewParameter($key);
    }

    public function __set($key, $value)
    {
        $this->setViewParameter($key, $value);
    }

    public function isViewParameter($key)
    {
        return isset($this->viewParameter[$key]);
    }

    public function setViewParameter($key, $value)
    {
        $this->viewParameter[$key] = $value;
    }

    public function getViewParameter($key)
    {
        if (isset($this->viewParameter[$key])) {
            return $this->viewParameter[$key];
        }
        throw new Exception("parameter $key not found");
    }

    public function getAllViewParameter()
    {
        return $this->viewParameter;
    }

    public function setMessage($message)
    {
        //En attendant mieux...
        $this->getEnvironnement()->session()->set('error', $message);
    }

    /**
     * @return Environnement
     */
    protected function getEnvironnement(): Environnement
    {
        return $this->objectInstancier->get(Environnement::class);
    }

    public function displayAndExit($message, $url_redirect)
    {
        if ($this->isApiCall()) {
            $json = new JSONoutput();
            $json->displayAndExit($message);
        } //@codeCoverageIgnore
        $this->setErrorMessage($message);
        $this->redirectSSL($url_redirect);
    }

    public function isApiCall()
    {
        $recuperateur = $this->getRecuperateurGet();
        $api = $recuperateur->get('api');
        if ($api) {
            return true;
        }
        $recuperateur = $this->getRecuperateurPost();
        return $recuperateur->get('api');
    }

    public function getRecuperateurGet()
    {
        return $this->getEnvironnement()->get();
    }

    public function getRecuperateurPost()
    {
        return $this->getEnvironnement()->post();
    }

    public function setErrorMessage($error_message)
    {
        $this->getEnvironnement()->session()->set('error', $error_message);
    }

    //@codeCoverageIgnore

    public function redirectSSL($url_path = "", $url_arg = "")
    {
        $url = trim(WEBSITE_SSL, "/") . "/" . trim($url_path, "/");
        if ($url_arg) {
            $url .= "?$url_arg";
        }
        if (!TESTING_ENVIRONNEMENT) {
            header("Location: $url");
            exit();
        }
        throw new RedirectException("Redirect to $url");
    }

    //@codeCoverageIgnore

    public function verifGroupAdmin($authority_id)
    {
        $this->verifAdmin();
        if ($this->me->isSuper()) {
            return;
        }
        if ($this->me->isGroupAdmin()) {
            $authoritySQL = $this->objectInstancier->get(AuthoritySQL::class);
            $info = $authoritySQL->getInfo($authority_id);
            if ($info['authority_group_id'] == $this->me->get("authority_group_id")) {
                return;
            }
        }

        $this->redirect(WEBSITE_SSL, "Accès refusé");
    }

    public function verifAdmin($authority_id = false)
    {
        $this->verifUser();

        if (!$this->me->isAdmin()) {
            $this->displayErrorAndExit("Accès refusé", "");
        } // @codeCoverageIgnore
        if ($this->me->isSuper()) {
            return;
        }

        if ($authority_id) {
            $authoritySQL = $this->objectInstancier->get(AuthoritySQL::class);
            $info = $authoritySQL->getInfo($authority_id);

            if ($this->me->isGroupAdmin()) {
                if ($info['authority_group_id'] == $this->me->get("authority_group_id")) {
                    return;
                }
                $this->displayErrorAndExit("Accès refusé", "");
            } // @codeCoverageIgnore

            if ($info['id'] == $this->me->get('authority_id')) {
                return;
            }
            $this->displayErrorAndExit("Accès refusé", "");
        } // @codeCoverageIgnore
    }

    public function verifUser()
    {
        $this->me = new User();
        $this->me->authenticate();
    }
    // @codeCoverageIgnore

    public function displayErrorAndExit($error_message, $url_redirect)
    {
        if ($this->isApiCall()) {
            $json = new JSONoutput();
            $json->displayErrorAndExit($error_message);
        } //@codeCoverageIgnore
        $this->setErrorMessage($error_message);
        if (TESTING_ENVIRONNEMENT) {
            throw new RedirectException("Redirect to $url_redirect with message : $error_message");
        }
        $this->redirectSSL($url_redirect);
    }

    /**
     * @return SQLQuery
     */
    public function getSQLQuery()
    {
        return $this->objectInstancier->get(SQLQuery::class);
    }

    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     */
    public function redirect($url, $error_message = "")
    {
        if ($error_message) {
            $this->setErrorMessage($error_message);
        }
        if (!TESTING_ENVIRONNEMENT) {
            header("Location: $url");
            exit();
        }
        throw new RedirectException("Redirect to $url with message : $error_message");
    }

    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     */
    public function verifSuperAdmin()
    {
        $this->verifAdmin();
        if (!$this->me->isSuper()) {
            $this->redirect(WEBSITE_SSL, 'Accès refusé');
        } // @codeCoverageIgnore
    }

    public function _actionBefore($controller, $action)
    {
        $this->setViewParameter('title', "S2low");
        $this->setViewParameter(
            'template_milieu',
            __DIR__ . "/../template/" . ucfirst($controller) . ucfirst($action) . ".php"
        );
        $this->setViewParameter('side_bar', false);
    }

    public function _actionAfter()
    {
        $this->renderDefault();
    }

    public function renderDefault()
    {
        $doc = new HTMLLayout();
        $doc->setTitle($this->getViewParameter('title'));

        $doc->openContainer();
        $doc->openSideBar();
        if ($this->me) {
            $doc->buildMenu($this->me);
        }

        $doc->addBody($this->getViewParameter('side_bar'));

        $doc->closeSideBar();
        $doc->openContent();

        ob_start();
        $this->render($this->getViewParameter('template_milieu'));
        $html = ob_get_contents();
        ob_end_clean();

        $doc->addBody($html);
        $doc->closeContent();
        $doc->closeContainer();

        $doc->buildFooter();

        $doc->display();
    }

    public function render($template)
    {
        foreach ($this->viewParameter as $key => $value) {
            $$key = $value;
        }
        include($template);
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function controller_exit()
    {
        exit_wrapper();
    }

    public function log($message)
    {
        Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, $this->me->get("role"), false, $this->me);
    }

    public function getUser(): User
    {
        return $this->me;
    }

    /**
     * @return MessageAdminSQL
     */
    protected function getMessageAdminSQL()
    {
        return $this->getObjectInstancier()->get(MessageAdminSQL::class);
    }

    public function getObjectInstancier()
    {
        return $this->objectInstancier;
    }
}
