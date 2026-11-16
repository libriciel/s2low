<?php

declare(strict_types=1);

namespace S2low\DTO;

use S2low\Enum\GroupContactField;
use S2low\Exceptions\InvalidGroupContactException;

final readonly class GroupContact
{
    private const string PHONE_PATTERN = '/^(?<prefix>0|\+33|0033)(?<number>[1-9]\d{8})$/';
    private const string NATIONAL_PREFIX = '0';
    private const string PHONE_SEPARATORS_PATTERN = '/[\s.\-]/u';
    private const int EMAIL_MAX_LENGTH = 255;
    private const int HOURS_MAX_LENGTH = 80;
    private const int MESSAGE_MAX_LENGTH = 1000;
    private const string SUBMITTED_SWITCH_ON = '1';

    private ContactField $phone;
    private ContactField $email;
    private ContactField $hours;
    private ContactField $message;

    /**
     * @param array<string, ContactField> $fields indexé par GroupContactField::value
     */
    private function __construct(array $fields)
    {
        $phone = $fields[GroupContactField::PHONE->value];
        $this->phone = new ContactField(
            (string) preg_replace(self::PHONE_SEPARATORS_PATTERN, '', $phone->value),
            $phone->isShown
        );
        $this->email = $fields[GroupContactField::EMAIL->value];
        $this->hours = $fields[GroupContactField::HOURS->value];
        $this->message = $fields[GroupContactField::MESSAGE->value];

        $this->assertValid();
    }

    /**
     * @param array<string, mixed> $groupInfo
     */
    public static function fromGroupInfo(array $groupInfo): self
    {
        $fields = [];
        foreach (GroupContactField::cases() as $field) {
            $fields[$field->value] = new ContactField(
                (string) ($groupInfo[$field->column()] ?? ''),
                (bool) ($groupInfo[$field->shownColumn()] ?? true)
            );
        }

        return new self($fields);
    }

    /**
     * @param array<string, mixed> $submittedValues
     */
    public static function fromSubmittedForm(array $submittedValues): self
    {
        $groupInfo = [];
        foreach (GroupContactField::cases() as $field) {
            $groupInfo[$field->column()] = (string) ($submittedValues[$field->column()] ?? '');
            $groupInfo[$field->shownColumn()] = ($submittedValues[$field->shownColumn()] ?? '') === self::SUBMITTED_SWITCH_ON;
        }

        return self::fromGroupInfo($groupInfo);
    }

    public static function empty(): self
    {
        return self::fromGroupInfo([]);
    }

    public function phone(): ContactField
    {
        return $this->phone;
    }

    public function email(): ContactField
    {
        return $this->email;
    }

    public function hours(): ContactField
    {
        return $this->hours;
    }

    public function message(): ContactField
    {
        return $this->message;
    }

    public function formattedPhone(): string
    {
        $parts = $this->phoneParts();
        if ($parts === []) {
            return '';
        }

        $number = $parts['number'];
        $prefixedFirstDigit = $parts['prefix'] === self::NATIONAL_PREFIX
            ? self::NATIONAL_PREFIX . $number[0]
            : $parts['prefix'] . ' ' . $number[0];

        return $prefixedFirstDigit . ' ' . implode(' ', str_split(substr($number, 1), 2));
    }

    public function hasFieldToDisplay(): bool
    {
        return $this->phone->isDisplayed()
            || $this->email->isDisplayed()
            || $this->hours->isDisplayed()
            || $this->message->isDisplayed();
    }

    private function assertValid(): void
    {
        if ($this->phone->value !== '' && $this->phoneParts() === []) {
            throw new InvalidGroupContactException(
                GroupContactField::PHONE,
                'Le numéro de téléphone doit être au format 05 55 12 34 56, +33 5 55 12 34 56 ou 0033 5 55 12 34 56.'
            );
        }

        $this->assertNotLongerThan(GroupContactField::EMAIL, $this->email, self::EMAIL_MAX_LENGTH);
        if ($this->email->value !== '' && filter_var($this->email->value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidGroupContactException(GroupContactField::EMAIL, "L'adresse e-mail n'est pas valide.");
        }

        $this->assertNotLongerThan(GroupContactField::HOURS, $this->hours, self::HOURS_MAX_LENGTH);
        $this->assertNotLongerThan(GroupContactField::MESSAGE, $this->message, self::MESSAGE_MAX_LENGTH);
    }

    /**
     * @return array<int|string, string>
     */
    private function phoneParts(): array
    {
        if (preg_match(self::PHONE_PATTERN, $this->phone->value, $parts) !== 1) {
            return [];
        }

        return $parts;
    }

    private function assertNotLongerThan(GroupContactField $field, ContactField $contactField, int $maxLength): void
    {
        $length = mb_strlen($contactField->value);
        if ($length > $maxLength) {
            throw new InvalidGroupContactException(
                $field,
                sprintf('Le champ « %s » ne doit pas dépasser %d caractères (%d saisis).', $field->label(), $maxLength, $length)
            );
        }
    }
}
