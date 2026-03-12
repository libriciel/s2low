<?php

namespace S2lowLegacy\Controller;

use Exception;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Lib\FancyDate;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\SirenFactory;
use S2lowLegacy\Lib\Siret;
use S2lowLegacy\Lib\SiretFactory;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\AuthorityTypesSQL;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\MessageAdmin;
use S2lowLegacy\Model\UserSQL;

class AdminController extends Controller
{
    public array|false $authority_info;
    public string $authority_id;
    protected array $siret_list;
    protected array $siret_blocked_list;
    protected Siret $siret_exemple;
    protected string $ftype;
    protected string $fname;
    protected string $fgroup;
    protected string $api;
    protected string $fsiret;
    protected int $count;
    protected int $page_number;
    protected int $taille_page;
    public array $authorities;
    protected array $authority_types;
    protected array $groupe_list;
    protected int $offset;
    protected array $message_list;
    protected FancyDate $fancyDate;
    protected MessageAdmin $messageAdmin;

    public function _actionBefore($controller, $action)
    {
        parent::_actionBefore($controller, $action);
    }

    /**
     * @return AuthoritySiretSQL
     */
    private function getAuthoritySiretSQL()
    {
        return $authoritySiretSQL = $this->getObjectInstancier()->get(AuthoritySiretSQL::class);
    }

    /**
     * @throws RedirectException
     */
    public function authoritySiretAction()
    {
        $recuperateur = $this->getRecuperateurGet();
        $id = $recuperateur->getInt('id');
        $this->siret = $recuperateur->get('siret');

        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $this->authority_info = $authoritySQL->getInfo($id);
        if (! $this->authority_info) {
            $this->displayErrorAndExit("Aucune collectivité trouvée", "/admin/authorities/admin_authorities.php");
        } // @codeCoverageIgnore

        $this->verifAdmin($id);

        $this->authority_id = $id;
        $authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
        $this->siret_list = $authoritySiret->siretList($id);
        $this->siret_blocked_list = $authoritySiret->siretListBlocked($id);
        if ($this->isApiCall()) {
            $result = array();
            foreach ($this->siret_list as $siret_info) {
                $result[]  = $siret_info['siret'];
            }
            $json = new JSONoutput();
            $json->display($result);

            $this->controller_exit();
        } //@codeCoverageIgnore

        $this->siret_exemple = $this->getSiretFactory()->generate();
        $this->setViewParameter('title', "Numéros SIRET - {$this->authority_info['name']}");
    }

    /**
     * @throws RedirectException
     */
    public function authoritySiretAddAction()
    {
        $this->verifSuperAdmin();
        $recuperateur = $this->getRecuperateurPost();
        $authority_id = $recuperateur->getInt('authority_id');
        $siret = $this->getSiretFactory()->get($recuperateur->get('siret'));

        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $this->authority_info = $authoritySQL->getInfo($authority_id);
        if (! $this->authority_info) {
            $this->displayErrorAndExit("Aucune collectivité trouvée", "/admin/authorities/admin_authorities.php");
        } // @codeCoverageIgnore


        if (! $siret->isValid()) {
            $this->displayErrorAndExit("Le numéro SIRET n'est pas valide", "/admin/authorities/admin_authority_siret.php?id=$authority_id&siret={$siret->getValue()}");
        } // @codeCoverageIgnore

        $authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
        $authoritySiret->add($authority_id, $siret->getValue());
        $this->displayAndExit("Numéro SIRET ajouté", "/admin/authorities/admin_authority_siret.php?id=$authority_id");
    }
    // @codeCoverageIgnore

    public function authoritySiretDelAction()
    {
        $this->verifSuperAdmin();
        $recuperateur = $this->getRecuperateurPost();
        $authority_siret_id = $recuperateur->getInt('authority_siret_id');
        $authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
        $info = $authoritySiret->getInfo($authority_siret_id);
        $authoritySiret->del($authority_siret_id);
        $this->displayAndExit("Numéro SIRET retiré", "/admin/authorities/admin_authority_siret.php?id={$info['authority_id']}&siret={$info['siret']}");
    }
    // @codeCoverageIgnore


    /**
     * @throws RedirectException
     */
    public function authoritySiretBlockedAction()
    {
        $this->verifSuperAdmin();
        $authority_siret_id = $this->getEnvironnement()->post()->getInt('authority_siret_id');
        $this->getAuthoritySiretSQL()->blocked($authority_siret_id);
        $this->redirectToSiretPage($authority_siret_id, "Le SIRET a été bloqué");
    }

    /**
     * @throws RedirectException
     */
    public function authoritySiretUnblockedAction()
    {
        $this->verifSuperAdmin();
        $authority_siret_id = $this->getEnvironnement()->post()->getInt('authority_siret_id');
        $this->getAuthoritySiretSQL()->unblocked($authority_siret_id);
        $this->redirectToSiretPage($authority_siret_id, "Le SIRET a été débloqué");
    }

    /**
     * @param $authority_siret_id
     * @param $message
     * @throws RedirectException
     */
    private function redirectToSiretPage($authority_siret_id, $message)
    {
        $info = $this->getAuthoritySiretSQL()->getInfo($authority_siret_id);
        $this->redirect("/admin/authorities/admin_authority_siret.php?id={$info['authority_id']}&siret={$info['siret']}", $message);
    }

    /**
     * @return Siret
     */
    private function getSiretFactory(): SiretFactory
    {
        return $this->getObjectInstancier()->get(SiretFactory::class);
    }

    private function getSirenFactory(): SirenFactory
    {
        return $this->getObjectInstancier()->get(SirenFactory::class);
    }

    public function authoritiesAction()
    {
        $this->verifAdmin();
        $pagerHTML  = new PagerHTML();
        $this->setViewParameter('title', "Gestion des collectivités | S²low");
        $recuperateur = $this->getRecuperateurGet();

        $this->ftype =  $recuperateur->get("type");
        $this->fname = $recuperateur->get("name");
        $this->fgroup = $recuperateur->get("group");
        $this->api = $recuperateur->get("api");
        $this->fsiren = $this->getSirenFactory()->get($recuperateur->get("siren"))->getValue();
        $this->fsiret = $recuperateur->get("siret");
        $this->count = (int) $recuperateur->get("count") ?: 10;
        $this->page_number = $recuperateur->getInt('page', 1);
        $this->taille_page =  $recuperateur->getInt('count', 10);


        $user_authority_group_id = $this->userContext->me->get('authority_group_id');
        if ($this->userContext->me->isGroupAdmin()) {
            $this->fgroup = $user_authority_group_id;
        }

        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $this->authorities = $authoritySQL->getList($this->fgroup, $this->ftype, $this->fname, $this->fsiren, $this->fsiret, ($this->page_number - 1) * $this->taille_page, $this->taille_page);

        $nb_authorities = $authoritySQL->getNb($this->fgroup, $this->ftype, $this->fname, $this->fsiren, $this->fsiret);

        if ($this->api) {
            $jsonOutput = new JSONoutput();
            $jsonOutput->retrictAndDisplay($this->authorities, array('id','name','authority_group_id','siren','address','city','postal_code','telephone'));
            $this->controller_exit();
        }

        $authorityTypes = new AuthorityTypesSQL($this->getSQLQuery());
        $this->authority_types = $authorityTypes->getChildList();

        $this->setViewParameter('side_bar', $pagerHTML->getHTML($this->page_number, $nb_authorities, $this->taille_page));

        if ($this->userContext->me->isGroupAdmin()) {
            $userSQL = new UserSQL($this->getSQLQuery());
            $group_name = $userSQL->getGroupeName($this->userContext->me->getId());
            $this->setViewParameter('titre', "Gestion des collectivités du groupe $group_name");
            $this->groupe_list = [];
        } else {
            $this->setViewParameter('titre', "Gestion des collectivités");
            $groupeSQL = new GroupSQL($this->getSQLQuery());
            $this->groupe_list = $groupeSQL->getAll();
        }
    }


    public function messageAction()
    {
        $this->verifSuperAdmin();
        $recuperateur = $this->getRecuperateurGet();
        $this->offset = $recuperateur->getInt('offset', 0);
        $this->message_list = $this->getMessageAdminSQL()->getAll($this->offset, 100);
        $this->fancyDate = $this->getObjectInstancier()->get(FancyDate::class);
    }

    public function messageEditAction()
    {
        $this->verifSuperAdmin();
        $recuperateur = $this->getRecuperateurGet();
        $message_id = $recuperateur->getInt('message_id');
        $this->messageAdmin = $this->getMessageAdminSQL()->getMessage($message_id);
    }

    /**
     * @throws RedirectException
     * @throws Exception
     */
    public function doMessageEditAction()
    {
        $this->verifSuperAdmin();
        $recuperateur = $this->getRecuperateurPost();
        $message_id = $recuperateur->getInt('message_id');
        $titre = $recuperateur->get('titre');
        $message = $recuperateur->get('message');
        $niveau = $recuperateur->get('niveau');
        $user_id = $this->userContext->me->getId();
        $message_id = $this->getMessageAdminSQL()->edit($message_id, $titre, $message, $user_id, $niveau);
        $this->redirect("/admin/message/detail.php?message_id=$message_id");
    }

    /**
     * @throws RedirectException
     */
    public function messagePublierAction()
    {
        $this->verifSuperAdmin();
        $recuperateur = $this->getRecuperateurGet();
        $message_id = $recuperateur->getInt('message_id');
        $user_id = $this->userContext->me->getId();
        $this->getMessageAdminSQL()->publier($message_id, $user_id);
        $this->redirect("/admin/message/detail.php?message_id=$message_id");
    }

    /**
     * @throws RedirectException
     */
    public function messageRetirerAction()
    {
        $this->verifSuperAdmin();
        $recuperateur = $this->getRecuperateurGet();
        $message_id = $recuperateur->getInt('message_id');
        $user_id = $this->userContext->me->getId();
        $this->getMessageAdminSQL()->retirer($message_id, $user_id);
        $this->redirect("/admin/message/detail.php?message_id=$message_id");
    }

    public function messageDetailAction()
    {
        $this->verifSuperAdmin();
        $recuperateur = $this->getRecuperateurGet();
        $message_id = $recuperateur->getInt('message_id');
        $this->{'messageAdmin'} = $this->getMessageAdminSQL()->getMessage($message_id);
        $this->{'fancyDate'} = $this->getObjectInstancier()->get(FancyDate::class);
    }
}
