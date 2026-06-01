<?php

namespace S2low\Controller;

use S2lowLegacy\Model\HeliosRetourSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HeliosRetourController extends AbstractController
{
    public function __construct(
        private readonly HeliosRetourSQL $heliosRetourSQL
    ) {
    }

    #[Route(
        path: '/helios-retour/update/status',
        name: 'app_helios_retour_update_status',
        methods: ['POST']
    )]
    public function updateStatus(Request $request): RedirectResponse
    {
        $ids = $request->request->all('ids');
        $status = $request->request->get('status');

        foreach ($ids as $id) {
            $this->heliosRetourSQL->changeStatus($id, $status);
        }

        return $this->redirect('/modules/helios/helios_retour.php');
    }
}
