<?php

namespace ZhenMu\Support\Utils;

class CommandTool
{
    protected $executableFinder;

    protected $defaultExtraDirs = [
        '/usr/bin/',
        '/usr/local/bin/',
    ];

    public function __construct()
    {
        $this->executableFinder = new \Symfony\Component\Process\ExecutableFinder();

        if (function_exists('base_path')) {
            $this->defaultExtraDirs = array_merge($this->defaultExtraDirs, [base_path()]);
        }
    }

    public static function make()
    {
        return new static();
    }

    public static function getRealpath($path)
    {
        return realpath($path);
    }

    public static function formatCommand($command)
    {
        if (is_string($command)) {
            $command = explode(' ', $command);
        }

        return $command;
    }

    public function createProcess(array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60)
    {
        return tap(new \Symfony\Component\Process\Process(...func_get_args()));
    }
    
    public static function findBinary(string $name, array $extraDirs = [])
    {
        $instance = static::make();

        $extraDirs = array_merge($instance->defaultExtraDirs, $extraDirs);

        $extraDirs = array_map(fn ($dir) => rtrim($dir, '/'), $extraDirs);
        
        return $instance->executableFinder->find($name, null, $extraDirs);
    }

    public static function getPhpProcess(array $argument)
    {
        $instance = new static();
        
        $php = $instance->findBinary('php');

        return $instance->createProcess([$php, ...$argument]);
    }

    public static function getComposerProcess(array $argument)
    {
        $instance = new static();
        
        $php = $instance->findBinary('php');

        $composer = $instance->findBinary('composer');

        return $instance->createProcess([$php, $composer, ...$argument]);
    }
}
