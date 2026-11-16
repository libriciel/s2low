<?php

declare(strict_types=1);

namespace S2low\Tests\DTO;

use PHPUnit\Framework\TestCase;
use S2low\DTO\GroupContact;
use S2low\Enum\GroupContactField;
use S2low\Exceptions\InvalidGroupContactException;

class GroupContactTest extends TestCase
{
    private const string PHONE = '0555123456';
    private const string EMAIL = 'support@sm87.invalid';

    public function testDisplaysFilledAndShownField(): void
    {
        $contact = GroupContact::fromSubmittedForm(['contact_phone' => self::PHONE, 'contact_phone_shown' => '1']);

        static::assertTrue($contact->phone()->isDisplayed());
    }

    public function testHidesFieldMaskedByTheAdministrator(): void
    {
        $contact = GroupContact::fromSubmittedForm(['contact_email' => self::EMAIL]);

        static::assertFalse($contact->email()->isDisplayed());
        static::assertSame(self::EMAIL, $contact->email()->value);
    }

    public function testHidesEmptyField(): void
    {
        $contact = GroupContact::fromSubmittedForm(['contact_hours' => '', 'contact_hours_shown' => '1']);

        static::assertFalse($contact->hours()->isDisplayed());
    }

    public function testTreatsBlankValueAsEmpty(): void
    {
        $contact = GroupContact::fromSubmittedForm(['contact_message' => "   \n ", 'contact_message_shown' => '1']);

        static::assertSame('', $contact->message()->value);
        static::assertFalse($contact->message()->isDisplayed());
    }

    public function testHasNothingToDisplayWhenEveryFieldIsEmptyOrMasked(): void
    {
        $contact = GroupContact::fromSubmittedForm([
            'contact_phone' => self::PHONE,
            'contact_email' => '',
            'contact_email_shown' => '1',
        ]);

        static::assertFalse($contact->hasFieldToDisplay());
    }

    public function testReadsMissingColumnsAsEmptyAndShown(): void
    {
        $contact = GroupContact::fromGroupInfo(['id' => 1, 'name' => 'Groupe de test']);

        foreach ([$contact->phone(), $contact->email(), $contact->hours(), $contact->message()] as $field) {
            static::assertSame('', $field->value);
            static::assertTrue($field->isShown);
        }
    }

    public function testRefusesMalformedEmail(): void
    {
        $this->expectException(InvalidGroupContactException::class);

        GroupContact::fromSubmittedForm(['contact_email' => 'support@']);
    }

    public function testRefusesHoursLongerThan80Characters(): void
    {
        $this->expectException(InvalidGroupContactException::class);

        GroupContact::fromSubmittedForm(['contact_hours' => str_repeat('a', 81)]);
    }

    public function testRefusesMessageLongerThan1000Characters(): void
    {
        $this->expectException(InvalidGroupContactException::class);

        GroupContact::fromSubmittedForm(['contact_message' => str_repeat('a', 1001)]);
    }

    public function testCountsAccentedCharactersOnceInLengthLimits(): void
    {
        $hours = str_repeat('é', 80);
        $message = str_repeat('é', 1000);

        $contact = GroupContact::fromSubmittedForm(['contact_hours' => $hours, 'contact_message' => $message]);

        static::assertSame($hours, $contact->hours()->value);
        static::assertSame($message, $contact->message()->value);
    }

    public function testKeepsLineBreaksOfTheMessage(): void
    {
        $message = "Ligne 1\nLigne 2";

        $contact = GroupContact::fromSubmittedForm(['contact_message' => $message]);

        static::assertSame($message, $contact->message()->value);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function phoneThatIsNotTenDigitsProvider(): array
    {
        return [
            'neuf chiffres' => ['055512345'],
            'onze chiffres' => ['05551234567'],
            'lettres' => ['05551234ab'],
            'format international' => ['+33555123456'],
        ];
    }

    /**
     * @dataProvider phoneThatIsNotTenDigitsProvider
     */
    public function testRefusesPhoneThatIsNotTenDigits(string $phone): void
    {
        $this->expectException(InvalidGroupContactException::class);

        GroupContact::fromSubmittedForm(['contact_phone' => $phone]);
    }

    public function testAcceptsPhoneWrittenWithSpacesDotsOrDashes(): void
    {
        $contact = GroupContact::fromSubmittedForm(['contact_phone' => '05 55.12-34 56']);

        static::assertSame(self::PHONE, $contact->phone()->value);
    }

    public function testDisplaysPhoneByPairs(): void
    {
        $contact = GroupContact::fromSubmittedForm(['contact_phone' => self::PHONE]);

        static::assertSame('05 55 12 34 56', $contact->formattedPhone());
    }

    public function testTooLongHoursAreRefusedWithTheLimitAndTheTypedLength(): void
    {
        $exception = $this->refusal(['contact_hours' => str_repeat('a', 95)]);

        static::assertSame(GroupContactField::HOURS, $exception->field);
        static::assertStringContainsString('80', $exception->getMessage());
        static::assertStringContainsString('95', $exception->getMessage());
    }

    public function testTooLongMessageIsRefusedWithTheLimitAndTheTypedLength(): void
    {
        $exception = $this->refusal(['contact_message' => str_repeat('é', 1200)]);

        static::assertSame(GroupContactField::MESSAGE, $exception->field);
        static::assertStringContainsString('1000', $exception->getMessage());
        static::assertStringContainsString('1200', $exception->getMessage());
    }

    public function testTooLongEmailIsRefusedWithTheLimit(): void
    {
        $exception = $this->refusal(['contact_email' => str_repeat('a', 250) . '@sm87.invalid']);

        static::assertSame(GroupContactField::EMAIL, $exception->field);
        static::assertStringContainsString('255', $exception->getMessage());
    }

    /**
     * @param array<string, string> $submittedValues
     */
    private function refusal(array $submittedValues): InvalidGroupContactException
    {
        try {
            GroupContact::fromSubmittedForm($submittedValues);
        } catch (InvalidGroupContactException $exception) {
            return $exception;
        }

        static::fail('La saisie aurait dû être refusée');
    }
}
