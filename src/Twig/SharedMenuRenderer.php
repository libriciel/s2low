<?php

declare(strict_types=1);

namespace S2low\Twig;

use S2low\DTO\GroupAffiliation;
use Twig\Environment;

final readonly class SharedMenuRenderer
{
    public const string HELP_HIDDEN_COOKIE = 's2low_menu_help_hidden';
    private const string HELP_HIDDEN_VALUE = '1';

    public function __construct(private Environment $twig)
    {
    }

    /**
     * @param list<GroupAffiliation> $groups
     */
    public function renderGroups(array $groups): string
    {
        return $this->twig->render('components/menu-groups.html.twig', ['groups' => $groups]);
    }

    /**
     * @param list<GroupAffiliation> $groups
     */
    public function renderHelp(array $groups, bool $isCollapsed): string
    {
        return $this->twig->render(
            'components/menu-help.html.twig',
            ['groups' => $groups, 'helpCollapsed' => $isCollapsed]
        );
    }

    /**
     * @param array<string, mixed> $cookies
     */
    public static function isHelpHiddenIn(array $cookies): bool
    {
        return ($cookies[self::HELP_HIDDEN_COOKIE] ?? '') === self::HELP_HIDDEN_VALUE;
    }
}
