<?php

namespace S2lowLegacy\Class;

use S2low\Helpers\RequestHelper;
use S2low\Helpers\RedirectHelper;
use S2low\Helpers\DateHelper;
use S2low\Helpers\SessionHelper;
use S2low\Helpers\StringHelper;
use S2low\Helpers\XmlHelper;
use S2low\Helpers\FilesystemHelper;
use S2low\Helpers\CertificateHelper;
use S2low\Helpers\UrlHelper;

class Helpers
{
    public static $last_error;

    private static ?SessionHelper $sessionHelper = null;
    private static ?RequestHelper $requestHelper = null;
    private static ?RedirectHelper $redirectHelper = null;
    private static ?DateHelper $dateHelper = null;
    private static ?StringHelper $stringHelper = null;
    private static ?XmlHelper $xmlHelper = null;
    private static ?FilesystemHelper $filesystemHelper = null;
    private static ?CertificateHelper $certificateHelper = null;
    private static ?UrlHelper $urlHelper = null;

    private static function getSessionHelper(): SessionHelper
    {
        if (self::$sessionHelper === null) {
            self::$sessionHelper = new SessionHelper();
        }
        return self::$sessionHelper;
    }

    private static function getRequestHelper(): RequestHelper
    {
        if (self::$requestHelper === null) {
            self::$requestHelper = new RequestHelper(self::getSessionHelper());
        }
        return self::$requestHelper;
    }

    private static function getRedirectHelper(): RedirectHelper
    {
        if (self::$redirectHelper === null) {
            self::$redirectHelper = new RedirectHelper(self::getRequestHelper());
        }
        return self::$redirectHelper;
    }

    private static function getDateHelper(): DateHelper
    {
        if (self::$dateHelper === null) {
            self::$dateHelper = new DateHelper();
        }
        return self::$dateHelper;
    }

    private static function getStringHelper(): StringHelper
    {
        if (self::$stringHelper === null) {
            self::$stringHelper = new StringHelper();
        }
        return self::$stringHelper;
    }

    private static function getXmlHelper(): XmlHelper
    {
        if (self::$xmlHelper === null) {
            self::$xmlHelper = new XmlHelper();
        }
        return self::$xmlHelper;
    }

    private static function getFilesystemHelper(): FilesystemHelper
    {
        if (self::$filesystemHelper === null) {
            self::$filesystemHelper = new FilesystemHelper();
        }
        return self::$filesystemHelper;
    }

    private static function getCertificateHelper(): CertificateHelper
    {
        if (self::$certificateHelper === null) {
            self::$certificateHelper = new CertificateHelper();
        }
        return self::$certificateHelper;
    }

    private static function getUrlHelper(): UrlHelper
    {
        if (self::$urlHelper === null) {
            self::$urlHelper = new UrlHelper();
        }
        return self::$urlHelper;
    }

    public static function getFiles($name, bool $allowGetApiCall = false)
    {
        return self::getRequestHelper()->getFiles($name, $allowGetApiCall);
    }

    public static function getFilesFromArray($name, bool $allowGetApiCall = false)
    {
        return self::getRequestHelper()->getFilesFromArray($name, $allowGetApiCall);
    }

    public static function getVarFromPost($name, $memorize = false, bool $allowGetApiCall = false)
    {
        return self::getRequestHelper()->getVarFromPost($name, $memorize, $allowGetApiCall);
    }

    public static function getIntFromPost($name, $nullable = false, bool $memorize = false)
    {
        return self::getRequestHelper()->getIntFromPost($name, $nullable, $memorize);
    }

    public static function getVarFromGet($name, $memorize = false)
    {
        return self::getRequestHelper()->getVarFromGet($name, $memorize);
    }

    public static function getIntFromGet($name, $nullable = false)
    {
        return self::getRequestHelper()->getIntFromGet($name, $nullable);
    }

    public static function getDateFromGet($name, $nullable = false)
    {
        return self::getRequestHelper()->getDateFromGet($name, $nullable);
    }

    public static function getVarFromRequest($name, $type, $memorize = false)
    {
        return self::getRequestHelper()->getVarFromRequest($name, $type, $memorize);
    }

    public static function stripSlashes($str)
    {
        return self::getRequestHelper()->stripSlashes($str);
    }

    public static function getFromSession($name, $delete = true)
    {
        return self::getSessionHelper()->getFromSession($name, $delete);
    }

    public static function putInSession($name, $value)
    {
        self::getSessionHelper()->putInSession($name, $value);
    }

    public static function purgeTempSession()
    {
        self::getSessionHelper()->purgeTempSession();
    }

    public static function returnAndExit($status, $msg, $redirect = null, $apiMsg = null): never
    {
        self::getRedirectHelper()->returnAndExit($status, $msg, $redirect, $apiMsg);
    }

    public static function ansiDateToTimestamp($date, $at_midnight = false)
    {
        return self::getDateHelper()->ansiDateToTimestamp($date, $at_midnight);
    }

    public static function TimestampToString($timestamp)
    {
        return self::getDateHelper()->TimestampToString($timestamp);
    }

    public static function getFromBDD($var)
    {
        return self::getStringHelper()->getFromBDD($var);
    }

    public static function escapeForXML($str)
    {
        return self::getXmlHelper()->escapeForXML($str);
    }

    public static function getFromXMLElt($elt)
    {
        return self::getXmlHelper()->getFromXMLElt($elt);
    }

    public static function truncateString($str, $length = 40, $add_ellipsis = true)
    {
        return self::getStringHelper()->truncateString($str, $length, $add_ellipsis);
    }

    public static function getPrettyHours($hour)
    {
        return self::getDateHelper()->getPrettyHours($hour);
    }

    public static function getTimestampFromBDDDate($date)
    {
        return self::getDateHelper()->getTimestampFromBDDDate($date);
    }

    public static function getDateFromBDDDate($date, $with_hours = false)
    {
        return self::getDateHelper()->getDateFromBDDDate($date, $with_hours);
    }

    public static function getANSIDateFromBDDDate($date)
    {
        return self::getDateHelper()->getANSIDateFromBDDDate($date);
    }

    public static function getURLWithParam($params)
    {
        return self::getUrlHelper()->getURLWithParam($params);
    }

    public static function createDirTree($path, $base = ACTES_FILES_UPLOAD_ROOT)
    {
        return self::getFilesystemHelper()->createDirTree($path, $base);
    }

    public static function deleteFromFS()
    {
        return self::getFilesystemHelper()->deleteFromFS(...func_get_args());
    }

    public static function fixPerms($path)
    {
        return self::getFilesystemHelper()->fixPerms($path);
    }

    public static function getAuthorizedCACerts($path = EXTENDED_VALIDCA_PATH)
    {
        $result = self::getCertificateHelper()->getAuthorizedCACerts($path);
        self::$last_error = self::getCertificateHelper()->getLastError();
        return $result;
    }

    public static function sendFileToBrowser($path, $filename, $content_type = null)
    {
        $result = self::getRedirectHelper()->sendFileToBrowser($path, $filename, $content_type);
        self::$last_error = self::getRedirectHelper()->getLastError();
        return $result;
    }

    public static function genTempName($length = 8, $prefix = true)
    {
        return self::getFilesystemHelper()->genTempName($length, $prefix);
    }

    public static function getFileType($path)
    {
        return self::getFilesystemHelper()->getFileType($path);
    }

    public static function checkInt(?string $var, bool $nullable, $name): ?string
    {
        return self::getRequestHelper()->checkInt($var, $nullable, $name);
    }

    public static function checkDate(?string $var, bool $nullable, string $name)
    {
        return self::getRequestHelper()->checkDate($var, $nullable, $name);
    }

    public static function chunkString($string, $length)
    {
        return self::getStringHelper()->chunkString($string, $length);
    }

    public static function getLink(string $relativePath): string
    {
        return self::getUrlHelper()->getLink($relativePath);
    }

    public static function exitOrDisplayError($api, $erreur_msg, $location)
    {
        self::getRedirectHelper()->exitOrDisplayError($api, $erreur_msg, $location);
    }
}
