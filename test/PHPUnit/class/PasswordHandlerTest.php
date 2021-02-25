<?php

class PasswordHandlerTest extends S2lowTestCase{
    public function testMD5password(){
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();
        $passwordHandler = new PasswordHandler($userSQL);

        $this->assertTrue(
            $passwordHandler->passwordMatchesHash(
                "password",
                md5("password"),
                1
            )
        );
    }

    public function testWrongMD5password(){
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();
        $passwordHandler = new PasswordHandler($userSQL);

        $this->assertFalse(
            $passwordHandler->passwordMatchesHash(
                "false_password",
                md5("password"),
                1
            )
        );
    }

    public function testMD5passwordIsChanged(){
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();

        $userSQL->expects($this->once())
            ->method('setPassword')
            ->with(
                $this->equalTo(1),
                $this->callback(function ($subject){
                return password_verify("password",$subject);
            }));
        $passwordHandler = new PasswordHandler($userSQL);

        $passwordHandler->passwordMatchesHash(
                "password",
                md5("password"),
                1
            );
    }

    public function testMD5passwordIsNotChangedOnWrongPassword(){
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();

        $userSQL->expects($this->never())->method('setPassword');
        $passwordHandler = new PasswordHandler($userSQL);

        $passwordHandler->passwordMatchesHash(
            "false_password",
            md5("password"),
            1
        );
    }

    public function testBcCryptPassword(){
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();
        $passwordHandler = new PasswordHandler($userSQL);

        $this->assertTrue(
            $passwordHandler->passwordMatchesHash(
                "password",
                password_hash("password", PASSWORD_DEFAULT),
                1
            )
        );
    }

    public function testBcCryptPasswordIsNotChanged(){
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();

        $userSQL->expects($this->never())->method('setPassword');
        $passwordHandler = new PasswordHandler($userSQL);

        $passwordHandler->passwordMatchesHash(
            "password",
            password_hash("password", PASSWORD_DEFAULT),
            1
        );
    }
}