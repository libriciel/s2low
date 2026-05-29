<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Model\HeliosRetourSQL;

class HeliosRetourControllerTest extends S2lowIntegrationTestCase
{
    public function testUpdateStatusToLuAsSuperAdmin(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $id1 = $heliosRetourSQL->add(1, "12345678900035", "test_helios1.txt", 10, "sha1");
        $id2 = $heliosRetourSQL->add(1, "12345678900035", "test_helios2.txt", 10, "sha1");

        // Verify initial status is 0 (non lu)
        $this->assertEquals(0, $heliosRetourSQL->getInfo($id1)['status']);
        $this->assertEquals(0, $heliosRetourSQL->getInfo($id2)['status']);

        // Post update status to LU
        $this->client->request(
            'POST',
            '/helios-retour/update/status',
            [
                'ids' => [$id1, $id2],
                'status' => '1',
            ]
        );

        // Verify redirect to the legacy retour modules page
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('modules/helios/helios_retour.php', $this->client->getResponse()->headers->get('location'));

        // Verify status is changed to LU in DB
        $this->assertEquals(1, $heliosRetourSQL->getInfo($id1)['status']);
        $this->assertEquals(1, $heliosRetourSQL->getInfo($id2)['status']);
    }
}
