<?php

namespace App\Modules\ForgeTesting\Traits;

use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;

trait DatabaseTesting
{
    private static bool $migrated = false;

    public function refreshDatabase(): void
    {
        if (!self::$migrated) {
            $this->runMigrations();
            self::$migrated = true;
        }
    }

    private function runMigrations(): void
    {
        // Run Database migrations
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function seed(string $seederClass): void
    {
        (new $seederClass())->run();
    }

    protected function assertDatabaseMissing(
        string $table,
        array  $criteria,
        string $message = "",
    ): void
    {
        /*** @var QueryBuilderInterface $db */
        $db = Container::getInstance()->get(QueryBuilderInterface::class);
        $query = $db->setTable($table);
        $query = $this->applyDatabaseCriteria($query, $criteria);
        $count = $query->count();

        $this->assertEquals(
            0,
            $count,
            $message ?:
                sprintf(
                    "Failed asserting that the table '%s' does not contain records matching the criteria %s. Found %d.",
                    $table,
                    json_encode($criteria),
                    $count,
                ),
        );
    }

    private function applyDatabaseCriteria(
        QueryBuilderInterface $query,
        array                 $criteria,
    ): QueryBuilderInterface
    {
        foreach ($criteria as $column => $value) {
            if (is_null($value)) {
                $query->whereNull($column);
            } else {
                $query->where($column, "=", $value);
            }
        }
        return $query;
    }

    protected function assertDatabaseCount(
        string $table,
        int    $expectedCount,
        array  $criteria,
        string $message = "",
    ): void
    {
        /*** @var QueryBuilderInterface $db */
        $db = Container::getInstance()->get(QueryBuilderInterface::class);
        $query = $db->setTable($table);
        $query = $this->applyDatabaseCriteria($query, $criteria);
        $actualCount = $query->count();

        $this->assertEquals(
            $expectedCount,
            $actualCount,
            $message ?:
                sprintf(
                    "Failed asserting that the table '%s' contains exactly %d records matching the criteria %s. Found %d.",
                    $table,
                    $expectedCount,
                    json_encode($criteria),
                    $actualCount,
                ),
        );
    }

    protected function assertDatabaseHas(
        string $table,
        array  $criteria,
        string $message = "",
    ): void
    {
        /*** @var QueryBuilderInterface $db */
        $db = Container::getInstance()->get(QueryBuilderInterface::class);
        $query = $db->setTable($table);
        $query = $this->applyDatabaseCriteria($query, $criteria);
        $count = $query->count();

        $this->assertGreaterThanOrEqual(
            1,
            $count,
            $message ?:
                sprintf(
                    "Failed asserting that the table '%s' contains at least one record matching the criteria %s. Found %d.",
                    $table,
                    json_encode($criteria),
                    $count,
                ),
        );
    }
}
