<?php

declare(strict_types=1);

namespace S2low\Tests\Twig;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use S2low\DTO\GroupAffiliation;
use S2low\DTO\GroupContact;
use S2low\Enum\AdministeredModule;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class MenuHelpTemplateTest extends TestCase
{
    private const string TEMPLATE = 'components/menu-help.html.twig';
    private const string GROUP_NAME = 'Syndicat mixte Haute-Vienne';
    private const string PHONE = '0555123456';
    private const string EMAIL = 'support@sm87.invalid';

    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = new Environment(
            new FilesystemLoader(dirname(__DIR__, 2) . '/templates'),
            ['strict_variables' => true]
        );
    }

    public function testShowsHelpBlockWithClickablePhoneAndEmail(): void
    {
        $contact = GroupContact::fromSubmittedForm([
            'contact_phone' => self::PHONE,
            'contact_phone_shown' => '1',
            'contact_email' => self::EMAIL,
            'contact_email_shown' => '1',
        ]);

        $html = $this->render([$this->actesGroup($contact)], false);

        static::assertStringContainsString(self::GROUP_NAME, $html);
        static::assertStringContainsString('href="tel:' . self::PHONE . '"', $html);
        static::assertStringContainsString('>05 55 12 34 56<', $html);
        static::assertStringContainsString('href="mailto:' . self::EMAIL . '"', $html);
    }

    public function testOmitsHelpBlockWhenNoGroupHasContactToDisplay(): void
    {
        $masked = GroupContact::fromSubmittedForm(['contact_phone' => self::PHONE]);

        $html = $this->render([$this->actesGroup(GroupContact::empty()), $this->actesGroup($masked)], false);

        static::assertSame('', trim($html));
    }

    public function testOmitsMaskedFieldFromTheMenu(): void
    {
        $contact = GroupContact::fromSubmittedForm([
            'contact_phone' => self::PHONE,
            'contact_phone_shown' => '1',
            'contact_email' => self::EMAIL,
        ]);

        $html = $this->render([$this->actesGroup($contact)], false);

        static::assertStringContainsString('tel:' . self::PHONE, $html);
        static::assertStringNotContainsString(self::EMAIL, $html);
    }

    public function testEscapesContactValues(): void
    {
        $contact = GroupContact::fromSubmittedForm([
            'contact_message' => '<script>alert(1)</script>',
            'contact_message_shown' => '1',
        ]);

        $html = $this->render([$this->actesGroup($contact)], false);

        static::assertStringNotContainsString('<script>alert(1)', $html);
        static::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testRendersHelpBlockCollapsedWhenTheUserHidItThisSession(): void
    {
        $html = $this->render([$this->actesGroup($this->displayedPhoneContact())], true);

        static::assertFalse($this->helpBlock($html)->hasAttribute('open'));
    }

    public function testRendersHelpBlockOpenByDefault(): void
    {
        $html = $this->render([$this->actesGroup($this->displayedPhoneContact())], false);

        static::assertTrue($this->helpBlock($html)->hasAttribute('open'));
    }

    /**
     * @param list<GroupAffiliation> $groups
     */
    private function render(array $groups, bool $helpCollapsed): string
    {
        return $this->twig->render(self::TEMPLATE, ['groups' => $groups, 'helpCollapsed' => $helpCollapsed]);
    }

    private function actesGroup(GroupContact $contact): GroupAffiliation
    {
        return new GroupAffiliation(self::GROUP_NAME, [AdministeredModule::ACTES], $contact);
    }

    private function displayedPhoneContact(): GroupContact
    {
        return GroupContact::fromSubmittedForm(['contact_phone' => self::PHONE, 'contact_phone_shown' => '1']);
    }

    private function helpBlock(string $html): DOMElement
    {
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR);
        $block = (new DOMXPath($document))->query('//details[contains(@class, "menu-help")]')->item(0);
        static::assertInstanceOf(DOMElement::class, $block);

        return $block;
    }
}
