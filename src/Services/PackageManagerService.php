<?php

namespace roilafx\PackageNavigator\Services;

use EvolutionCMS\Core;

class PackageManagerService
{
    protected $modx;
    protected $projectRoot;
    protected $customComposerPath;
    protected $providersConfigPath;
    protected $vendorPath;

    public function __construct(Core $modx)
    {
        $this->modx = $modx;
        $this->projectRoot = rtrim(base_path(), '/\\');
        $this->customComposerPath = $this->projectRoot . '/custom/composer.json';
        $this->providersConfigPath = $this->projectRoot . '/custom/config/app/providers';
        $this->vendorPath = $this->projectRoot . '/vendor';
    }

    /**
     * Установка Evolution CMS пакета через Artisan
     */
    public function installPackage($package, $version = '*')
    {
        $command = "cd \"{$this->projectRoot}\" && php artisan package:installrequire {$package} \"{$version}\"";

        set_time_limit(120);
        $result = $this->executeCommand($command, 120);

        return $result;
    }

    /**
     * Установка модуля из архива
     */
    public function installFromArchive($archiveFile)
    {
        $tempDir = $this->projectRoot . '/temp/modules/' . uniqid();
        $extractDir = $tempDir . '/extracted';
        
        try {
            // Создаем временные директории
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            if (!is_dir($extractDir)) {
                mkdir($extractDir, 0755, true);
            }

            // Сохраняем архив
            $archivePath = $tempDir . '/' . $archiveFile->getClientOriginalName();
            $archiveFile->move($tempDir, $archiveFile->getClientOriginalName());

            // Извлекаем архив
            $extracted = $this->extractArchive($archivePath, $extractDir);
            if (!$extracted) {
                throw new \Exception('Не удалось извлечь архив');
            }

            // Ищем composer.json в извлеченных файлах
            $composerJsonPath = $this->findComposerJson($extractDir);
            if (!$composerJsonPath) {
                throw new \Exception('composer.json не найден в архиве');
            }

            // Читаем информацию о пакете
            $composerData = json_decode(file_get_contents($composerJsonPath), true);
            $packageName = $composerData['name'] ?? null;
            
            if (!$packageName) {
                throw new \Exception('Не удалось определить имя пакета');
            }

            // Копируем файлы в vendor директорию
            $vendorPath = $this->vendorPath . '/' . $packageName;
            $this->copyDirectory($extractDir, $vendorPath);

            // Добавляем в custom/composer.json
            $this->addToCustomComposer($packageName, '*');

            // Устанавливаем через composer
            $command = "cd \"{$this->projectRoot}\" && composer update --no-interaction";
            $result = $this->executeCommand($command, 120);

            // Очищаем временные файлы
            $this->removeDirectory($tempDir);

            return [
                'success' => $result['success'],
                'package' => $packageName,
                'output' => $result['output'],
                'error' => $result['error'] ?? ''
            ];

        } catch (\Exception $e) {
            // Очищаем временные файлы в случае ошибки
            if (is_dir($tempDir)) {
                $this->removeDirectory($tempDir);
            }
            throw $e;
        }
    }

    /**
     * Извлечение архива
     */
    protected function extractArchive($archivePath, $extractDir)
    {
        $extension = pathinfo($archivePath, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'zip':
                $zip = new \ZipArchive();
                if ($zip->open($archivePath) === TRUE) {
                    $zip->extractTo($extractDir);
                    $zip->close();
                    return true;
                }
                break;
                
            case 'tar':
            case 'gz':
                $phar = new \PharData($archivePath);
                $phar->extractTo($extractDir);
                return true;
        }
        
        return false;
    }

    /**
     * Поиск composer.json в директории
     */
    protected function findComposerJson($directory)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'composer.json') {
                return $file->getPathname();
            }
        }
        
        return null;
    }

    /**
     * Рекурсивное копирование директории
     */
    protected function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $dir = opendir($source);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcFile = $source . '/' . $file;
                $destFile = $destination . '/' . $file;
                
                if (is_dir($srcFile)) {
                    $this->copyDirectory($srcFile, $destFile);
                } else {
                    copy($srcFile, $destFile);
                }
            }
        }
        
        closedir($dir);
    }

    /**
     * Рекурсивное удаление директории
     */
    protected function removeDirectory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        
        rmdir($directory);
    }

    /**
     * Добавление пакета в custom/composer.json
     */
    protected function addToCustomComposer($package, $version)
    {
        if (file_exists($this->customComposerPath)) {
            $composerData = json_decode(file_get_contents($this->customComposerPath), true);
        } else {
            $composerData = ['require' => []];
        }
        
        $composerData['require'][$package] = $version;
        
        file_put_contents(
            $this->customComposerPath, 
            json_encode($composerData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }

    /**
     * Удаление пакета с очисткой провайдеров
     */
    public function removePackage($package)
    {
        // 1. Получаем провайдеры пакета из его composer.json
        $packageProviders = $this->getPackageProviders($package);
        
        // 2. Удаляем файлы провайдеров этого пакета
        $removedFiles = $this->removeProviderFiles($package, $packageProviders);
        
        // 3. Удаляем пакет из custom/composer.json
        if (file_exists($this->customComposerPath)) {
            $composerData = json_decode(file_get_contents($this->customComposerPath), true);
            
            if (isset($composerData['require'][$package])) {
                unset($composerData['require'][$package]);
                
                // Сохраняем обновленный composer.json
                file_put_contents(
                    $this->customComposerPath, 
                    json_encode($composerData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                );
                
                // 4. Запускаем composer update для применения изменений
                $command = "cd \"{$this->projectRoot}\" && composer update --no-interaction";
                $result = $this->executeCommand($command, 120);
                
                // Добавляем информацию об удаленных файлах в результат
                $result['removed_files'] = $removedFiles;
                $result['package_providers'] = $packageProviders;
                
                return $result;
            }
        }
        
        return [
            'success' => false,
            'error' => 'Package not found in custom/composer.json'
        ];
    }

    /**
     * Получаем провайдеры пакета из его composer.json
     */
    protected function getPackageProviders($package)
    {
        $packagePath = $this->vendorPath . '/' . $package;
        $composerJsonPath = $packagePath . '/composer.json';
        
        if (!file_exists($composerJsonPath)) {
            return [];
        }
        
        $composerData = json_decode(file_get_contents($composerJsonPath), true);
        
        // Ищем провайдеры в extra->laravel->providers
        $providers = $composerData['extra']['laravel']['providers'] ?? [];
        
        // Также проверяем autoload.psr-4 для возможных провайдеров
        if (empty($providers)) {
            $providers = $this->discoverProvidersFromAutoload($composerData, $packagePath);
        }
        
        return $providers;
    }

    /**
     * Авто-обнаружение провайдеров из autoload
     */
    protected function discoverProvidersFromAutoload($composerData, $packagePath)
    {
        $providers = [];
        
        // Ищем ServiceProvider классы в PSR-4 autoload
        if (isset($composerData['autoload']['psr-4'])) {
            foreach ($composerData['autoload']['psr-4'] as $namespace => $path) {
                $fullPath = $packagePath . '/' . $path;
                if (is_dir($fullPath)) {
                    $foundProviders = $this->findServiceProviders($fullPath, $namespace);
                    $providers = array_merge($providers, $foundProviders);
                }
            }
        }
        
        return $providers;
    }

    /**
     * Рекурсивный поиск ServiceProvider классов
     */
    protected function findServiceProviders($directory, $namespace)
    {
        $providers = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace([$directory, '.php'], '', $file->getPathname());
                $className = $namespace . str_replace('/', '\\', $relativePath);
                
                if (class_exists($className) && $this->isServiceProvider($className)) {
                    $providers[] = $className;
                }
            }
        }
        
        return $providers;
    }

    /**
     * Проверяем, является ли класс ServiceProvider
     */
    protected function isServiceProvider($className)
    {
        try {
            $reflection = new \ReflectionClass($className);
            return $reflection->isSubclassOf(\Illuminate\Support\ServiceProvider::class);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Удаление файлов провайдеров для пакета
     */
    protected function removeProviderFiles($package, $packageProviders)
    {
        $removedFiles = [];
        
        if (is_dir($this->providersConfigPath)) {
            $files = scandir($this->providersConfigPath);
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filePath = $this->providersConfigPath . '/' . $file;
                    
                    if ($this->isProviderForPackage($filePath, $package, $packageProviders)) {
                        if (unlink($filePath)) {
                            $removedFiles[] = $file;
                        }
                    }
                }
            }
        }
        return $removedFiles;
    }

    /**
     * Проверяет, относится ли файл провайдера к указанному пакету
     */
    protected function isProviderForPackage($filePath, $package, $packageProviders)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $content = file_get_contents($filePath);
        
        // Ищем возвращаемый класс
        if (preg_match('/return\s+([^;]+)::class;/', $content, $matches)) {
            $providerClass = trim($matches[1]);
            
            // Проверяем, есть ли этот класс в списке провайдеров пакета
            if (in_array($providerClass, $packageProviders)) {
                return true;
            }
            
            // Дополнительная проверка по namespace пакета
            $packageParts = explode('/', $package);
            $vendorName = $packageParts[0] ?? '';
            
            if (stripos($providerClass, $vendorName) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Получение установленных пакетов из custom/composer.json
     */
    public function getInstalledPackages()
    {
        $packages = [];

        if (file_exists($this->customComposerPath)) {
            $composerData = json_decode(file_get_contents($this->customComposerPath), true);
            $customPackages = $composerData['require'] ?? [];
            
            foreach ($customPackages as $name => $version) {
                $packageInfo = $this->getPackageInfo($name);
                
                $packages[] = [
                    'name' => $name,
                    'version' => $version,
                    'description' => $packageInfo['description'],
                    'providers' => $packageInfo['providers']
                ];
            }
        }

        return $packages;
    }

    /**
     * Получение детальной информации о пакете
     */
    public function getPackageInfo($package)
    {
        $packagePath = $this->vendorPath . '/' . $package;
        $composerJsonPath = $packagePath . '/composer.json';
        
        if (!file_exists($composerJsonPath)) {
            // Если composer.json не найден, возвращаем базовую информацию
            return [
                'name' => $package,
                'description' => '',
                'version' => '',
                'providers' => [],
                'autoload' => [],
                'extra' => []
            ];
        }
        
        $composerData = json_decode(file_get_contents($composerJsonPath), true);
        $providers = $this->getPackageProviders($package);
        
        return [
            'name' => $package,
            'description' => $composerData['description'] ?? '',
            'version' => $composerData['version'] ?? '',
            'providers' => $providers,
            'autoload' => $composerData['autoload'] ?? [],
            'extra' => $composerData['extra'] ?? []
        ];
    }

    protected function executeCommand($command, $timeout = 60)
    {
        if (function_exists('shell_exec')) {
            $originalTimeout = ini_get('max_execution_time');
            set_time_limit($timeout);
            
            $output = shell_exec($command . ' 2>&1');
            
            set_time_limit($originalTimeout);
            
            if ($output !== null) {
                return [
                    'success' => !$this->containsError($output),
                    'output' => $output,
                    'error' => $this->containsError($output) ? $output : '',
                    'method' => 'shell_exec'
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Command execution failed',
            'method' => 'none'
        ];
    }

    protected function containsError($output)
    {
        $errors = [
            'Could not open input file',
            'is not recognized as an internal or external command',
            'command not found',
            'Permission denied',
            'No such file or directory'
        ];

        foreach ($errors as $error) {
            if (stripos($output, $error) !== false) {
                return true;
            }
        }

        return false;
    }

    public function getComposerVersion()
    {
        $command = "composer --version";
        $result = $this->executeCommand($command);
        
        if ($result['success']) {
            $output = trim($result['output']);
            // Берем только первую строку
            $lines = explode("\n", $output);
            return $lines[0] ?? $output;
        }
        
        return "Unknown";
    }
}