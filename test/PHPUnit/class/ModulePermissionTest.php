<?php

declare(strict_types=1);

use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;

final class ModulePermissionTest extends S2lowTestCase
{
    private const ARCHIVIST_OF_AUTHORITY_1 = 12;
    private const USER_OF_AUTHORITY_1 = 5;
    private const USER_OF_AUTHORITY_2 = 51;

    public function testArchivistCanViewTransactionOfOwnAuthority(): void
    {
        $permission = $this->createActesPermission();

        $canView = $permission->canView(
            $this->loadUser(self::ARCHIVIST_OF_AUTHORITY_1),
            $this->loadUser(self::USER_OF_AUTHORITY_1)
        );

        static::assertTrue($canView);
    }

    public function testArchivistCannotViewTransactionOfAnotherAuthority(): void
    {
        $permission = $this->createActesPermission();

        $canView = $permission->canView(
            $this->loadUser(self::ARCHIVIST_OF_AUTHORITY_1),
            $this->loadUser(self::USER_OF_AUTHORITY_2)
        );

        static::assertFalse($canView);
    }

    private function createActesPermission(): ModulePermission
    {
        return new ModulePermission($this->getObjectInstancier()->get(ServiceUser::class), 'actes');
    }

    private function loadUser(int $id): User
    {
        $user = new User($id);
        $user->init();
        return $user;
    }
}
