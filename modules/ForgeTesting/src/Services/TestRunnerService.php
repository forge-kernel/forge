<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting\Services;

use App\Modules\ForgeTesting\Attributes\AfterEach;
use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Attributes\DataProvider;
use App\Modules\ForgeTesting\Attributes\Depends;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Incomplete;
use App\Modules\ForgeTesting\Attributes\Skip;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use Forge\Traits\NamespaceHelper;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

#[Service]
#[Provides(TestRunnerService::class, version: '0.1.0')]
#[Requires()]
final class TestRunnerService
{
    use OutputHelper;
    use NamespaceHelper;

    private array $config = [];
    private array $results = [];
    private array $filterGroups = [];
    private array $testClasses = [];
    private ?string $groupFilter = null;
    private Container $container;


    public function __construct()
    {
        $this->results = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'incomplete' => 0,
            'failures' => [],
            'skipped_tests' => [],
            'incomplete_tests' => [],
            'benchmarks' => [],
            'durations' => [],
            'passed_tests' => []
        ];
    }

    public function setTestClasses(array $classes): self
    {
        $this->testClasses = $classes;
        return $this;
    }

    public function setGroupFilter(?string $group): self
    {
        $this->groupFilter = $group;
        return $this;
    }

    public function runTests(): array
    {
        $this->__construct();

        // Ensure TestCase is loaded so the Autoloader doesn't block parsing *Test classes
        if (!class_exists(TestCase::class)) {
            // Unreachable fallback
        }

        foreach ($this->testClasses as $testClass) {
            if (!class_exists($testClass)) {
                continue;
            }
            $this->processTestClass($testClass);
        }

        $this->renderResults();
        return $this->results;
    }

    private function isTestClass(string $className): bool
    {
        $reflection = new ReflectionClass($className);
        return $reflection->isSubclassOf(TestCase::class);
    }

    private function processTestClass(string $testClass): void
    {
        $reflection = new ReflectionClass($testClass);

        if ($this->groupFilter && !$this->classMatchesGroup($reflection)) {
            return;
        }


        $this->runTestLifecycle($reflection, [
            'before' => $this->getMethodsWithAttribute($reflection, BeforeEach::class),
            'after' => $this->getMethodsWithAttribute($reflection, AfterEach::class)
        ]);
    }

    private function classMatchesGroup(ReflectionClass $class): bool
    {
        $attributes = $class->getAttributes(Group::class);

        foreach ($attributes as $attr) {
            $group = $attr->newInstance();
            if ($group->name === $this->groupFilter) {
                return true;
            }
        }

        foreach ($class->getMethods() as $method) {
            $attributes = $method->getAttributes(Group::class);
            foreach ($attributes as $attr) {
                $group = $attr->newInstance();
                if ($group->name === $this->groupFilter) {
                    return true;
                }
            }
        }

        return false;
    }

    private function runTestLifecycle(ReflectionClass $reflection, array $lifecycle): void
    {
        foreach ($reflection->getMethods() as $method) {
            if ($this->isTestMethod($method)) {
                $this->executeTestMethod($reflection, $method, $lifecycle);
            }
        }
    }

    private function executeTestMethod(ReflectionClass $reflection, ReflectionMethod $method, array $lifecycle): void
    {
        $testInstance = $reflection->newInstance();
        $this->results['total']++;
        $fullMethodName = $reflection->getName() . '::' . $method->getName();

        $testAttribute = $this->getMethodAttribute($method, Test::class);
        $testDescription = $testAttribute ? $testAttribute->description : $method->getName();

        $startTime = microtime(true);
        $returnValue = null;

        try {
            if ($skip = $this->getMethodAttribute($method, Skip::class)) {
                $this->handleSkippedTest($method, $skip);
                $this->recordDuration($fullMethodName, $startTime);
                return;
            }
            if ($incomplete = $this->getMethodAttribute($method, Incomplete::class)) {
                $this->handleIncompleteTest($method, $incomplete);
                $this->recordDuration($fullMethodName, $startTime);
                return;
            }

            $this->runBeforeEach($testInstance, $lifecycle['before']);

            if ($dataProvider = $this->getMethodAttribute($method, DataProvider::class)) {
                $this->runDataProvider($testInstance, $method, $dataProvider);
            } elseif ($depends = $this->getMethodAttribute($method, Depends::class)) {
                $this->runDependency($testInstance, $depends->testMethod);
                $returnValue = $method->invoke($testInstance);
            } else {
                $returnValue = $method->invoke($testInstance);
            }

            $this->runAfterEach($testInstance, $lifecycle['after']);

            $this->recordDuration($fullMethodName, $startTime);

            if (is_array($returnValue) && isset($returnValue['avg'])) {
                $this->results['benchmarks'][$fullMethodName] = $returnValue;
                //iterations count for test method calculation
                // $this->results['benchmarks'][$fullMethodName]['iterations'] = $iterations_variable;
            }

            $this->results['passed']++;
            $this->results['passed_tests'][] = [
                'Test' => $testDescription,
                'Class' => $reflection->getName(),
                'Method' => $method->getName(),
                'Duration' => $this->results['durations'][$fullMethodName] ?? 0,
            ];
        } catch (\Throwable $e) {
            $this->recordDuration($fullMethodName, $startTime);
            $this->handleTestFailure($method, $e);
        }
    }

    private function recordDuration(string $methodName, float $startTime): void
    {
        $endTime = microtime(true);
        $this->results['durations'][$methodName] = $endTime - $startTime;
    }

    private function handleTestFailure(ReflectionMethod $method, Throwable $e): void
    {
        $this->results['failed']++;
        $this->results['failures'][] = [
            'class' => $method->getDeclaringClass()->getName(),
            'method' => $method->getName(),
            'message' => $e->getMessage(),
            'exception' => $e
        ];
    }

    private function isTestMethod(ReflectionMethod $method): bool
    {
        return (bool) $this->getMethodAttribute($method, Test::class);
    }

    private function getMethodAttribute(ReflectionMethod $method, string $attribute): ?object
    {
        $attributes = $method->getAttributes($attribute);
        return $attributes ? $attributes[0]->newInstance() : null;
    }

    private function getClassAttribute(ReflectionClass $class, string $attribute): ?object
    {
        $attributes = $class->getAttributes($attribute);
        return $attributes ? $attributes[0]->newInstance() : null;
    }

    private function shouldSkipClass(ReflectionClass $class, ?string $filter): bool
    {
        if ($skip = $this->getClassAttribute($class, Skip::class)) {
            $this->handleSkippedClass($class, $skip);
            return true;
        }

        if ($filter && !$this->classMatchesFilter($class, $filter)) {
            return true;
        }

        return false;
    }

    private function classMatchesFilter(ReflectionClass $class, string $filter): bool
    {
        $group = $this->getClassAttribute($class, Group::class);
        return $group && $group->name === $filter;
    }

    private function handlesTestFailure(ReflectionMethod $method, Throwable $e): void
    {
        $this->results['failed']++;
        $this->results['failures'][] = [
            'class' => $method->getDeclaringClass()->getName(),
            'method' => $method->getName(),
            'message' => $e->getMessage(),
            'exception' => $e
        ];
    }

    private function runTestWithDependencies(object $instance, ReflectionMethod $method): void
    {
        if ($depends = $this->getMethodAttribute($method, Depends::class)) {
            $this->runDependency($instance, $depends->testMethod);
        }

        if ($dataProvider = $this->getMethodAttribute($method, DataProvider::class)) {
            $this->runDataProvider($instance, $method, $dataProvider);
        } else {
            $method->invoke($instance);
        }
    }

    private function runDataProvider(object $instance, ReflectionMethod $testMethod, DataProvider $dataProvider): void
    {
        $providerMethod = new ReflectionMethod($instance, $dataProvider->methodName);
        foreach ($providerMethod->invoke($instance) as $dataSet) {
            $testMethod->invokeArgs($instance, $dataSet);
        }
    }

    private function renderResults(): void
    {
        $this->renderBenchmarkResults();
        $this->renderTestDurations();

        $this->renderPassedTests();
        $this->renderFailureDetails();
        $this->renderSkippedTests();
        $this->renderIncompleteTests();
        $this->renderSummaryTable();
    }

    private function renderBenchmarkResults(): void
    {
        if (!empty($this->results['benchmarks'])) {
            $this->line("\nBenchmark Results:");

            foreach ($this->results['benchmarks'] as $methodName => $benchmarkData) {
                $this->info("\n{$methodName}:");

                $rowData = [];

                if (isset($benchmarkData['iterations'])) {
                    $rowData['Iterations'] = $benchmarkData['iterations'];
                }

                if (isset($benchmarkData['avg']) && is_numeric($benchmarkData['avg'])) {
                    $rowData['Avg Time/Iter'] = number_format($benchmarkData['avg'] * 1000, 3) . ' ms';
                }
                if (isset($benchmarkData['min']) && is_numeric($benchmarkData['min'])) {
                    $rowData['Min Time/Iter'] = number_format($benchmarkData['min'] * 1000, 3) . ' ms';
                }
                if (isset($benchmarkData['max']) && is_numeric($benchmarkData['max'])) {
                    $rowData['Max Time/Iter'] = number_format($benchmarkData['max'] * 1000, 3) . ' ms';
                }
                if (isset($benchmarkData['total']) && is_numeric($benchmarkData['total'])) {
                    $rowData['Total Time'] = number_format($benchmarkData['total'] * 1000, 3) . ' ms';
                }

                $headers = array_keys($rowData);

                if (!empty($headers)) {
                    $this->table($headers, [$rowData]);
                } else {
                    $this->warning("  No benchmark data could be formatted for display.");
                    error_log("Original benchmark data for {$methodName}: " . print_r($benchmarkData, true));
                }
            }
            $this->line('');
        }
    }

    private function renderTestDurations(int $topN = 5): void
    {
        if (!empty($this->results['durations'])) {
            $this->line("\nSlowest Tests:");

            arsort($this->results['durations']);
            $slowest = array_slice($this->results['durations'], 0, $topN, true);

            $tableData = [];
            foreach ($slowest as $methodName => $duration) {
                $tableData[] = [
                    'Test' => $methodName,
                    'Duration' => number_format($duration * 1000, 2) . ' ms'
                ];
            }

            if (!empty($tableData)) {
                $this->table(['Test', 'Duration'], $tableData);
                $this->line('');
            }
        }
    }

    private function renderSummaryTable(): void
    {
        $this->line("Test Results:");
        $headers = ['Total', 'Passed', 'Failed', 'Skipped', 'Incomplete'];
        $rowData = [
            'Total' => $this->results['total'],
            'Passed' => $this->results['passed'],
            'Failed' => $this->results['failed'],
            'Skipped' => $this->results['skipped'],
            'Incomplete' => $this->results['incomplete']
        ];
        $this->table($headers, [$rowData]);
    }

    private function renderFailureDetails(): void
    {
        if (!empty($this->results['failures'])) {
            $this->error("\nFailures:");
            foreach ($this->results['failures'] as $failure) {
                $methodName = $failure['class'] . '::' . $failure['method'];
                $durationMs = isset($this->results['durations'][$methodName])
                    ? ' (' . number_format($this->results['durations'][$methodName] * 1000, 0) . ' ms)'
                    : '';

                $this->line(sprintf(
                    "%s%s\n%s\n%s:%d\n",
                    $methodName,
                    $durationMs,
                    $failure['message'],
                    $failure['exception']->getFile(),
                    $failure['exception']->getLine()
                ));
            }
        }
    }

    private function runBeforeEach(object $instance, array $methods): void
    {
        foreach ($methods as $method) {
            $method->invoke($instance);
        }
    }

    private function runAfterEach(object $instance, array $methods): void
    {
        foreach ($methods as $method) {
            $method->invoke($instance);
        }
    }

    private function handleSkippedTest(ReflectionMethod $method, Skip $skip): void
    {
        $this->results['skipped']++;
        $this->results['skipped_tests'][] = [
            'class' => $method->getDeclaringClass()->getName(),
            'method' => $method->getName(),
            'reason' => $skip->reason
        ];
    }

    private function handleIncompleteTest(ReflectionMethod $method, Incomplete $incomplete): void
    {
        $this->results['incomplete']++;
        $this->results['incomplete_tests'][] = [
            'class' => $method->getDeclaringClass()->getName(),
            'method' => $method->getName(),
            'reason' => $incomplete->reason
        ];
    }

    private function handleSkippedClass(ReflectionClass $class, Skip $skip): void
    {
        $this->results['skipped']++;
        $this->results['skipped_tests'][] = [
            'class' => $class->getName(),
            'reason' => $skip->reason
        ];
    }

    private function renderSkippedTests(): void
    {
        if (!empty($this->results['skipped_tests'])) {
            $this->warning("\nSkipped Tests:");
            foreach ($this->results['skipped_tests'] as $skippedTest) {
                $methodName = $skippedTest['class'] . '::' . ($skippedTest['method'] ?? '{class}');
                $durationMs = isset($this->results['durations'][$methodName])
                    ? ' (' . number_format($this->results['durations'][$methodName] * 1000, 0) . ' ms)'
                    : '';

                $this->line(sprintf(
                    "%s%s\nReason: %s\n",
                    $methodName,
                    $durationMs,
                    $skippedTest['reason']
                ));
            }
        }
    }

    private function renderPassedTests(): void
    {
        if (!empty($this->results['passed_tests'])) {
            $this->line("\nPassed Tests:");
            $tableData = [];
            foreach ($this->results['passed_tests'] as $passedTest) {
                $tableData[] = [
                    'Test' => $passedTest['Test'],
                    'Class' => $passedTest['Class'],
                    'Method' => $passedTest['Method'],
                    'Duration' => number_format($passedTest['Duration'] * 1000, 2) . ' ms',
                ];
            }
            $this->table(['Test', 'Class', 'Method', 'Duration'], $tableData);
            $this->line('');
        }
    }

    private function renderIncompleteTests(): void
    {
        if (!empty($this->results['incomplete_tests'])) {
            $this->warning("\nIncomplete Tests:");
            foreach ($this->results['incomplete_tests'] as $incompleteTest) {
                $this->line(sprintf(
                    "%s::%s\nReason: %s\n",
                    $incompleteTest['class'],
                    $incompleteTest['method'],
                    $incompleteTest['reason']
                ));
            }
        }
    }

    private function runDependency(object $instance, string $dependencyMethod): void
    {
        $dependency = new ReflectionMethod($instance, $dependencyMethod);
        $dependency->invoke($instance);
    }

    private function getMethodsWithAttribute(ReflectionClass $class, string $attribute): array
    {
        $methods = [];
        foreach ($class->getMethods() as $method) {
            if ($this->getMethodAttribute($method, $attribute)) {
                $methods[] = $method;
            }
        }
        return $methods;
    }
}
