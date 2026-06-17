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
    command: 'generate:controller',
    description: 'Create a new controller',
    usage: 'generate:controller [--type=app|module] [--module=ModuleName] [--name=ControllerName]',
    examples: [
        'generate:controller --type=app --name=Auth',
        'generate:controller --type=app --name=api/User',
        'generate:controller --type=app --name=admin/Dashboard',
        'generate:controller --type=module --module=Blog --name=Post',
        'generate:controller   (starts wizard)',
    ]
)]
final class GenerateControllerCommand extends Command
{
    use StringHelper;
    use CliGenerator;

    #[Arg(name: 'type', description: 'app or module', validate: 'app|module')]
    private string $type;

    #[Arg(name: 'module', description: 'Module name when type=module', required: false)]
    private ?string $module = null;

    #[Arg(name: 'name', description: 'Controller name (e.g., User, api/User, admin/Dashboard)', validate: '/^[\w\/\s-]+$/')]
    private string $name;

    #[Arg(
        name: 'path',
        description: 'Optional subfolder inside Controllers (e.g., Admin, Api/V1)',
        default: '',
        required: false
    )]
    private string $path = '';

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

        $controllerFile = $this->controllerPath();

        $parsed = $this->parseFolderFilenameForClass($this->name);
        $className = $this->toPascalCase($parsed['filename']) . 'Controller';
        $routeName = $this->toKebabCase($parsed['filename']);

        $viewName = $parsed['folder'] !== ''
            ? $parsed['folder'] . '/' . $this->toKebabCase($parsed['filename'])
            : $this->toKebabCase($parsed['filename']);

        $viewFile = $this->viewPathForName($viewName);

        $tokens = [
            '{{ controllerName }}' => $className,
            '{{ controllerRoute }}' => $routeName,
            '{{ controllerView }}'  => $viewName,
            '{{ controllerNameSpace }}' => $this->controllerNamespace(),
        ];

        $this->generateFromStub('controller', $controllerFile, $tokens);

        $generateView = $this->askGenerateView();
        if ($generateView) {
            $this->generateFromStub('controller-view', $viewFile, []);
        }

        $this->showPostGenerationInfo('controller', [
            'type' => $this->type,
            'module' => $this->module,
            'name' => $this->name,
        ]);

        return 0;
    }

    private function askGenerateView(): bool
    {
        $this->prompt("\033[1;36mGenerate view? (y/n) [No]:\033[0m ");
        $input = trim(fgets(STDIN));

        if ($input === '') {
            return false;
        }

        $normalized = strtolower($input);
        return in_array($normalized, ['y', 'yes'], true);
    }
}
