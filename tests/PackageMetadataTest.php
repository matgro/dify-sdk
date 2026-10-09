<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use PHPUnit\Framework\TestCase;

final class PackageMetadataTest extends TestCase
{
    public function testPackageMetadataDeclaresThePublicContract(): void
    {
        $composer = json_decode(
            file_get_contents(__DIR__ . '/../composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('matgro/dify-sdk', $composer['name']);
        self::assertSame('MIT', $composer['license']);
        self::assertSame('src/', $composer['autoload']['psr-4']['Matgro\\Dify\\']);
        self::assertSame('^7.4 || ^8.0', $composer['require']['php']);
    }
}
