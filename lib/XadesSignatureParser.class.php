<?php
    class XadesSignatureParser{
        public function extractSigningTime(SimpleXMLElement $XMLElement, $target){
            $xpath ="//*[namespace-uri()='http://uri.etsi.org/01903/v1.1.1#'][local-name()='QualifyingProperties']";

            foreach ($XMLElement->xpath($xpath) as $xmlElement){
                if(strval($xmlElement->attributes()->Target) === $target){
                    return $xmlElement->children('http://uri.etsi.org/01903/v1.1.1#')
                        ->SignedProperties
                        ->SignedSignatureProperties
                        ->SigningTime;
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