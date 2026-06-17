<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Commands;

use Forge\CLI\Command;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;

#[Cli(
    command: 'tailwind:watch',
    description: 'Watch & rebuild Tailwind CSS automatically when source changes',
    usage: 'tailwind:watch [--input=CSS_PATH] [--output=CSS_PATH] [--platform=PLATFORM]',
    examples: [
        'tailwind:watch',
        'tailwind:watch --input=app/resources/css/tailwind.css --output=public/assets/css/app.css',
        'tailwind:watch --platform=linux-x64'
    ]
)]
final class WatchTailwindCommand extends Command
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

    #[Arg(
        name: 'platform',
        description: 'Tailwind binary platform (default: macos-arm64)',
        default: 'macos-arm64',
        required: false,
        validate: 'macos-arm64|macos-x64|windows-x64|linux-arm64|linux-arm64-musl|linux-x64|linux-x64-musl'
    )]
    private string $platform;

    private array $binaries = [
        'macos-arm64' => 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-macos-arm64',
        'macos-x64' => 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-macos-x64',
        'windows-x64' => 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-windows-x64.exe',
        'linux-arm64' => 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-arm64',
        'linux-arm64-musl' => 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-arm64-musl',
        'linux-x64' => 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-x64',
        'linux-x64-musl' => 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-x64-musl',
    ];

    public function execute(array $args): int
    {
        $this->wizard($args);

        $binPath = BASE_PATH . '/storage/bin';
        $binName = $this->platform === 'windows-x64' ? 'tailwindcss.exe' : 'tailwindcss';
        $bin = $binPath . '/' . $binName;

        if (!file_exists($bin)) {
            $this->info("Tailwind CSS binary not found for platform '{$this->platform}'. Downloading...", 'TailwindSetup');

            if (!is_dir($binPath)) {
                mkdir($binPath, 0755, true);
            }

            $url = $this->binaries[$this->platform] ?? null;
            if (!$url) {
                $this->error("Unsupported platform '{$this->platform}'");
                return 1;
            }

            $tempBin = $binPath . '/tailwindcss-temp';
            exec("curl -sL {$url} -o " . escapeshellarg($tempBin), $out, $ret);
            if ($ret !== 0) {
                $this->error("Failed to download Tailwind CSS binary: " . implode("\n", $out));
                return 1;
            }

            if ($this->platform !== 'windows-x64') {
                exec("chmod +x " . escapeshellarg($tempBin), $out, $ret);
                if ($ret !== 0) {
                    $this->error("Failed to set executable permission: " . implode("\n", $out));
                    unlink($tempBin);
                    return 1;
                }
            }

            exec("mv " . escapeshellarg($tempBin) . " " . escapeshellarg($bin), $out, $ret);
            if ($ret !== 0) {
                $this->error("Failed to move Tailwind binary: " . implode("\n", $out));
                if (file_exists($tempBin)) unlink($tempBin);
                return 1;
            }

            $this->info("Tailwind CSS binary setup complete for platform '{$this->platform}'.", 'TailwindSetup');
        }

        $input = escapeshellarg($this->inputCss ?? BASE_PATH . '/app/resources/assets/css/tailwind.css');
        $output = escapeshellarg($this->outputCss ?? BASE_PATH . '/public/assets/css/app.css');

        $this->info('Watching CSS for changes… (Ctrl-C to stop)', 'TailwindWatch');

        passthru(escapeshellarg($bin) . " -i {$input} -o {$output} --minify --watch");

        return 0;
    }
}