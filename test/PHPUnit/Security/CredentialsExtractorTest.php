<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use S2low\Security\CredentialsExtractor;
use Symfony\Component\HttpFoundation\Request;

class CredentialsExtractorTest extends TestCase
{
    private CredentialsExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new CredentialsExtractor();
    }

    public function testExtractReturnsEmptyArrayWhenNoCredentialsPresent(): void
    {
        $request = new Request();
        $credentials = $this->extractor->extract($request);

        $this->assertEquals([
            'login' => null,
            'password' => null
        ], $credentials);
    }

    public function testExtractReturnsBasicAuthCredentials(): void
    {
        $request = new Request();
        $request->server->set('PHP_AUTH_USER', 'basic_user');
        $request->server->set('PHP_AUTH_PW', 'basic_password');

        $credentials = $this->extractor->extract($request);

        $this->assertEquals([
            'login' => 'basic_user',
            'password' => 'basic_password'
        ], $credentials);
    }

    public function testExtractReturnsPostCredentials(): void
    {
        $request = new Request();
        $request->request->set('login', 'post_user');
        $request->request->set('password', 'post_password');

        $credentials = $this->extractor->extract($request);

        $this->assertEquals([
            'login' => 'post_user',
            'password' => 'post_password'
        ], $credentials);
    }

    public function testExtractPrioritizesBasicAuthOverPost(): void
    {
        $request = new Request();
        $request->server->set('PHP_AUTH_USER', 'basic_user');
        $request->server->set('PHP_AUTH_PW', 'basic_password');
        $request->request->set('login', 'post_user');
        $request->request->set('password', 'post_password');

        $credentials = $this->extractor->extract($request);

        $this->assertEquals([
            'login' => 'basic_user',
            'password' => 'basic_password'
        ], $credentials);
    }

    public function testExtractHandlesIso88591Encoding(): void
    {
        $request = new Request();
        // crée une chaîne avec l'encodage ISO-8859-1
        $isoString = mb_convert_encoding('café', 'ISO-8859-1', 'UTF-8');
        $request->server->set('PHP_AUTH_USER', $isoString);
        $request->server->set('PHP_AUTH_PW', 'secret');

        $credentials = $this->extractor->extract($request);

        $this->assertEquals('café', $credentials['login']);
        $this->assertEquals('secret', $credentials['password']);
    }
}
