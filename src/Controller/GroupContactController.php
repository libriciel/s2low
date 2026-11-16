<?php

declare(strict_types=1);

namespace S2low\Controller;

use S2low\DTO\GroupContact;
use S2low\Enum\GroupContactField;
use S2low\Exceptions\InvalidGroupContactException;
use S2low\Security\SecurityUser;
use S2lowLegacy\Model\GroupSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GADM')]
final class GroupContactController extends AbstractController
{
    private const string ROUTE = 'admin_group_contact';
    private const string CSRF_TOKEN_ID = 'group_contact';

    public function __construct(private readonly GroupSQL $groupSQL)
    {
    }

    #[Route('/admin/mon-groupe/contact', name: self::ROUTE, methods: ['GET'])]
    public function edit(#[CurrentUser] SecurityUser $user): Response
    {
        $groupInfo = $this->groupInfoOf($user);
        $contact = GroupContact::fromGroupInfo($groupInfo);

        return $this->renderContactPage($groupInfo['name'], $this->formValuesOf($contact), null);
    }

    #[Route('/admin/mon-groupe/contact', methods: ['POST'])]
    public function save(Request $request, #[CurrentUser] SecurityUser $user): Response
    {
        $groupInfo = $this->groupInfoOf($user);
        if (! $this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException("Jeton CSRF invalide pour le contact du groupe {$groupInfo['id']}");
        }

        $submittedValues = $this->submittedValuesOf($request);
        try {
            $contact = GroupContact::fromSubmittedForm($submittedValues);
        } catch (InvalidGroupContactException $exception) {
            return $this->renderContactPage($groupInfo['name'], $submittedValues, $exception);
        }

        $this->groupSQL->updateContact((int) $groupInfo['id'], $contact);
        $this->addFlash('success', 'Les informations d\'assistance ont été enregistrées.');

        return $this->redirectToRoute(self::ROUTE);
    }

    /**
     * @return array<string, mixed>
     */
    private function groupInfoOf(SecurityUser $user): array
    {
        $groupId = $user->getAuthorityGroupId();
        $groupInfo = $groupId ? $this->groupSQL->getInfo($groupId) : false;
        if (! is_array($groupInfo)) {
            throw $this->createAccessDeniedException("L'administrateur de groupe {$user->getId()} n'a pas de groupe");
        }

        return $groupInfo;
    }

    /**
     * @return array<string, string>
     */
    private function submittedValuesOf(Request $request): array
    {
        $submittedValues = [];
        foreach (GroupContactField::cases() as $field) {
            $submittedValues[$field->column()] = (string) $request->request->get($field->column(), '');
            $submittedValues[$field->shownColumn()] = (string) $request->request->get($field->shownColumn(), '');
        }

        return $submittedValues;
    }

    /**
     * @return array<string, string>
     */
    private function formValuesOf(GroupContact $contact): array
    {
        $shown = static fn(bool $isShown): string => $isShown ? '1' : '';

        return [
            GroupContactField::PHONE->column() => $contact->formattedPhone(),
            GroupContactField::PHONE->shownColumn() => $shown($contact->phone()->isShown),
            GroupContactField::EMAIL->column() => $contact->email()->value,
            GroupContactField::EMAIL->shownColumn() => $shown($contact->email()->isShown),
            GroupContactField::HOURS->column() => $contact->hours()->value,
            GroupContactField::HOURS->shownColumn() => $shown($contact->hours()->isShown),
            GroupContactField::MESSAGE->column() => $contact->message()->value,
            GroupContactField::MESSAGE->shownColumn() => $shown($contact->message()->isShown),
        ];
    }

    /**
     * @param array<string, string> $formValues
     */
    private function renderContactPage(string $groupName, array $formValues, ?InvalidGroupContactException $refusal): Response
    {
        return $this->render(
            'admin/group_contact.html.twig',
            [
                'group_name' => $groupName,
                'fields' => GroupContactField::cases(),
                'values' => $formValues,
                'error' => $refusal?->getMessage(),
                'invalid_field' => $refusal?->field,
                'csrf_token_id' => self::CSRF_TOKEN_ID,
            ],
            new Response(status: $refusal === null ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
