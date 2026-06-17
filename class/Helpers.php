<?php

namespace S2lowLegacy\Class;

use S2low\Helpers\CertificatHelper;
use S2low\Helpers\DateHelper;
use S2low\Helpers\FichierHelper;
use S2low\Helpers\FormatHelper;
use S2low\Helpers\RequeteHelper;
use S2low\Helpers\SessionHelper;

class Helpers
{
    public static $last_error;

    private static function getHelper(string $class)
    {
        return LegacyObjectsManager::getObject($class);
    }

    public static function getFiles($name, bool $allowGetApiCall = false)
    {
        return self::getHelper(RequeteHelper::class)->getFiles($name, $allowGetApiCall);
    }

    public static function getFilesFromArray($name, bool $allowGetApiCall = false)
    {
        return self::getHelper(RequeteHelper::class)->getFilesFromArray($name, $allowGetApiCall);
    }

  /**
   * \brief Méthode renvoyant une variable récupérée depuis une requête POST
   * \param $name chaîne : nom de la variable à récupérer
   * \param $memorize booléen (optionnel) : Détermine si la variable doit être enregistré dans la session
   * \return La valeur de la variable ou null si la variable est introuvable
  */
    public static function getVarFromPost($name, $memorize = false, bool $allowGetApiCall = false)
    {
        return self::getHelper(RequeteHelper::class)->getVarFromPost($name, $memorize, $allowGetApiCall);
    }

    public static function getIntFromPost($name, $nullable = false, bool $memorize = false)
    {
        return self::getHelper(RequeteHelper::class)->getIntFromPost($name, $nullable, $memorize);
    }
  /**
   * \brief Méthode renvoyant une variable récupérée depuis une requête GET
   * \param $name chaîne : nom de la variable à récupérer
   * \param $memorize booléen (optionnel) : Détermine si la variable doit être enregistré dans la session
   * \return La valeur de la variable ou null si la variable est introuvable
  */
    public static function getVarFromGet($name, $memorize = false)
    {
        return self::getHelper(RequeteHelper::class)->getVarFromGet($name, $memorize);
    }

    public static function getIntFromGet($name, $nullable = false)
    {
        return self::getHelper(RequeteHelper::class)->getIntFromGet($name, $nullable);
    }

    public static function getDateFromGet($name, $nullable = false)
    {
        return self::getHelper(RequeteHelper::class)->getDateFromGet($name, $nullable);
    }

  /**
   * \brief Méthode renvoyant une variable récupérée depuis une requête HTTP
   * \param $name chaîne : nom de la variable à récupérer
   * \param $type chaîne : type de la requête GET ou POST
   * \param $memorize booléen (optionnel) : Détermine si la variable doit être enregistré dans la session
   * \return La valeur de la variable ou null si la variable est introuvable
  */
    public static function getVarFromRequest($name, $type, $memorize = false)
    {
        return self::getHelper(RequeteHelper::class)->getVarFromRequest($name, $type, $memorize);
    }

  /**
   * \brief Méthode de suppression des échappements dans une chaîne
   * \param $str chaîne : chaîne à traiter
   * \return La chaîne sans échappement
  */
    public static function stripSlashes($str)
    {
        return self::getHelper(FormatHelper::class)->stripSlashes($str);
    }

  /**
   * \brief Méthode renvoyant une variable présente dans la session
   * \param $name chaîne : nom de la variable à récupérer
   * \param $delete booléen (optionnel) : Détermine si la variable doit être supprimé après récupération (true par défaut)
   * \return La valeur de la variable ou null si la variable est introuvable
  */
    public static function getFromSession($name, $delete = true)
    {
        return self::getHelper(SessionHelper::class)->getFromSession($name, $delete);
    }

  /**
   * \brief Méthode d'ajout d'une variable dans la session
   * \param $name chaîne : nom de la variable à enregistrer
   * \param $value chaîne : Valeur de la variable
  */
    public static function putInSession($name, $value)
    {
        self::getHelper(SessionHelper::class)->putInSession($name, $value);
    }

  /**
   * \brief Méthode effacant les variables temporaires de la session
  */
    public static function purgeTempSession()
    {
        self::getHelper(SessionHelper::class)->purgeTempSession();
    }

  /**
   * \brief Méthode de redirection et renvoi de status prenant en compte le type de client (API ou formulaire Web)
   * \param $status entier : statut global du retour (0 : succès, 1 : erreur)
        //FIXME : n'importe quoi, c'est une inversion de true=1 et false=0!!!!
   * \param $msg chaîne : message à renvoyer
   * \param $redirect (optionnel) : URL vers laquelle rediriger
   * \param $apiMsg (optionnel) : message renvoyé dans le cas d'un appel par API (sinon $msg)
  */
    public static function returnAndExit($status, $msg, $redirect = null, $apiMsg = null): never
    {
        /** @var \S2low\Helpers\RequeteHelper $helper */
        $helper = self::getHelper(RequeteHelper::class);
        $helper->returnAndExit($status, $msg, $redirect, $apiMsg);
    }

  /**
   * \brief Méthode de conversion d'une date au format YYYY-MM-DD vers un timestamp
   * \param $date chaîne : Date au format YYYY-MM-DD
   * \param $at_midnight booléen (optionnel) : Générer le timestamp à minuit (à midi par défaut)
   * \return Le timestamp correspondant
  */
    public static function ansiDateToTimestamp($date, $at_midnight = false)
    {
        return self::getHelper(DateHelper::class)->ansiDateToTimestamp($date, $at_midnight);
    }

    public static function TimestampToString($timestamp)
    {
        return self::getHelper(DateHelper::class)->TimestampToString($timestamp);
    }

  /**
   * \brief Méthode traitant une variable récupérée depuis une base de données (prise en compte de l'échappement)
   * \param $var mixed : valeur récupérée depuis la base de données
   * \return La valeur de la variable après traitement
  */
    public static function getFromBDD($var)
    {
        return self::getHelper(FormatHelper::class)->getFromBDD($var);
    }

  /**
   * \brief Méthode d'échappement des guillemets double
   * \param $str chaîne : chaîne à échappée
   * \return La chaîne avec tous les guillemets doubles précédés d'un \
  */
    public static function escapeForXML($str)
    {
        return self::getHelper(FormatHelper::class)->escapeForXML($str);
    }

  /**
   * \brief Méthode de récupération d'un champ depuis un objet SimpleXMLElement
   * \param $elt SimpleXMLElement : objet dont extraire la valeur
   * \return La chaîne correspondante en ISO-8859-1
  */
    public static function getFromXMLElt($elt)
    {
        return self::getHelper(FormatHelper::class)->getFromXMLElt($elt);
    }

  /**
   * \brief Méthode de troncature d'une chaine à une longueur donnée
   * \param $str chaîne : chaîne à tronquer
   * \param $length entier (optionnel) : longueur résiduelle de la chaîne (40 par défaut)
   * \param $add_ellipsis booléen (optionnel) : ajouter une ellipse à la fin de la chaîne (true par défaut)
   * \return La chaîne avec tous les guillemets doubles précédés d'un \
  */
    public static function truncateString($str, $length = 40, $add_ellipsis = true)
    {
        return self::getHelper(FormatHelper::class)->truncateString($str, $length, $add_ellipsis);
    }

  /**
   * \brief Méthode qui renvoit une heure bien formatée depuis un format HH:MM:SS
   * \param $hour chaîne : chaîne d'heure sous la forme HH:MM:SS
   * \return La chaine bien formatée HHh MMmin SSs
  */
    public static function getPrettyHours($hour)
    {
        return self::getHelper(DateHelper::class)->getPrettyHours($hour);
    }

  /**
   * \brief Méthode qui renvoit un timestamp correspondant à une date issue de la base de données
   * \param $date chaîne : chaîne de date issue de la base de données (YYYY-MM-DD HH:MM:SS+TZ)
   * \return Le timestamp correspondant
  */
    public static function getTimestampFromBDDDate($date)
    {
        return self::getHelper(DateHelper::class)->getTimestampFromBDDDate($date);
    }

  /**
   * \brief Méthode qui renvoit une date formatée issue de la base de données
   * \param $date chaîne : chaîne de date issue de la base de données (YYYY-MM-DD HH:MM:SS+TZ)
   * \param $with_hours booleén (optionnel) : Si true, la chaîne incluera les heures (false par défaut)
   * \return La chaîne correspondant à la date
  */
    public static function getDateFromBDDDate($date, $with_hours = false)
    {
        return self::getHelper(DateHelper::class)->getDateFromBDDDate($date, $with_hours);
    }

  /**
   * \brief Méthode qui renvoit une date ANSI issue de la base de données
   * \param $date chaîne : chaîne de date issue de la base de données (YYYY-MM-DD HH:MM:SS+TZ)
   * \return La chaîne correspondant à la date au format YYYY-MM-DD
  */
    public static function getANSIDateFromBDDDate($date)
    {
        return self::getHelper(DateHelper::class)->getANSIDateFromBDDDate($date);
    }

  /**
   * \brief Méthode de construction d'une URL avec un paramètre spécifié en préservant les paramètres existant
   * \param $params tableau : tableau ayant pour clef les noms des paramètres à ajouter dans l'URL et pour valeur les valeurs des paramètres
   * \return L'URL générée
   */
    public static function getURLWithParam($params)
    {
        return self::getHelper(RequeteHelper::class)->getURLWithParam($params);
    }

  /**
   * \brief Méthode de création d'une arborescence de répertoire (sous ACTES_FILES_UPLOAD_ROOT par défaut)
   * \param $path chaîne : chemin absolu vers l'arborescence à créer
   * \param $base chaîne (optionnel) : répertoire de base de la création (ACTES_FILES_UPLOAD_ROOT par défaut)
   * \return True en cas de succès, false sinon
   */
    public static function createDirTree($path, $base = ACTES_FILES_UPLOAD_ROOT)
    {
        return self::getHelper(FichierHelper::class)->createDirTree($path, $base);
    }

  /**
   * \brief Méthode de suppression de fichiers et répertoires
   * \param .. Liste variables de fichiers et répertoires à supprimer
   * \return True en cas de succès, false sinon
   */
    public static function deleteFromFS()
    {
        return self::getHelper(FichierHelper::class)->deleteFromFS(...func_get_args());
    }

  /**
   * \brief Méthode de modification des permissions en fonction de la configuration actuelle
   * \param $path chaîne : chemin vers le fichier ou répertoire dont modifier les permissions
   * \return True en cas de succès, false sinon
   */
    public static function fixPerms($path)
    {
        return self::getHelper(FichierHelper::class)->fixPerms($path);
    }

  /**
   * \brief Méthode de récupération des informations des certificats reconnues par le système
   * \param $path chaîne (optionnel) : chemin vers le répertoire contenant les certificats (defaut EXTENDED_VALIDCA_PATH)
   * \return Un tableau des données des certificats
   */
    public static function getAuthorizedCACerts($path = EXTENDED_VALIDCA_PATH)
    {
        return self::getHelper(CertificatHelper::class)->getAuthorizedCACerts($path);
    }

  /**
   * \brief Méthode d'envoi d'un fichier au navigateur
   * \param $path chaîne : chemin vers le fichier à envoyer, si null envoi des en-têtes uniquement
   * \param $filename chaîne : nom du fichier dans le navigateur
   * \param $content_type chaîne (optionnel) : content-type du fichier
   * \return True en cas de succès, false sinon
   */
    public static function sendFileToBrowser($path, $filename, $content_type = null)
    {
        return self::getHelper(FichierHelper::class)->sendFileToBrowser($path, $filename, $content_type);
    }

  /**
   * \brief Méthode de génération d'un nom temporaire
   * \param $length entier (optionnel) : longueur du suffixe aléatoire
   * \param $prefix booleén (optionnel) : ajouter ou non le préfixe "__tmp__" devant la chaine générée (défaut true)
  */
    public static function genTempName($length = 8, $prefix = true)
    {
        return self::getHelper(FichierHelper::class)->genTempName($length, $prefix);
    }

  /**
   * \brief Méthode de récupération du type d'un fichier
   * \param $path chaine : chemin vers le fichier
   * \return Le type MIME du fichier ou null en cas d'erreur
  */
    public static function getFileType($path)
    {
        return self::getHelper(FichierHelper::class)->getFileType($path);
    }

    /**
     * @param string|null $var
     * @param bool $nullable
     * @param $name
     * @return string|null
     */
    public static function checkInt(?string $var, bool $nullable, $name): ?string
    {
        return self::getHelper(FormatHelper::class)->checkInt($var, $nullable, $name);
    }

    public static function checkDate(?string $var, bool $nullable, string $name)
    {
        return self::getHelper(DateHelper::class)->checkDate($var, $nullable, $name);
    }

    public static function chunkString($string, $length)
    {
        return self::getHelper(FormatHelper::class)->chunkString($string, $length);
    }

    public static function getLink(string $relativePath): string
    {
        return self::getHelper(RequeteHelper::class)->getLink($relativePath);
    }

    /**
     * @throws \Exception
     */
    public static function exitOrDisplayError($api, $erreur_msg, $location)
    {
        self::getHelper(RequeteHelper::class)->exitOrDisplayError($api, $erreur_msg, $location);
    }
}
