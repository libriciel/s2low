<?php

namespace S2low\Tests\Services\XMLFromDGFIP\PesBuilder;

use LibXMLError;
use PHPUnit\Framework\TestCase;
use S2low\Exceptions\MissingFieldInParsedPesException;
use S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\CodCol;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\NomFich;
use S2low\Services\XMLFromDGFiP\PesBuilder\ParsedPes;

class ParsedPesTest extends TestCase
{
    /**
     * @throws \S2low\Exceptions\MissingFieldInParsedPesException
     */
    public function testValidPesAcquit(): void
    {
        $parsedPes = new ParsedPes(
            PesDocumentType::PES_ACQUIT,
            PesDocumentType::PES_ACQUIT->value,
            [
                CodCol::KEY => 'CodCol',
                NomFich::KEY => 'NomFich'
            ],
            []
        );

        self::assertTrue($parsedPes->hasType(PesDocumentType::PES_ACQUIT));
        self::assertFalse($parsedPes->hasType(PesDocumentType::UNKNOWN_ROOT));

        self::assertFalse($parsedPes->hasXsdErrors());

        self::assertTrue($parsedPes->hasValue(CodCol::KEY));
        self::assertSame(
            'CodCol',
            $parsedPes->getStringValue(CodCol::KEY)
        );
    }

    public function testPesAcquitWithXSDError(): void
    {
        $xsdError = new LibXMLError();
        $xsdError->message = 'some error';
        $xsdError->line = 10;

        $parsedPes = new ParsedPes(
            PesDocumentType::PES_ACQUIT,
            PesDocumentType::PES_ACQUIT->value,
            [
                CodCol::KEY => 'CodCol',
                NomFich::KEY => 'NomFich'
            ],
            [$xsdError]
        );

        self::assertTrue($parsedPes->hasXsdErrors());
        self::assertSame(
            ['[line 10] some error'],
            $parsedPes->getXsdErrors()
        );
    }

    public function testUndefinedStringValue(): void
    {
        $xsdError = new LibXMLError();
        $xsdError->message = 'some error';
        $xsdError->line = 10;

        $parsedPes = new ParsedPes(
            PesDocumentType::PES_ACQUIT,
            PesDocumentType::PES_ACQUIT->value,
            [
                CodCol::KEY => 'CodCol',
                NomFich::KEY => 'NomFich'
            ],
            [$xsdError]
        );

        self::expectException(MissingFieldInParsedPesException::class);
        self::expectExceptionMessage(
            '[ PES_ACQUIT] Champ UnexistingKey introuvable. Champs disponibles : cod_col, nom_fic'
        );

        $parsedPes->getStringValue('UnexistingKey');
    }
}
