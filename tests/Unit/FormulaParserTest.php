<?php

namespace Serri\Alchemist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Serri\Alchemist\Exceptions\InvalidFormulaException;
use Serri\Alchemist\Support\FormulaParser;

class FormulaParserTest extends TestCase
{
    public function test_plain_fields_normalise_to_null_specs(): void
    {
        $this->assertSame(
            ['id' => null, 'title' => null],
            FormulaParser::normalise(['id', 'title'])
        );
    }

    public function test_nested_specs_keep_their_sub_formula(): void
    {
        $this->assertSame(
            ['id' => null, 'comments' => ['body'], 'writer' => ['fullName']],
            FormulaParser::normalise(['id', 'comments' => ['body'], 'writer' => ['fullName']])
        );
    }

    public function test_order_is_preserved_across_mixed_entries(): void
    {
        $normalised = FormulaParser::normalise(['title', 'comments' => ['body'], 'id']);

        $this->assertSame(['title', 'comments', 'id'], array_keys($normalised));
    }

    public function test_a_string_key_with_a_string_value_is_rejected(): void
    {
        $this->expectException(InvalidFormulaException::class);
        $this->expectExceptionMessage('title');

        FormulaParser::normalise(['title' => 'oops']);
    }

    public function test_an_integer_key_with_a_non_string_value_is_rejected(): void
    {
        $this->expectException(InvalidFormulaException::class);

        FormulaParser::normalise([['id', 'title']]);
    }

    public function test_an_empty_formula_normalises_to_an_empty_map(): void
    {
        $this->assertSame([], FormulaParser::normalise([]));
    }
}
