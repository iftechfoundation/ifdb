<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'initdb',
        'node_modules',
    ])
    // Finder defaults to *.php only. IFDB also has a credentials template
    // and many extensionless PHP scripts under www/.
    ->name('*.php')
    ->name('local-credentials.php.template')
    ->name('/^[^.]+$/')
    ->filter(function (SplFileInfo $file): bool {
        $name = $file->getFilename();
        if (str_ends_with($name, '.php') || $name === 'local-credentials.php.template') {
            return true;
        }

        $handle = fopen($file->getPathname(), 'rb');
        if ($handle === false) {
            return false;
        }
        $start = fread($handle, 32);
        fclose($handle);
        $start = ltrim($start, " \t\r\n\xEF\xBB\xBF");

        return str_starts_with($start, '<?php') || str_starts_with($start, '<?=');
    });

return (new PhpCsFixer\Config())
    ->setRules([
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;
