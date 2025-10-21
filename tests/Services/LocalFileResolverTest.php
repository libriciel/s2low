<?php

declare(strict_types=1);

namespace S2low\Tests\Services;

use PHPUnit\Framework\TestCase;
use S2low\Services\LocalFileResolver;
use S2low\Services\FileDataProvider;

/**
 * @covers \S2low\Services\LocalFileResolver
 */
final class LocalFileResolverTest extends TestCase
{
    public function testCallsProviderWithTransactionId(): void
    {
        $transactionId = 'tx-123';
        $relative = 'foo/bar.pdf';

        $provider = $this->createMock(FileDataProvider::class);
        $provider
            ->expects(self::once())
            ->method('getRelativePath')
            ->with($transactionId)
            ->willReturn($relative);

        $resolver = new LocalFileResolver($provider, '/var/data');

        self::assertSame('/var/data/foo/bar.pdf', $resolver->getFullPath($transactionId));
    }

    /**
     * @dataProvider pathCases
     */
    public function testBuildsNormalizedPath(string $prefix, string $relative, string $expected): void
    {
        $provider = $this->createMock(FileDataProvider::class);
        $provider
            ->method('getRelativePath')
            ->willReturn($relative);

        $resolver = new LocalFileResolver($provider, $prefix);

        self::assertSame($expected, $resolver->getFullPath('whatever'));
    }

    public static function pathCases(): array
    {
        return [
            'simple join' => [
                '/var/data',
                'foo/bar.txt',
                '/var/data/foo/bar.txt',
            ],
            'prefix trailing slash' => [
                '/var/data/',
                'foo/bar.txt',
                '/var/data/foo/bar.txt',
            ],
            'relative leading slash' => [
                '/var/data',
                '/foo/bar.txt',
                '/var/data/foo/bar.txt',
            ],
            'both sides with slashes' => [
                '/var/data/',
                '/foo/bar.txt',
                '/var/data/foo/bar.txt',
            ],
            'collapse multiple slashes' => [
                '/var//data///',
                '///foo//bar.txt',
                '/var/data/foo/bar.txt',
            ],
            'relative empty' => [
                '/var/data',
                '',
                '/var/data/',
            ],
            'relative is just slashes' => [
                '/var/data',
                '////',
                '/var/data/',
            ],
            'deep path with redundant slashes' => [
                '/opt///local/',
                'a///b//c////d.pdf',
                '/opt/local/a/b/c/d.pdf',
            ],
        ];
    }
}
