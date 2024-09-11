<?php

namespace S2lowLegacy\Controller;

use Exception;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\MessageAdminSQL;

class Controller
{
    protected User $me;

    private array $viewParameter = [];

    private ObjectInstancier $objectInstancier;
    private array $files;

    public function __construct(ObjectInstancier $objectInstancier)
    {
        $this->objectInstancier = $objectInstancier;
        $this->setFiles($_FILES);
    }

    /**
     * @throws \Exception
     */
    public function __get($key): mixed
    {
        return $this->getViewParameter($key);
    }

    public function __set($key, $value): void
    {
        $this->setViewParameter($key, $value);
    }

    public function isViewParameter($key): bool
    {
        return isset($this->viewParameter[$key]);
    }

    public function setViewParameter($key, $value): void
    {
        $this->viewParameter[$key] = $value;
    }

    /**
     * @throws Exception
     */
    public function getViewParameter($key): mixed
    {
        if (isset($this->viewParameter[$key])) {
            return $this->viewParameter[$key];
        }
        throw new Exception("parameter $key not found");
    }

    public function getAllViewParameter(): array
    {
        return $this->viewParameter;
    }

    public function setFiles($files): void
    {
        $this->files = $files;
    }

    /**
     * @return Environnement
     */
    protected function getEnvironnement(): Environnement
    {
        return $this->objectInstancier->get(Environnement::class);
    }

    public function setErrorMessage($error_message): void
    {
        $this->getEnvironnement()->session()->set('error', $error_message);
    }

    public function setMessage($message): void
    {
        //En attendant mieux...
        $this->getEnvironnement()->session()->set('error', $message);
    }

    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     */
    public function redirectSSL($url_path = '', $url_arg = ''): void
    {
        $url = trim(WEBSITE_SSL, '/') . '/' . trim($url_path, '/');
        if ($url_arg) {
            $url .= "?$url_arg";
        }
        if (! TESTING_ENVIRONNEMENT) {
            header("Location: $url");
            exit();
        }
        throw new RedirectException("Redirect to $url");
    }

    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     */
    public function redirect($url, $error_message = ''): void
    {
        if ($error_message) {
            $this->setErrorMessage($error_message);
        }
        if (! TESTING_ENVIRONNEMENT) {
            header("Location: $url");
            exit();
        }
        throw new RedirectException("Redirect to $url with message : $error_message");
    }

    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     * @throws \Exception
     */
    public function displayErrorAndExit($error_message, $url_redirect): void
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
    //@codeCoverageIgnore

    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     * @throws \Exception
     */
    public function displayAndExit($message, $url_redirect): void
    {
        if ($this->isApiCall()) {
            $json = new JSONoutput();
            $json->displayAndExit($message);
        } //@codeCoverageIgnore
        $this->setErrorMessage($message);
        $this->redirectSSL($url_redirect);
    }
    //@codeCoverageIgnore


    /**
     * @throws \Exception
     */
    public function verifUser(): void
    {
        $this->me = new User();
        $this->me->authenticate();
    }

    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     * @throws \Exception
     */
    public function verifAdmin($authority_id = false): void
    {
        $this->verifUser();

        if (! $this->me->isAdmin()) {
            $this->displayErrorAndExit('Accès refusé', '');
        } // @codeCoverageIgnore
        if ($this->me->isSuper()) {
            return;
        }
        if ($authority_id) {
            $authoritySQL = new AuthoritySQL($this->getSQLQuery());
            $info = $authoritySQL->getInfo($authority_id);

            if ($this->me->isGroupAdmin()) {
                if ($info['authority_group_id'] == $this->me->get('authority_group_id')) {
                    return;
                }
                $this->displayErrorAndExit('Accès refusé', '');
            } // @codeCoverageIgnore

            if ($info['id'] == $this->me->get('authority_id')) {
                return ;
            }
            $this->displayErrorAndExit('Accès refusé', '');
        } // @codeCoverageIgnore
    }

    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     */
    public function verifGroupAdmin($authority_id): void
    {
        $this->verifAdmin();
        if ($this->me->isSuper()) {
            return;
        }

        if ($this->me->isGroupAdmin()) {
            $authoritySQL = new AuthoritySQL($this->getSQLQuery());
            $info = $authoritySQL->getInfo($authority_id);
            if ($info['authority_group_id'] == $this->me->get('authority_group_id')) {
                return;
            }
        }

        $this->redirect(WEBSITE_SSL, 'Accès refusé');
    }
    // @codeCoverageIgnore


    /**
     * @throws \S2lowLegacy\Lib\RedirectException
     */
    public function verifSuperAdmin(): void
    {
        $this->verifAdmin();
        if (! $this->me->isSuper()) {
            $this->redirect(WEBSITE_SSL, 'Accès refusé');
        } // @codeCoverageIgnore
    }


    /**
     * @throws \Exception
     */
    public function renderDefault(): void
    {
        $doc = new HTMLLayout();
        $doc->setTitle($this->getViewParameter('title'));

        $doc->openContainer();
        $doc->openSideBar();
        if (isset($this->me)) {
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

    public function render($template): void
    {
        foreach ($this->viewParameter as $key => $value) {
            $$key = $value;
        }
        include($template);
    }

    public function _actionBefore($controller, $action): void
    {
        $this->setViewParameter('title', 'S2low');
        $this->setViewParameter('template_milieu', __DIR__ . '/../template/' . ucfirst($controller) . ucfirst($action) . '.php');
        $this->setViewParameter('side_bar', false);
    }

    /**
     * @throws \Exception
     */
    public function _actionAfter(): void
    {
        $this->renderDefault();
    }

    public function getRecuperateurGet(): Recuperateur
    {
        return $this->getEnvironnement()->get();
    }

    public function getRecuperateurPost(): Recuperateur
    {
        return $this->getEnvironnement()->post();
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function isApiCall(): bool
    {
        $recuperateur = $this->getRecuperateurGet();
        $api = $recuperateur->get('api');
        if ($api) {
            return true;
        }
        $recuperateur = $this->getRecuperateurPost();
        return $recuperateur->get('api');
    }

    /**
     * @return SQLQuery
     */
    public function getSQLQuery(): SQLQuery
    {
        return $this->objectInstancier->get(SQLQuery::class);
    }

    public function getObjectInstancier(): ObjectInstancier
    {
        return $this->objectInstancier;
    }

    public function controller_exit(): void
    {
        exit_wrapper();
    }

    public function log($message): void
    {
        Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, $this->me->get('role'), false, $this->me);
    }

    /**
     * @return MessageAdminSQL
     */
    protected function getMessageAdminSQL(): MessageAdminSQL
    {
        return $this->getObjectInstancier()->get(MessageAdminSQL::class);
    }

    public function getUser(): User
    {
        return $this->me;
    }
}
