<?php

use S2lowLegacy\Class\PasswordHandler;
use S2lowLegacy\Model\UserSQL;
use PHPUnit\Framework\MockObject\MockObject;

class PasswordHandlerTest extends S2lowTestCase
{
    /**
     * @var MockObject|UserSQL
     */
    private $userSQL;
    /** @var PasswordHandler  */
    private $passwordHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();
        $this->passwordHandler = new PasswordHandler($this->userSQL);
    }

    /**
     * @param $password
     * @param $hash
     * @param $match
     * @dataProvider passwordValidationProvider
     */
    public function testPasswordValidation(
        string $password,
        string $hash,
        bool $match
    ) {
        $this->assertEquals(
            $match,
            $this->passwordHandler->passwordMatchesHash(
                $password,
                $hash,
                1
            )
        );
    }

    public function passwordValidationProvider()
    {
        return [
            ["password",md5("password"),true],
            ["wrong_password",md5("password"),false],
            ["password",password_hash("password", PASSWORD_DEFAULT), true],
            ["wrong_password",password_hash("password", PASSWORD_DEFAULT), false]
        ];
    }


    public function testMD5passwordIsChanged()
    {

        $this->userSQL->expects($this->once())
            ->method('setPassword')
            ->with(
                $this->equalTo(1),
                $this->callback(function ($subject) {
                    return password_verify("password", $subject);
                })
            );

        $this->passwordHandler->passwordMatchesHash(
            "password",
            md5("password"),
            1
        );
    }

    /**
     * @dataProvider passwordNotChangedProvider
     */
    public function testMD5passwordIsNotChanged(string $password, string $hash)
    {
        $this->userSQL->expects($this->never())->method('setPassword');

        $this->passwordHandler->passwordMatchesHash(
            $password,
            $hash,
            1
        );
    }

    public function passwordNotChangedProvider(): array
    {
        return[
            ["wrong_password",md5("password")],
            ["password",password_hash("password", PASSWORD_DEFAULT)],
            ["wrong_password",password_hash("password", PASSWORD_DEFAULT)]
        ];
    }
}
