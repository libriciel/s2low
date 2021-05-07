<?php
    class XadesSignatureParser{
        /**
         * @throws \Exception
         */
        public function extractRawSigningTime(SimpleXMLElement $XMLElement, string $target){
            //TODO : clarifier les expressions Xpath...
            $xpath ="//*[contains(namespace-uri(),'http://uri.etsi.org/01903/v')][local-name()='QualifyingProperties']";
            foreach ($XMLElement->xpath($xpath) as $xmlElement){
                if(strval($xmlElement->attributes()->Target) === "#".$target){
                    return $xmlElement->children($xmlElement->getNamespaces()["xad"])
                        ->SignedProperties
                        ->SignedSignatureProperties
                        ->SigningTime;
                }
            }
            throw new Exception("SigningTime non trouvé");
        }

        public function extractXadesSigningTime(SimpleXMLElement $XMLElement, string $target): ?DateTime
        {
            try{
                return new DateTime(
                    $this->extractRawSigningTime($XMLElement,$target),
                    new DateTimeZone('Europe/London')
                );
            } catch (Exception $exception) {
                return null;
            }
        }
    }