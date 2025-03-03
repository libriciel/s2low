<?php

namespace IntegrationTests;

class ActesStatusTest extends S2lowIntegrationTestCase
{
    private function prepareAndGetResponse()
    {
        $client = $this->setUpUserAndGetClient();
        $client->request('GET', '/modules/actes/api/actes_status.php');
        return $client->getResponse();
    }

    public function testActesStatusReturnOk()
    {
        $response = $this->prepareAndGetResponse();

        self::assertResponseIsSuccessful($response);
    }

    public function testActesStatusJsonOk()
    {
        $response = $this->prepareAndGetResponse();
        $responseContent = $response->getContent();

        self::assertTrue(json_validate($responseContent), "Le JSON n'est pas valide.");
    }

    public function testActesStatusAreOk()
    {
        $responseModel ='
        {
          "-1": "Erreur",
          "0": "Annul\u00e9",
          "1": "Post\u00e9",
          "2": "En attente de transmission",
          "3": "Transmis",
          "4": "Acquittement re\u00e7u",
          "5": "Valid\u00e9",
          "6": "Refus\u00e9",
          "7": "Document re\u00e7u",
          "8": "Acquittement envoy\u00e9",
          "9": "Document envoy\u00e9",
          "10": "Refus d\'envoie",
          "11": "Acquittement de document re\u00e7u",
          "12": "Envoy\u00e9 au SAE",
          "13": "Archiv\u00e9 par le SAE",
          "14": "Erreur lors de l\'archivage",
          "15": "Re\u00e7u par le SAE",
          "16": "D\u00e9truite",
          "17": "En attente d\'\u00eatre post\u00e9e",
          "18": "En attente d\'\u00eatre sign\u00e9",
          "19": "En attente de transmission au SAE",
          "20": "Erreur lors de l\'envoi au SAE",
          "21": "Document re\u00e7u (pas d\'AR)"
        }';

        $response = $this->prepareAndGetResponse();
        $responseContent = $response->getContent();

        $responseArray = json_decode($responseContent, true);
        $responseModelArray = json_decode($responseModel, true);

        $differentValuesInArrays = array_diff($responseArray, $responseModelArray);
        $nbOfDifferentValuesInArrays = count($differentValuesInArrays);
        self::assertTrue($nbOfDifferentValuesInArrays === 0, "Les statuts différents.");
    }

    /**
     * @throws \Exception
     */
    private function setUpUserAndGetClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        return $this->setUpUser();
    }
}