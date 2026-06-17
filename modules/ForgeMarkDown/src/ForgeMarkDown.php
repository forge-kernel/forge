<?php

namespace App\Modules\ForgeMarkDown;

class ForgeMarkDown
{
    public function parse(string $markdown): string
    {
        $html = $this->parseFrontMatter($markdown);
        $html = $this->parseBlockElements($html);
        $html = $this->parseInlineElements($html);
        return trim($html);
    }

    private function parseFrontMatter(string $markdown): string
    {
        if (preg_match('/^---\n(.+?)\n---\n(.*?)$/s', $markdown, $matches)) {
            return trim($matches[2]);
        }
        return $markdown;
    }

    private function parseBlockElements(string $markdown): string
    {
        $markdown = $this->parseCodeBlocks($markdown);

        $replacements = [
            '/^#{6} (.*)$/m' => function ($matches) {
                return '<h6>' . trim($matches[1]) . '</h6>';
            },
            '/^#{5} (.*)$/m' => function ($matches) {
                return '<h5>' . trim($matches[1]) . '</h5>';
            },
            '/^#{4} (.*)$/m' => function ($matches) {
                return '<h4>' . trim($matches[1]) . '</h4>';
            },
            '/^#{3} (.*)$/m' => function ($matches) {
                return '<h3>' . trim($matches[1]) . '</h3>';
            },
            '/^#{2} (.*)$/m' => function ($matches) {
                return '<h2>' . trim($matches[1]) . '</h2>';
            },
            '/^# (.*)$/m' => function ($matches) {
                return '<h1>' . trim($matches[1]) . '</h1>';
            },

            '/^[-*_]{3,}$/m' => function () {
                return '<hr>';
            },

            '/^> (.*)$/m' => function ($matches) {
                return '<blockquote>' . trim($matches[1]) . '</blockquote>';
            },

            '/(\n|^)([*\-+]) (.*?)(?=\n{2,}|$)/s' => function ($matches) {
                $items = preg_split('/\n[*\-+] /', $matches[0], -1, PREG_SPLIT_NO_EMPTY);
                $html = "\n<ul>\n";
                foreach ($items as $item) {
                    $html .= "<li>" . trim($item) . "</li>\n";
                }
                return $html . "</ul>";
            },

            '/^\|(.+)\|\n\|?(?:[-:]+[-| :]*)\|\n((?:^\|.*\|\n?)+)/m' => function ($matches) {
                return $this->parseTable($matches[1], $matches[2]);
            }
        ];

        return preg_replace_callback_array($replacements, $markdown);
    }

    private function parseInlineElements(string $markdown): string
    {
        $replacements = [
            '/(\*\*|__)(?=\S)(.+?)(?<=\S)(\*\*|__)/s' => function ($matches) {
                return '<strong>' . $matches[2] . '</strong>';
            },

            '/(\*|_)(?=\S)(.+?)(?<=\S)(\*|_)/s' => function ($matches) {
                return '<em>' . $matches[2] . '</em>';
            },

            '/~~(.+?)~~/s' => function ($matches) {
                return '<del>' . $matches[1] . '</del>';
            },

            '/!\[(.*?)\]\((.*?)\)/' => function ($matches) {
                return '<img src="' . htmlspecialchars($matches[2]) . '" alt="' . htmlspecialchars($matches[1]) . '">';
            },

            '/\[(.*?)\]\((.*?)\)/' => function ($matches) {
                return '<a href="' . htmlspecialchars($matches[2]) . '">' . $matches[1] . '</a>';
            },

            '/`([^`]+)`/' => function ($matches) {
                return '<code>' . htmlspecialchars($matches[1]) . '</code>';
            }
        ];

        return preg_replace_callback_array($replacements, $markdown);
    }

    private function parseCodeBlocks(string $markdown): string
    {
        $markdown = preg_replace_callback(
            '/^```([a-zA-Z0-9-+]*)?\n(.*?)\n```$/sm',
            function ($matches) {
                $lang = !empty($matches[1]) ? ' class="language-' . htmlspecialchars(trim($matches[1])) . '"' : '';
                return '<pre><code' . $lang . '>' . htmlspecialchars($matches[2]) . '</code></pre>';
            },
            $markdown
        );

        $markdown = preg_replace_callback(
            '/(?:\n|^)( {4}|\t)(.+?)(?=\n[^ \t]|$)/ms',
            function ($matches) {
                return '<pre><code>' . htmlspecialchars($matches[2]) . '</code></pre>';
            },
            $markdown
        );

        return $markdown;
    }

    private function parseTable(string $headers, string $rows): string
    {
        $headerCells = array_map('trim', explode('|', $headers));
        $rows = array_filter(explode("\n", $rows));

        $html = "<table>\n<thead>\n<tr>";
        foreach ($headerCells as $cell) {
            if (!empty($cell)) {
                $html .= '<th>' . trim($cell) . '</th>';
            }
        }
        $html .= "</tr>\n</thead>\n<tbody>";

        foreach ($rows as $row) {
            if (strpos(trim($row), '|') === 0) {
                $cells = array_map('trim', explode('|', trim($row)));
                $html .= "\n<tr>";
                foreach ($cells as $cell) {
                    if (!empty($cell)) {
                        $html .= '<td>' . $cell . '</td>';
                    }
                }
                $html .= "</tr>";
            }
        }

        return $html . "\n</tbody>\n</table>";
    }

    public function parseFile(string $path): array
    {
        $content = file_get_contents($path);
        $frontMatterResult = $this->extractFrontMatter($content);

        return [
            'content' => $this->parse($frontMatterResult['content']),
            'front_matter' => $frontMatterResult['front_matter'],
        ];
    }

    private function extractFrontMatter(string $content): array
    {
        $front_matter = [];
        $content_without_frontmatter = $content;

        if (preg_match('/^---\n(.+?)\n---\n(.*?)$/s', $content, $matches)) {
            try {
                $front_matter = yaml_parse($matches[1]) ?: [];
            } catch (\Exception $e) {
                $front_matter = ['error' => 'Invalid YAML syntax'];
            }
            $content_without_frontmatter = trim($matches[2]);
        }

        return [
            'front_matter' => $front_matter,
            'content' => $content_without_frontmatter,
        ];
    }
}
