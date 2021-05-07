<?php
    class XadesSignatureParser{
        public function extractSigningTime(SimpleXMLElement $XMLElement, $target){
            //TODO : clarifier les expressions Xpath...
            $xpath ="//*[contains(namespace-uri(),'http://uri.etsi.org/01903/v')][local-name()='QualifyingProperties']";
            foreach ($XMLElement->xpath($xpath) as $xmlElement){
                if(strval($xmlElement->attributes()->Target) === "#".$target){
                    $signingTime = new DateTime($xmlElement->children($xmlElement->getNamespaces()["xad"])
                        ->SignedProperties
                        ->SignedSignatureProperties
                        ->SigningTime);
                    return $signingTime->format("Y-m-d G:i:s");
                }
            }
            return false;
        }

        public function extractSigningTimeTimestamp(SimpleXMLElement $XMLElement, $target){
            $date = $this->extractSigningTime( $XMLElement, $target);
            if($date){
                try{
                    return date_timestamp_get(new DateTime($date));
                } catch (Exception $exception){
                    return false;
                }
            }
            return false;
        }
    }