<?php

use App\DTO\MigrationItem;
use App\Enum\Status;
use App\Enum\Type;
use PHPUnit\Framework\TestCase;

class MigrationItemTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $item = new MigrationItem(
            id: 42,
            oldKey: 'siren/file.tar.gz',
            newKey: 'siren/file.tar.gz',
            type: Type::ACTE,
            status: Status::HANDLE,
            date: '2023-01-01',
            bucket: 'my-bucket'
        );

        $this->assertEquals(42, $item->id);
        $this->assertEquals('siren/file.tar.gz', $item->oldKey);
        $this->assertEquals(Type::ACTE->value, $item->type);
        $this->assertEquals('2023-01-01', $item->date);
        $this->assertEquals('my-bucket', $item->bucket);
    }

    public function testConstructorBucketDefaultsToNull(): void
    {
        $item = new MigrationItem(
            1,
            'oldkey',
            'newkey',
            Type::MAIL,
            '2020-06-01',
            Status::HANDLE,
            'siren'
        );
        $this->assertNull($item->bucket);
    }

    public function testBucketCanBeUpdated(): void
    {
        $item = new MigrationItem(
            1,
            'oldkey',
            'newkey',
            Type::ACTE,
            '2020-01-01',
            Status::HANDLE,
            'siren'
        );
        $this->assertNull($item->bucket);

        $item->bucket = 'new-bucket';
        $this->assertEquals('new-bucket', $item->bucket);
    }
}

class StatusEnumTest extends TestCase
{
    public function testAllStatusValues(): void
    {
        $this->assertEquals('HANDLE', Status::HANDLE->value);
        $this->assertEquals('ASK', Status::ASK->value);
        $this->assertEquals('BUCKET_FOUND', Status::BUCKET_FOUND->value);
        $this->assertEquals('DOWNLOADED', Status::DOWNLOADED->value);
        $this->assertEquals('COMPLETED', Status::COMPLETED->value);
        $this->assertEquals('ERROR', Status::ERROR->value);
        $this->assertEquals('ERROR_KEY_NULL', Status::ERROR_KEY_NULL->value);
    }

    public function testStatusEnumFromValue(): void
    {
        $this->assertEquals(Status::HANDLE, Status::from('HANDLE'));
        $this->assertEquals(Status::BUCKET_FOUND, Status::from('BUCKET_FOUND'));
        $this->assertEquals(Status::COMPLETED, Status::from('COMPLETED'));
    }
}

class TypeEnumTest extends TestCase
{
    public function testAllTypeValues(): void
    {
        $this->assertEquals('ACTE', Type::ACTE->value);
        $this->assertEquals('PES_ALLER', Type::PES_ALLER->value);
        $this->assertEquals('PES_ACQUIT', Type::PES_ACQUIT->value);
        $this->assertEquals('PES_RETOUR', Type::PES_RETOUR->value);
        $this->assertEquals('MAIL', Type::MAIL->value);
    }

    public function testTypeEnumFromValue(): void
    {
        $this->assertEquals(Type::ACTE, Type::from('ACTE'));
        $this->assertEquals(Type::PES_ALLER, Type::from('PES_ALLER'));
    }

    public function testTypeEnumInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);
        Type::from('UNKNOWN');
    }
}
