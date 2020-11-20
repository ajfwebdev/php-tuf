<?php

namespace Tuf\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tuf\JsonNormalizer;

/**
 * @coversDefaultClass \Tuf\JsonNormalizer
 */
class JsonNormalizerTest extends TestCase
{

    /**
     * @covers ::asNormalizedJson
     * @covers ::decode
     *
     * @return void
     */
    public function testSort():void
    {
        $fixturesDirectory = __DIR__ . '/../../non_repo_fixtures';
        $sortedData = JsonNormalizer::decode(file_get_contents("$fixturesDirectory/sorted.json"));
        $unsortedData = JsonNormalizer::decode(file_get_contents("$fixturesDirectory/unsorted.json"));
        // asNormalizedJson()
        $this->assertSame(JsonNormalizer::asNormalizedJson($sortedData), JsonNormalizer::asNormalizedJson($unsortedData));
        $this->assertSame(json_encode($sortedData), JsonNormalizer::asNormalizedJson($unsortedData));
    }
}