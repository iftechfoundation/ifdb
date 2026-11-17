<?php

// Files in stubs/ shadow www files that have side effects when included.
set_include_path(implode(PATH_SEPARATOR, [
    __DIR__ . '/stubs',
    dirname(__DIR__, 2),
    dirname(__DIR__, 2) . '/www',
]));
