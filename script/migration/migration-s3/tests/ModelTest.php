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
            key: 'siren/file.tar.gz',
            type: Type::ACTE->value,
            date: '2023-01-01',
            siren: 'siren123',
            bucket: 'my-bucket'
        );

        $this->assertEquals(42, $item->id);
        $this->assertEquals('siren/file.tar.gz', $item->key);
        $this->assertEquals(Type::ACTE->value, $item->type);
        $this->assertEquals('2023-01-01', $item->date);
        $this->assertEquals('siren123', $item->siren);
        $this->assertEquals('my-bucket', $item->bucket);
    }

    public function testConstructorBucketDefaultsToNull(): void
    {
        $item = new MigrationItem(1, 'key', Type::MAIL->value, '2020-06-01', 'siren');
        $this->assertNull($item->bucket);
    }

    public function testBucketCanBeUpdated(): void
    {
        $item = new MigrationItem(1, 'key', Type::ACTE->value, '2020-01-01', 'siren');
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
    }

    public function testStatusEnumFromValue(): void
    {
        $this->assertEquals(Status::HANDLE, Status::from('HANDLE'));
        $this->assertEquals(Status::BUCKET_FOUND, Status::from('BUCKET_FOUND'));
        $this->assertEquals(Status::COMPLETED, Status::from('COMPLETED'));
    }

    public function testStatusEnumInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);
        Status::from('INVALID');
    }
}

class TypeEnumTest extends TestCase
{
    public function testAllTypeValues(): void
    {
        $this->assertEquals('ACTE', Type::ACTE->value);
        $this->assertEquals('PES_ALLER', Type::PES_ALLER->value);
        $this->assertEquals('PES_ACQUIT', Type::PES_ACQUIT->value);
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
