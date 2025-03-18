<?php

namespace S2lowLegacy\Lib;

use Exception;

class JSONoutput
{
    public function displayErrorAndExit($Errormessage)
    {
        $result['status'] = 'error';
        $result['error-message'] = $Errormessage;;
        $this->display($result);
        exit_wrapper();  // @codeCoverageIgnore
    }

    public function displayAndExit($message)
    {
        $result['status'] = 'ok';
        $result['message'] = $message;;
        $this->display($result);
        if (TESTING_ENVIRONNEMENT) {
            throw new Exception("Exit !");
        }
        exit;  // @codeCoverageIgnore
    }


    private function normalize($array)
    {
        if (!is_array($array)) {
            return $array ?? "";
        }
        $result = array();
        foreach ($array as $cle => $value) {
            $result[$cle] = $this->normalize($value);
        }
        return $result;
    }

    public function retrictAndDisplay($data, array $colname_to_display)
    {
        $result = array();
        foreach ($data as $line) {
            $node = array();
            foreach ($colname_to_display as $name) {
                $node[$name] = $line[$name];
            }
            $result[] = $node;
        }
        $this->display($result);
    }

    public function display(array $array, bool $compatibilityMode = true)
    {
        header_wrapper("Content-type: text/plain");
        $array = $this->normalize($array);
        $jsonResult = json_encode($array);
        if ($compatibilityMode) {                        // L'API de la v4 sortait le JSON en ISO
            $jsonResult = utf8_decode($jsonResult);    // On réalise donc la conversion pour
        }                                              // garder la compatibilité.
        echo $jsonResult;
    }
}
