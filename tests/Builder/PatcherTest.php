<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Tests\Builder;

use PHPUnit\Framework\TestCase;
use UniMethod\JsonToPhpClass\Builder\Model\Patch;
use UniMethod\JsonToPhpClass\Builder\Patcher;
use UniMethod\JsonToPhpClass\Converter\Model\_Class;
use UniMethod\JsonToPhpClass\Converter\Model\_Simple;

/**
 * @coversDefaultClass \UniMethod\JsonToPhpClass\Builder\Patcher
 */
final class PatcherTest extends TestCase
{
    protected Patcher $patcher;

    /**
     * @covers ::applyPatches
     */
    public function testApplyPatches(): void
    {
        $case01Class01 = new _Class('Item');
        $case01Class01->addSimpleType(new _Simple('title', 'string', 'string', 'title'));

        $case01UpdatedClass01 = new _Class('Item');
        $case01UpdatedClass01->addSimpleType(new _Simple('title', 'string', 'string', 'title', false, false));

        $patches = [
            new Patch('Item', 'title', false)
        ];

        $this->assertEquals($case01UpdatedClass01, $this->patcher->applyPatches($case01Class01, $patches));
    }

    protected function setUp(): void
    {
        $this->patcher = new Patcher();
    }
}
