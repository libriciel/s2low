<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Model\NounceSQL;

class AuthenticationNounceController extends Controller
{
    private NounceSQL $nounceSQL;
    private Environnement $environnement;

    public function __construct(
        ObjectInstancier $objectInstancier,
        UserContext $userContext
    ) {
        $this->nounceSQL = $objectInstancier->get(NounceSQL::class);
        $this->environnement = $objectInstancier->get(Environnement::class);
        parent::__construct($objectInstancier, $userContext);
    }

    public function _actionAfter()
    {
        /* Nothing to do*/
    }

    public function getAction()
    {
        $this->verifUser();
        if (empty($this->environnement->server()->get('PHP_AUTH_USER'))) {
            header("HTTP/1.1 401 Unauthorized");
            header('WWW-Authenticate: Basic realm="API S2low"');
            echo "La fonction n'est utilisable qu'avec un login+mot de passe HTTP";
            return false;
        }

        $authority_id = $this->userContext->me->get('authority_id');
        $nounce = $this->nounceSQL->create(
            $this->environnement->server()->get('PHP_AUTH_USER'),
            $this->environnement->server()->get('PHP_AUTH_PW'),
            $authority_id
        );

        echo json_encode(array('nounce' => $nounce));

        return true;
    }
}
