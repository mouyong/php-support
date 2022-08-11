<?php

namespace ZhenMu\Support\Utils;

use Illuminate\Support\Facades\File;
use PhpZip\ZipFile;

class Zip
{
    protected $zipFile;

    public function __construct()
    {
        $this->zipFile = new ZipFile();
    }

    public function pack(string $sourcePath, ?string $filename = null, ?string $targetPath = null): ?string
    {
        if (!File::exists($sourcePath)) {
            throw new \RuntimeException("待解压目录不存在 {$sourcePath}");
        }

        $filename = $filename ?? File::name($sourcePath);
        $targetPath = $targetPath ?? File::dirname($sourcePath);
        $targetPath = $targetPath ?: File::dirname($sourcePath);

        File::ensureDirectoryExists($targetPath);

        $zipFilename = str_contains($filename, '.zip') ? $filename : $filename . '.zip';
        $zipFilepath = "{$targetPath}/{$zipFilename}";

        while (File::exists($zipFilepath)) {
            $basename = File::name($zipFilepath);
            $zipCount = count(File::glob("{$targetPath}/{$basename}*.zip"));

            $zipFilename = $basename . ($zipCount) . '.zip';
            $zipFilepath = "{$targetPath}/{$zipFilename}";
        }

        // 压缩
        $this->zipFile->addDirRecursive($sourcePath, $filename);
        $this->zipFile->saveAsFile($zipFilepath);

        return $targetPath;
    }

    public function unpack(string $sourcePath, ?string $targetPath = null): ?string
    {
        try {
            // 检测文件类型，只有 zip 文件才进行解压操作
            $mimeType = File::mimeType($sourcePath);
        } catch (\Throwable $e) {
            \info("解压失败 {$e->getMessage()}");
            throw new \RuntimeException("解压失败 {$e->getMessage()}");
        }

        // 获取文件类型（只处理目录和 zip 文件）
        $type = match (true) {
            default => null,
            str_contains($mimeType, 'directory') => 1,
            str_contains($mimeType, 'zip') => 2,
        };

        if (is_null($type)) {
            \info("unsupport mime type $mimeType");
            throw new \RuntimeException("unsupport mime type $mimeType");
        }

        // 确保解压目标目录存在
        $targetPath = $targetPath ?? storage_path('app/extensions/.tmp');
        if (empty($targetPath)) {
            throw new \RuntimeException("targetPath cannot be empty.");
        }

        if (!is_dir($targetPath)) {
            File::ensureDirectoryExists($targetPath);
        }

        // 清空目录，避免留下其他插件的文件
        File::cleanDirectory($targetPath);

        // 目录无需解压操作，将原目录拷贝到临时目录中
        if ($type == 1) {
            File::copyDirectory($sourcePath, $targetPath);

            // 确保目录解压层级是插件目录顶层
            $this->ensureDoesntHaveSubdir($targetPath);

            return $targetPath;
        }

        if ($type == 2) {
            // 解压
            $zipFile = $this->zipFile->openFile($sourcePath);
            $zipFile->extractTo($targetPath);

            // 确保目录解压层级是插件目录顶层
            $this->ensureDoesntHaveSubdir($targetPath);

            // 解压到指定目录
            return $targetPath;
        }

        return null;
    }

    public function ensureDoesntHaveSubdir(string $targetPath): string
    {
        $targetPath = $targetPath ?? storage_path('app/extensions/.tmp');

        $pattern = sprintf("%s/*", rtrim($targetPath, DIRECTORY_SEPARATOR));
        $files = File::glob($pattern);

        if (count($files) > 1) {
            return $targetPath;
        }

        $tmpDir = $targetPath . '-subdir';
        File::ensureDirectoryExists($tmpDir);

        $firstEntryname = File::name(current($files));

        File::copyDirectory($targetPath . "/{$firstEntryname}", $tmpDir);
        File::cleanDirectory($targetPath);
        File::copyDirectory($tmpDir, $targetPath);
        File::deleteDirectory($tmpDir);

        return $targetPath;
    }
}
