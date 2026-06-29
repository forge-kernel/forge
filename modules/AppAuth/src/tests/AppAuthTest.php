<?php

declare(strict_types=1);

namespace Modules\AppAuth\Tests;

use Modules\AppAuth\Models\User;
use Modules\ForgeTesting\Attributes\DataProvider;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Incomplete;
use Modules\ForgeTesting\Attributes\Skip;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;

#[Group("auth")]
final class AppAuthTest extends TestCase
{
    private QueryBuilderInterface $queryBuilder;
    private array $exampleUser = [];

    public function __construct()
    {
        $this->queryBuilder = Container::getInstance()->get(
            QueryBuilderInterface::class,
        );

        $this->exampleUser = [
            "identifier" => "example",
            "email" => "test@example.com",
            "password" => password_hash("test1234", PASSWORD_BCRYPT),
            "status" => "active",
            "metadata" => [],
        ];
    }

    #[Test("User login functionality")]
    #[Skip("Waiting on implementation")]
    public function login_works(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    #[Group("smtp")]
    #[Skip("Waiting on SMTP implementation")]
    public function password_reset_email(): void
    {
        // Test implementation
    }

    #[Test]
    #[Skip("Need to implememnt 2FA checks")]
    public function two_factor_authentication(): void
    {
        // Partial implementation
    }

    #[Test("Insert a new record in the Database")]
    #[Group("Database")]
    #[Incomplete("Needs to check save performance in the model")]
    public function create_user(): void
    {
        $user = new User();
        $user->identifier = $this->exampleUser["identifier"];
        $user->password = password_hash(
            $this->exampleUser["password"],
            PASSWORD_BCRYPT,
        );
        $user->email = $this->exampleUser["email"];
        $user->status = $this->exampleUser["status"];
        $user->metadata = [];
        $user->save();
        $this->assertNotNull($user->id);
    }

    #[Test("Check a record exists in the Database by identifier")]
    #[Group("Database")]
    public function user_exists(): void
    {
        $this->assertDatabaseHas("users", [
            "identifier" => $this->exampleUser["identifier"],
        ]);
    }

    #[DataProvider("userProvider")]
    #[Test]
    #[Group("Database")]
    public function multiple_users(array $users): void
    {
        $this->assertArrayHasKey("email", $users);
    }

    #[Test("Benchmark user lookup")]
    #[Group("Database")]
    public function benchmark_user_lookup(): array
    {
        $results = $this->benchmark(function () {
            $this->assertDatabaseHas("users", [
                "email" => $this->exampleUser["email"],
            ]);
        }, 1);

        return $results;
    }

    #[Test("Delete user from the Database by using email")]
    #[Group("Database")]
    #[Skip("Needs to check delete performance in the model")]
    public function delete_user(): void
    {
        $user = $this->queryBuilder
            ->reset()
            ->where("email", "=", $this->exampleUser["email"])
            ->setTable("users")
            ->delete();
        $this->assertTrue($user ? true : false);
    }

    public function userProvider(): array
    {
        $users = $this->queryBuilder
            ->reset()
            ->setTable("users")
            ->select("*")
            ->limit(10)
            ->get(null);

        $dataProvider = [];
        foreach ($users as $user) {
            $dataProvider[] = [["email" => $user["email"]]];
        }

        return $dataProvider;
    }
}
