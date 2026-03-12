<?php

namespace S2lowLegacy\Controller;

use Exception;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

class AdminUserController extends Controller
{
    /**
     * @var UserSQL
     */
    private $userSQL;

    public function __construct(ObjectInstancier $objectInstancier, UserContext $userContext)
    {
        parent::__construct($objectInstancier, $userContext);
        $this->userSQL = new UserSQL($this->getSQLQuery());
    }

    private function getFromFile($name)
    {
        if (empty($_FILES[$name])) {
            return false;
        }
        if (! is_uploaded_file_wrapper($_FILES[$name]['tmp_name'])) {
            return false;
        }
        return file_get_contents($_FILES[$name]['tmp_name']);
    }

    /**
     * @param $my_user_id
     * @param Recuperateur $recuperateur
     * @throws Exception
     */
    private function doEdit($my_user_id, Recuperateur $recuperateur)
    {
        $api = $recuperateur->getInt('api');

        $user_id = $recuperateur->getInt('id');
        $user_id_a_cloner = $recuperateur->getInt('new_id');

        $authority_id = $recuperateur->getInt('authority_id');
        $role = $recuperateur->get('role');
        $authority_group_id = $recuperateur->get('authority_group_id');
        $login = $recuperateur->get('login');
        $password = $recuperateur->get('password');
        $password2 = $recuperateur->get('password2');

        $auth_method = $recuperateur->get("auth_method");


        if ($api && ! $authority_id) {
            //Il faut penser au cas où on on est en modification et ou on passe pas l'authority_id... c'est  nul...
            throw new Exception("authority_id est obligatoire");
        }

        if ($role == 'GADM' && ! $authority_group_id) {
            throw new Exception("Vous devez indiquer un groupe pour créer un administrateur de groupe");
        }

        if (! $api && $password != $password2) {
            throw new Exception("Les mots de passe ne correspondent pas");
        }

        $x509Certificate = new X509Certificate();

        $user_info = false;
        if ($user_id) {
            $userSQL = new UserSQL($this->getSQLQuery());
            $user_info = $userSQL->getInfo($user_id);
            if (!$user_info) {
                throw new Exception("Erreur lors de la modification de l'utilisateur");
            }
        } elseif ($user_id_a_cloner) {
            $userSQL = new UserSQL($this->getSQLQuery());
            $user_info = $userSQL->getInfo($user_id_a_cloner);
            if (!$user_info) {
                throw new Exception("Erreur lors du clonage de l'utilisateur");
            }
            if (! $login || ! $password) {
                throw new Exception("Le login et le mot de passe sont obligatoire pour cloner un certificat");
            }
        }

        if ($user_info) {
            $certificat_connexion_info = $x509Certificate->getInfo($user_info['certificate']);
        } else {
            $certificat_connexion_info = false;
        }


        $certificat_connexion = $this->getFromFile('certificate');

        if ($certificat_connexion) {
            $certificat_connexion_info = $x509Certificate->getInfo($certificat_connexion);
        }

        if (! $certificat_connexion_info) {
            throw new Exception("Le certificat utilisateur est obligatoire");
        }

        if ($auth_method == UserSQL::IDENT_METHOD_CERT_ONLY) { // Normalement, login devrait être vide ...
            $login = '';                                       // On s'en assure pour éviter un doublon
        }

        if ($this->userSQL->hasDoublon($user_id, $certificat_connexion_info, $login)) {
            throw new Exception("Un utilisateur avec les mêmes informations de connexion et d'identification existe dans la base S2low");
        }

        //OK ALL GOOD !
    }

    /**
     * @return bool|null
     * @throws RedirectException
     */
    public function doEditAction()
    {
        $this->verifAdmin();
        $my_user_id = $this->me->getId();

        $api = $this->getEnvironnement()->post()->get('api');
        /** @var string $id */
        $id = $this->getEnvironnement()->post()->get('id');

        if (!(is_null($id) || !$id) && !ctype_digit($id)) {
            $this->displayErrorAndExit("Le paramètre id doit être un entier", "/");
        }

        $name = $this->getEnvironnement()->post()->get('name');
        Helpers::putInSession("name", $name);

        $givenname = $this->getEnvironnement()->post()->get('givenname');
        Helpers::putInSession("givenname", $givenname);

        $email = $this->getEnvironnement()->post()->get('email');
        Helpers::putInSession("email", $email);

        $telephone = $this->getEnvironnement()->post()->get('telephone');
        Helpers::putInSession("telephone", $telephone);

        $status = $this->getEnvironnement()->post()->getInt('status');
        Helpers::putInSession("status", $status);

        $authority_id = $this->getEnvironnement()->post()->get('authority_id');
        Helpers::putInSession("authority_id", $authority_id);

        $role = $this->getEnvironnement()->post()->get('role');
        Helpers::putInSession("role", $role);

        $authority_group_id = $this->getEnvironnement()->post()->get('authority_group_id');
        Helpers::putInSession("authority_group_id", $authority_group_id);

        $new_id = $this->getEnvironnement()->post()->get('new_id');
        Helpers::putInSession("new_id", $new_id);

        $auth_method = $this->getEnvironnement()->post()->get('auth_method');
        Helpers::putInSession("auth_method", $auth_method);

        if ($auth_method == UserSQL::IDENT_METHOD_CERT_ONLY) {      // Normalement, login et password devraient être vides ...
            $this->getEnvironnement()->post()->set('login', '');
            $this->getEnvironnement()->post()->set('password', ''); // On les reset au cas ou il y a un effet de cache du nav.
            $this->getEnvironnement()->post()->set('password2', '');
        }

        $login = $this->getEnvironnement()->post()->get('login');
        Helpers::putInSession("login", $login);

        $password = $this->getEnvironnement()->post()->get('password');
        Helpers::putInSession("password", $password);

        $password2 = $this->getEnvironnement()->post()->get('password2');
        Helpers::putInSession("password2", $password2);

        $certificate = $_FILES['certificate'] ?? [];

        $me = new User();

        if (! $me->authenticate()) {
            $this->displayErrorAndExit("Échec de l'authentification", "/");
        }

        if (! $me->isAdmin()) {
            $this->displayErrorAndExit("Accès refusé", "/");
        }

        $myAuthority = new Authority($me->get("authority_id"));


        $him = new User();
        $mod = false;

        if ($id) {
            $him->setId($id);
            if (! $him->init()) {
                $this->displayErrorAndExit("Erreur lors de la modification de l'utilisateur", "/admin/users/admin_users.php");
            }

            // On vérifie que l'utilisateur courant à le droit de modifier cet utilisateur
            if (! $me->canEditUser($id)) {
                $this->displayErrorAndExit(
                    "Accès refusé pour la modification de cet utilisateur",
                    "/admin/users/admin_users.php"
                );
            }
            $mod = true;
        }


        try {
            $this->doEdit($my_user_id, $this->getRecuperateurPost());
        } catch (Exception $e) {
            $this->displayErrorAndExit($e->getMessage(), "/admin/users/admin_user_edit.php?id=$id&new_id=$new_id");
        }


        $him->set("name", $name);
        $him->set("givenname", $givenname);
        $him->set("email", $email);
        $him->set("telephone", $telephone);
        $him->set("status", $status);
        $him->set("login", $login);
        if ($password) {
            $him->set("password", password_hash($password, PASSWORD_DEFAULT));
        }

        if ($auth_method == UserSQL::IDENT_METHOD_CERT_ONLY) {
            $him->set('login', '');                              // On reset login et password si on demande à changer
            $him->set('password', '');                           // de méthode d'authentification
        }

        // Le groupe d'appartenance pour un administrateur de groupe
        if ($me->isSuper()) {
            $him->set("authority_group_id", $authority_group_id);
        }

        // Traitement du certificat
        if (is_array($certificate) && count($certificate) > 0 && is_uploaded_file_wrapper($certificate["tmp_name"])) {
            $him->set("certFilePath", $certificate["tmp_name"]);
        }

        if ($new_id) {
            if ($him->getIdFromLogin($login)) {
                $this->displayErrorAndExit("Ce login est déja utilisé", "/admin/users/admin_user_edit.php?new_id=$new_id");
            }
            $him->cloneCertificat($new_id);
        } else {
            if ($login) {
                $the_id = $him->getIdFromLogin($login);

                if ($the_id && $id != $the_id) {
                    $this->displayErrorAndExit("Ce login est déja utilisé", "/admin/users/admin_users.php");
                }
            }
        }



        if (! $mod || $new_id) {
            if ($me->isSuper()) {
                $him->set("authority_id", $authority_id);
            } elseif ($me->isGroupAdmin()) {
                $authority = new Authority($authority_id);

                if ($authority->isInGroup($me->get("authority_group_id"))) {
                    $him->set("authority_id", $authority_id);
                } else {
                    $this->displayErrorAndExit(
                        "La collectivité n'appartient pas au groupe courant",
                        "/admin/users/admin_users.php"
                    );
                }
            } else {
                // Un admin de collectivité ne peut créer que des utilisateurs appartenant à sa collectivité
                $him->set("authority_id", $myAuthority->getId());
            }
        }

        // Les admins de collectivité et de groupe ne peuvent créer que des administrateurs de collectivité
        // ou des utilisateurs simples
        if (! $me->isSuper()) {
            if (strcasecmp($role, 'SADM') == 0 || strcasecmp($role, 'GADM') == 0) {
                $role = 'ADM';
            }
        }

        $him->set("role", $role);

        if (! $him->get('authority_id')) {
            $this->displayErrorAndExit("authority_id est obligatoire", "/admin/users/admin_users.php");
        }


        // Permissions sur les modules
        // Récupération des modules actifs globalement
        $modules = Module::getActiveModulesList();
        // Récupération des modules authorisés pour la collectivité
        $authModules = Module::getModulesForAuthority($him->get("authority_id"));

        $him->resetPerms();

        foreach ($modules as $module) {
            if (isset($authModules[$module["id"]]) && ($authModules[$module["id"]] || $him->isGroupAdmin())) {
                if ($me->isSuper()) {
                    $module['specific_perms']['GRANT'] = 'Concession';
                }
                $him->setPerm($module["id"], Helpers::getVarFromPost("perm_" . $module["id"]), $module['specific_perms']);
            }
        }

        if (! $him->save()) {
            $msg = "Erreur lors de l'enregistrement de l'utilisateur :\n" . $him->getErrorMsg();
            if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
                $msg .= "\nErreur de journalisation.";
            }


            if ($him->isNew()) {
                $location = "/admin/users/admin_user_edit.php?new_id=$new_id";
            } else {
                Helpers::purgeTempSession();
                $location = "/admin/users/admin_user_edit.php?id=" . $him->getId() . "&new_id=$new_id";
            }

            $this->displayErrorAndExit(nl2br($msg), $location);
        }


        $msg = ($mod) ? "Modification" : "Création";
        $msg .= " de l'utilisateur " . $him->getPrettyName() . " (id=" . $him->getId() . "). Résultat ok.";

        $userSQL = new UserSQL($this->getSQLQuery());

        if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
            $msg .= "\nErreur de journalisation.";
        }

        if ($api) {
            $jsonOutput = new JSONoutput();
            $jsonOutput->display(array('status' => 'ok','message' => $msg,'id' => $him->getId()));
            exit_wrapper();
        } else {
            $_SESSION["error"] = nl2br($msg);
            Helpers::purgeTempSession();
            if (TESTING_ENVIRONNEMENT) {
                return $him->getId();
            } else {
                $this->redirectSSL("/admin/users/admin_user_edit.php?id=" . $him->getId());
            }
        }
        return true;
    }

    /**
     * @throws Exception
     * @throws RedirectException
     */
    public function listAction()
    {
        $this->verifAdmin();
        $user_id = $this->getRecuperateurGet()->get('user_id');
        if (! $user_id) {
            $this->redirect("/");
        }
        if (! is_numeric($user_id)) {
            $this->redirect("/");
        }
        if (!$this->me->canEditUser($user_id)) {
            $this->redirect("/");
        }

        $this->user_id = $user_id;

        $userSQL = $this->getObjectInstancier()->get(UserSQL::class);

        $x509Certificate = new X509Certificate();

        $this->user_info = $userSQL->getInfo($user_id);
        $this->certificat_expiration =  date("d/m/Y H:i:s", strtotime($x509Certificate->getExpirationDate($this->user_info['certificate'])));

        $this->user_list = $userSQL->getListFromCertificateInfo($this->user_info['certificate_hash']);

        $this->title = "Utilisateurs partageant le même certificat";

        $this->status_type_list = $this->me->get("statusTypes");
        $this->roles_type_list = User::ROLES_DESCR;
    }

    /**
     * @throws RedirectException
     */
    public function doBulkModifCertifAction()
    {
        $this->verifAdmin();

        $me = new User();
        $me->authenticate();

        $user_id = $this->getRecuperateurPost()->getInt('user_id');
        if (! $user_id) {
            $this->redirect("/", "Aucun identifiant utilisateur n'a été présenté");
        }

        $confirm = $this->getRecuperateurPost()->get('confirm');
        if ($confirm != 'OUI') {
            $this->redirect(
                "/admin/users/admin_user_list.php?user_id=$user_id",
                "Vous devez confirmer la modification"
            );
        }

        $files = $this->getFiles();

        if (empty($files['certificat'])) {
            $this->redirect(
                "/admin/users/admin_user_list.php?user_id=$user_id",
                "Vous devez fournir un certificat"
            );
        }

        $certificate_filepath = $files['certificat']['tmp_name'];

        $userSQL = $this->getObjectInstancier()->get(UserSQL::class);

        $user_info = $userSQL->getInfo($user_id);
        $list = $userSQL->getListFromCertificateInfo($user_info['certificate_hash']);
        foreach ($list as $user_info) {
            if (! $me->canEditUser($user_info['id'])) {
                continue;
            }
            $him = new User($user_info['id']);
            $him->init();
            $him->set('certFilePath', $certificate_filepath);
            if (! $him->save()) {
                $this->redirect("/admin/users/admin_user_list.php?user_id=$user_id", $him->getErrorMsg());
            }
        }

        $this->redirect("/admin/users/admin_user_list.php?user_id=$user_id", "Certificat mis à jour");
    }
}
