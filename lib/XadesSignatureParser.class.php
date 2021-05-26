<?php
    class XadesSignatureParser{
        /**
         * @throws \Exception
         */
        public function extractRawSigningTime(SimpleXMLElement $XMLElement, string $target): string
        {
            $xpath ="//*[local-name()='QualifyingProperties' and @Target=\"#$target\"]//*[local-name()='SigningTime']";
            $result = $XMLElement->xpath($xpath);
            if(!count($result)==1){
                throw new Exception("SigningTime non trouvé");
            }
            return (string) $result[0];
        }

        public function extractXadesSigningTime(SimpleXMLElement $XMLElement, string $target): ?DateTime
        {
            try{
                return new DateTime(
                    $this->extractRawSigningTime($XMLElement,$target),
                    new DateTimeZone('UTC')
                );
            } catch (Exception $exception) {
                return null;
            }
        }
    }