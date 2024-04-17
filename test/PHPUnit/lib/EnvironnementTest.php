<?php

use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\SessionWrapper;

class EnvironnementTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();
        $environnement = new Environnement($get, $post, $request, $session, $server, false);
        $this->assertInstanceOf(SessionWrapper::class, $environnement->session());
        $this->assertInstanceOf(Recuperateur::class, $environnement->get());
        $this->assertInstanceOf(Recuperateur::class, $environnement->post());
        $this->assertInstanceOf(Recuperateur::class, $environnement->request());
        $this->assertInstanceOf(Recuperateur::class, $environnement->server());
    }

    /**
     * @param array $get
     * @param string $expectedGetContent
     * @param array $post
     * @param string $expectedPostContent
     * @param array $request
     * @param string $expectedRequestContent
     * @param array $session
     * @param string $expectedSessionContent
     * @param array $server
     * @param string $expectedServerContent
     * @param bool $forceConversionFromIso
     * @return void
     * @dataProvider environnementProvider
     */
    public function test(
        array $get,
        string $expectedGetContent,
        array $post,
        string $expectedPostContent,
        array $request,
        string $expectedRequestContent,
        array $session,
        string $expectedSessionContent,
        array $server,
        string $expectedServerContent,
        bool $forceConversionFromIso
    ) {

        $environnement = new Environnement($get, $post, $request, $session, $server, $forceConversionFromIso);
        static::assertEquals($environnement->get()->get('content'), $expectedGetContent);
        static::assertEquals($environnement->post()->get('content'), $expectedPostContent);
        static::assertEquals($environnement->request()->get('content'), $expectedRequestContent);
        static::assertEquals($environnement->session()->get('content'), $expectedSessionContent);
    }

    /**
     * @return array[]
     */
    public function environnementProvider(): array
    {
        $content = 'éàö';
        $contentIso = mb_convert_encoding('éàö', 'ISO-8859-1');

        return [
            // api=1 n'est pas présent, pas d'authentification HTTP, quelle que soit $forceConversionFromIso,
            // on considère que le client utilise l'IHM
            // L'encodage utilisé est donc UTF-8
            [
                ['content' => $content],$content,   // get
                ['content' => $content],$content,   // post
                ['content' => $content],$content,   // request
                ['content' => $content],$content,   // session
                ['content' => $content],$content,   // server
                false
            ],
            [
                ['content' => $content],$content,   // get
                ['content' => $content],$content,   // post
                ['content' => $content],$content,   // request
                ['content' => $content],$content,   // session
                ['content' => $content],$content,   // server
                true
            ],
            // Si api=1 est présent, pas d'authentification HTTP, quelle que soit $forceConversionFromIso,
            // on considère qu'on utilise l'API
            // Les paramètres de la requete ou api=1 est présent sont donc considérés encodés en ISO-8859-1
            [
                ['api' => 1, 'content' => $contentIso],$content,    // get
                [], '',                                             // post
                [], '',                                             // request
                [], '',                                             // session
                [], '',                                             // server
                false
            ],
            [
                ['api' => 1, 'content' => $contentIso],$content,    // get
                [], '',                                             // post
                [], '',                                             // request
                [], '',                                             // session
                [], '',                                             // server
                true
            ],
            // ATTENTION : api n'affecte que le type de requête dans laquelle la variable apparait.
            // Ce comportement pourrait être à corriger ?!
            // Par exemple :
            [
                ['api' => 1, 'content' => ''], '',                   // get : api=1
                ['content' => $content],$content,                   // post : l'encodage n'est pas modifié
                ['content' => $content],$content,                   // request idem
                [],'',                                              // (session)
                ['content' => $content],$content,                   // server idem
                false
            ],
            // Si on s'authentifie par nounce et $forceConversionFromIso,
            // il faut considérer que l'on est dans un appel API
            [
                ['login' => 'login','nounce' => 'nounce','hash' => 'hash','content' => $contentIso],$content,
                ['content' => $contentIso],$content,                   // post : l'encodage n'est pas modifié
                ['content' => $contentIso],$content,                   // request idem
                [],'',                                                 // (session)
                ['content' => $contentIso],$content,                   // server idem
                true
            ],
            // Si on a un login HTTP et $forceConversionFromIso
            // Alors les paramètres get sont donc considérés encodés en ISO-8859-1
            [
                ['content' => $contentIso],$content,                        // get
                ['content' => $contentIso],$content,                        // post
                ['content' => $contentIso],$content,                        // request
                [],'',                                                      // (session)
                ['PHP_AUTH_USER' => 'user','PHP_AUTH_PW' => 'alice'], '',    // server
            true
            ]
        ];
    }
}
