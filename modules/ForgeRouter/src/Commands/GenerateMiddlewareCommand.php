<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Commands;

use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\CoreCommand;
use Forge\CLI\Command;
use Forge\CLI\Traits\CliGenerator;
use Forge\Traits\StringHelper;

#[CoreCommand]
#[Cli(
    command: 'generate:middleware',
    description: 'Create a new middleware',
    usage: 'generate:middleware [--type=app|module] [--module=ModuleName] [--name=MiddlewareName]',
    examples: [
        'generate:middleware --type=app --name=Auth',
        'generate:middleware --type=app --name=api/Auth',
        'generate:middleware --type=module --module=Blog --name=CheckUser',
        'generate:middleware   (starts wizard)',
    ]
)]
final class GenerateMiddlewareCommand extends Command
{
    use StringHelper;
    use CliGenerator;

    #[Arg(name: 'type', description: 'app or module', validate: 'app|module')]
    private string $type;

    #[Arg(name: 'module', description: 'Module name when type=module', required: false)]
    private ?string $module = null;

    #[Arg(name: 'name', description: 'Middleware name (e.g., Auth, api/Auth, admin/CheckUser)', validate: '/^[\w\/\s-]+$/')]
    private string $name;

    protected function generateFromStub(string $stub, string $targetPath, array $tokens, bool $force = false): void
    {
        if (is_file($targetPath) && !$force) {
            $this->error("File exists: $targetPath  (--force to overwrite)");
            exit(1);
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        $stubFile = __DIR__ . '/../resources/stubs/' . $stub . '.stub';
        $content = file_get_contents($stubFile);
        $content = strtr($content, $tokens);

        file_put_contents($targetPath, $content);
        $this->success("Created: $targetPath");
    }

    /**
     * @throws Exception
     */
    public function execute(array $args): int
    {
        $this->wizard($args);

        if ($this->type === 'module' && !$this->module) {
            $this->error('--module=Name required when --type=module');
            return 1;
        }

        $middlewareFile = $this->middlewarePath();

        $parsed = $this->parseFolderFilenameForClass($this->name);
        $className = $this->toPascalCase($parsed['filename']) . 'Middleware';

        $tokens = [
            '{{ middlewareName }}'      => $className,
            '{{ middlewareNamespace }}' => $this->middlewareNamespace(),
        ];

        $this->generateFromStub('middleware', $middlewareFile, $tokens);

        $this->showPostGenerationInfo('middleware', [
            'type' => $this->type,
            'module' => $this->module,
            'name' => $this->name,
        ]);

        return 0;
    }
}
