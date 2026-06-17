<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Commands;

use Forge\CLI\Command;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;

#[Cli(
    command: 'tailwind:build',
    description: 'Build Tailwind CSS from source using the binary',
    usage: 'tailwind:build [--input=CSS_PATH] [--output=CSS_PATH]',
    examples: [
        'tailwind:build',
        'tailwind:build --input=app/resources/css/tailwind.css --output=public/assets/css/app.css'
    ]
)]
final class BuildTailwindCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(
        name: 'input',
        description: 'Input CSS file path (default: app/resources/assets/css/tailwind.css)',
        required: false
    )]
    private ?string $inputCss = null;

    #[Arg(
        name: 'output',
        description: 'Output CSS file path (default: public/assets/css/app.css)',
        required: false
    )]
    private ?string $outputCss = null;

    public function execute(array $args): int
    {
        $this->wizard($args);

        $binPath = BASE_PATH . '/storage/bin';
        $bin = $binPath . '/tailwindcss';

        // Setup binary if it doesn't exist
        if (!file_exists($bin)) {
            $this->info('Tailwind CSS binary not found. Downloading and setting up...', 'TailwindSetup');

            if (!is_dir($binPath)) {
                mkdir($binPath, 0755, true);
            }

            $downloadUrl = 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-macos-arm64';
            $tempBin = $binPath . '/tailwindcss-temp';

            exec("curl -sL {$downloadUrl} -o " . escapeshellarg($tempBin), $out, $ret);
            if ($ret !== 0) {
                $this->error("Failed to download Tailwind CSS binary: " . implode("\n", $out), 'TailwindSetup');
                return 1;
            }

            exec("chmod +x " . escapeshellarg($tempBin), $out, $ret);
            if ($ret !== 0) {
                $this->error("Failed to make Tailwind binary executable: " . implode("\n", $out), 'TailwindSetup');
                unlink($tempBin);
                return 1;
            }

            exec("mv " . escapeshellarg($tempBin) . " " . escapeshellarg($bin), $out, $ret);
            if ($ret !== 0) {
                $this->error("Failed to move Tailwind binary: " . implode("\n", $out), 'TailwindSetup');
                unlink($tempBin);
                return 1;
            }

            $this->info('Tailwind CSS binary setup complete.', 'TailwindSetup');
        }

        $input = escapeshellarg($this->inputCss ?? BASE_PATH . '/app/resources/assets/css/tailwind.css');
        $output = escapeshellarg($this->outputCss ?? BASE_PATH . '/public/assets/css/app.css');

        $cmd = escapeshellarg($bin) . " -i {$input} -o {$output} --minify 2>&1";
        exec($cmd, $out, $ret);

        if ($ret !== 0) {
            $this->error("TailwindBuild failed: " . implode("\n", $out));
            return 1;
        }

        $this->success("Tailwind CSS built successfully!", 'TailwindBuild');
        return 0;
    }
}