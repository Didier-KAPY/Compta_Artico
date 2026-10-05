<?php

namespace Tests\Unit;

use App\Services\SqlGeneratedColumnNormalizer;
use PHPUnit\Framework\TestCase;

class SqlGeneratedColumnNormalizerTest extends TestCase
{
    private function columns(): array
    {
        return [
            ['Field' => 'id', 'Extra' => 'auto_increment'],
            ['Field' => 'label', 'Extra' => ''],
            ['Field' => 'active_unique', 'Extra' => 'VIRTUAL GENERATED'],
        ];
    }

    public function test_replaces_only_generated_values_in_multiple_rows(): void
    {
        $sql = "INSERT INTO `rh_paies` VALUES (1,'a,b);c',1),(2,'it''s a test',NULL)";
        $this->assertSame(
            "INSERT INTO `rh_paies` VALUES (1,'a,b);c',DEFAULT),(2,'it''s a test',DEFAULT)",
            (new SqlGeneratedColumnNormalizer())->normalize($sql, $this->columns()),
        );
    }

    public function test_handles_explicit_column_order_comments_and_expressions(): void
    {
        $sql = "-- data\nINSERT INTO `rh_paies` (`active_unique`,`label`,`id`) VALUES (1,CONCAT('a','b'),3)";
        $this->assertSame(
            "-- data\nINSERT INTO `rh_paies` (`active_unique`,`label`,`id`) VALUES (DEFAULT,CONCAT('a','b'),3)",
            (new SqlGeneratedColumnNormalizer())->normalize($sql, $this->columns()),
        );
    }

    public function test_leaves_inserts_without_generated_columns_alone(): void
    {
        $this->assertNull((new SqlGeneratedColumnNormalizer())->normalize(
            "INSERT INTO `rh_paies` (`id`,`label`) VALUES (1,'test')", $this->columns(),
        ));
        $this->assertNull((new SqlGeneratedColumnNormalizer())->normalize('DROP TABLE `rh_paies`', $this->columns()));
    }
}
