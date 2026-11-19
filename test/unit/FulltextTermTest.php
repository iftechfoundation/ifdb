<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once 'util.php';

final class FulltextTermTest extends TestCase
{
    public static function terms(): array
    {
        return [
            ["zork", "zork"],
            ["+zork", "+zork"],
            ["-zork", "-zork"],
            ["zork*", "zork*"],
            ["+zork*", "+zork*"],
            ["<zork", "<zork"],
            ["don't", "don't"],
            ["café", "café"],
            ["0", "0"],
            ["\"the lost\"", "\"the lost\""],
            ["\"unbalanced", "\"unbalanced\""],

            # InnoDB rejects these with a syntax error
            ["c++", "c"],
            ["++zork", "+zork"],
            ["zork-", "zork"],
            ["zork+", "zork"],
            ["zork**", "zork*"],
            ["(zork", "zork"],
            ["\"zork\"*", "\"zork\""],
            ["@", ""],
            ["*", ""],
            ["+", ""],
            ["-", ""],
            ["\xff", ""],

            # Like MyISAM, punctuation separates words
            ["sci-fi", "sci fi"],
            ["-sci-fi", "-sci fi"],
            ["foo@bar.com", "foo bar com"],
            ["9:05", "9 05"],
            ["sci-fi*", "sci fi*"],
            ["zork*,", "zork*"],
            ["+অজানা", "+অজানা"],
        ];
    }

    #[DataProvider('terms')]
    public function testFulltextTerm(string $input, string $expected): void
    {
        $this->assertSame($expected, fulltextTerm($input));
    }

    public static function queries(): array
    {
        return [
            ["zork  -troll", ["zork", "-troll"]],
            ["+\"multiple endings\" +dragon", ["+\"multiple endings\"", "+dragon"]],
            ["-\"cover art\" zork", ["-\"cover art\"", "zork"]],
            ["\"the lost\"* c++", ["\"the lost\"", "c"]],
            ["\"unbalanced phrase", ["\"unbalanced phrase\""]],
            ["@ + zork", ["zork"]],
            ["+\"multiple endings\"zork", ["+\"multiple endings\"", "zork"]],
            ["title:\"one or\"", ["title", "\"one or\""]],
            ["+zork +(dragon troll)", ["+zork", "+(", "dragon", "troll", ")"]],
            ["(a (b c)) -()", ["(", "a", "(", "b", "c", ")", ")"]],
            ["(zork troll", ["zork", "troll"]],
            ["zork) (troll", ["zork", "troll"]],
            ["", []],
        ];
    }

    #[DataProvider('queries')]
    public function testFulltextTerms(string $input, array $expected): void
    {
        $this->assertSame($expected, fulltextTerms($input));
    }

    public static function stopwordQueries(): array
    {
        return [
            ["+lord +of +the +rings", ["+lord", "+rings"]],
            ["The Hobbit", ["Hobbit"]],
            ["+the-end", ["+end"]],
            ["the*", ["the*"]],
            ["\"lord of the rings\"", ["\"lord of the rings\""]],
            ["+\"of the\" zork", ["zork"]],
            ["+zork +(of the)", ["+zork"]],
            ["+zork +((of the))", ["+zork"]],
            ["-zork -(of the)", ["-zork"]],
            ["+zork +the's", ["+zork", "the's"]],
            ["+zork +'The", ["+zork"]],
            ["+\"'the'\" zork", ["zork"]],
            ["+THE +Zork", ["+Zork"]],
            ["+à +la", ["+à", "+la"]],
            ["-the", []],
        ];
    }

    #[DataProvider('stopwordQueries')]
    public function testFulltextTermsWithStopwords(string $input, array $expected): void
    {
        $this->assertSame($expected, fulltextTerms($input, ["a", "an", "and", "of", "the"]));
    }

    public function testFulltextAllWords(): void
    {
        $this->assertSame("+Zork +Part +I", fulltextAllWords("Zork - Part I"));
        $this->assertSame("+Spider +Man's +C", fulltextAllWords("Spider-Man's C++"));
        $this->assertSame("", fulltextAllWords("@ --"));
        $this->assertSame("+Lord +Rings", fulltextAllWords("The Lord of the Rings", ["of", "the"]));
        $this->assertSame("+Alias +Magpie'", fulltextAllWords("Alias 'The Magpie'", ["the"]));
        $this->assertSame("+Panique l'a", fulltextAllWords("Panique l'a", ["a"]));
    }
}
