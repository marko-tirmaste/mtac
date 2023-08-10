<?php

spl_autoload_register(function ($class) {
    /* @var $prefix string */
    $prefix = 'Vdisain\\Mtac\\';

    /* @var $length int */
    $length = strlen($prefix);
    if (strncmp($prefix, $class, $length) !== 0) {
        return;
    }

    /* @var $relativeClass string */
    $relativeClass = substr($class, $length);

    /* @var $path string */
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($path)) {
        require $path;
    } else {
        throw new Exception(sprintf('Class with name %1$s not found. Looked in %2$s.', $class, $path));
    }
});