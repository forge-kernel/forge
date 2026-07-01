<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\Services\TenantManager;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeTesting\Traits\MetricsProvider;

#[Group('multi-tenant')]
#[Group('performance')]
final class TenantManagerPerformanceTest extends TestCase
{
    use MetricsProvider;

    public function tearDown(): void
    {
        parent::tearDown();
        gc_collect_cycles();
    }

    #[Test("Cold load + hot lookups: 100 tenants")]
    public function benchmark_100(): void
    {
        $this->runBenchmark(100);
    }

    #[Test("Cold load + hot lookups: 200k tenants")]
    public function benchmark_200k(): void
    {
        $this->runBenchmark(200_000);
    }

    #[Test("Cold load + hot lookups: 500k tenants")]
    public function benchmark_500k(): void
    {
        $this->runBenchmark(500_000);
    }

    #[Test("Cold load + hot lookups: 900k tenants")]
    public function benchmark_900k(): void
    {
        $this->runBenchmark(900_000);
    }

    #[Test("Caching: no re-fetch after 100 hot lookups (200k tenants)")]
    public function cached_lookups_no_refetch(): void
    {
        $callCount = 0;
        $tenants = $this->generateTenants(200_000);
        $callback = function () use (&$callCount, $tenants) {
            $callCount++;
            return $tenants;
        };

        $manager = new TenantManager(dataCallback: $callback);

        $manager->all();
        $this->assertSame(1, $callCount);

        for ($i = 0; $i < 100; $i++) {
            $manager->resolveByDomain($tenants[array_rand($tenants)]['domain']);
        }

        $this->assertSame(1, $callCount, 'No re-fetch after 100 resolveByDomain calls');
    }

    private function runBenchmark(int $count): void
    {
        ini_set('memory_limit', '-1');
        $tenants = $this->generateTenants($count);
        $manager = new TenantManager(dataCallback: fn() => $tenants);

        $cold = $this->profile(fn() => $manager->all());

        $known = $tenants[array_rand($tenants)];
        $knownHost = $known['subdomain']
            ? "{$known['subdomain']}.{$known['domain']}"
            : $known['domain'];

        $hotResolve = $this->profile(fn() => $manager->resolveByDomain($knownHost));
        $hotFind = $this->profile(fn() => $manager->find($known['id']));
        $hotMiss = $this->profile(fn() => $manager->resolveByDomain('nonexistent-' . uniqid() . '.com'));

        $this->assertNotNull($hotResolve['result']);
        $this->assertSame($known['id'], $hotResolve['result']->id);
        $this->assertSame($known['id'], $hotFind['result']->id);

        $this->recordMetrics([
            'Dataset' => number_format($count),
            'Cold Wall' => number_format($cold['wall_ms'], 2) . ' ms',
            'Cold CPU' => number_format($cold['cpu_ms'], 2) . ' ms',
            'Cold CPU%' => $cold['cpu_pct'] . '%',
            'Cold Mem' => number_format($cold['memory_mb'], 2) . ' MB',
            'Hot resolveByDomain' => number_format($hotResolve['wall_ms'], 3) . ' ms',
            'Hot find' => number_format($hotFind['wall_ms'], 3) . ' ms',
            'Hot miss' => number_format($hotMiss['wall_ms'], 3) . ' ms',
        ]);
    }

    private function generateTenants(int $count): array
    {
        $tenants = [];
        $tlds = ['.com', '.io', '.dev', '.app', '.tech', '.co', '.net', '.org', '.cloud', '.systems'];
        $brands = [
            'forge',
            'acme',
            'globex',
            'initech',
            'umbrella',
            'stark',
            'wayne',
            'cyberdyne',
            'sprockets',
            'hooli',
            'piedpiper',
            'massivedynamic',
            'wonka',
            'oscorp',
            'lexcorp',
            'tyrell',
            'werner',
            'northimport',
            'mountainlogistics',
            'peaksolutions',
            'velocity',
            'apex',
            'core',
            'prime',
            'elite',
            'ultra',
            'hyper',
            'meta',
            'quantum',
            'nexus',
            'vertex',
            'pulse',
            'phoenix',
            'titan',
            'aegis',
            'catalyst',
            'horizon',
            'infinity',
            'lambda',
            'omega'
        ];
        $suffixes = [
            'corp',
            'labs',
            'tech',
            'io',
            'systems',
            'global',
            'solutions',
            'dynamics',
            'industries',
            'enterprises',
            'partners',
            'group',
            'holdings',
            'ventures',
            'analytics',
            'networks',
            'platform'
        ];

        $strategies = ['column', 'database', 'view'];
        $brandCount = count($brands);
        $suffixCount = count($suffixes);
        $tldCount = count($tlds);

        for ($i = 0; $i < $count; $i++) {
            $brand = $brands[$i % $brandCount];
            $suffix = $suffixes[intdiv($i, $brandCount) % $suffixCount];
            $strategy = $strategies[$i % 3];
            $useSubdomain = ($i % 3 === 0);

            $tenants[] = [
                'id' => dechex($i),
                'domain' => "{$i}-{$brand}-{$suffix}{$tlds[$i % $tldCount]}",
                'subdomain' => $useSubdomain ? "app-{$i}" : null,
                'strategy' => $strategy,
                'db_name' => ($strategy === 'database') ? "forge_{$brand}_{$suffix}" : null,
                'connection' => null,
            ];
        }
        return $tenants;
    }
}
