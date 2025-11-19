<?php

namespace roilafx\PackageNavigator\Controllers;

use roilafx\PackageNavigator\Services\PackageManagerService;
use Illuminate\Http\Request;

class Module
{
    protected $packageService;
    protected $remoteRepositoryUrl = 'https://raw.githubusercontent.com/Kolya1222/evolution-cms-extensions/main/packages.json';

    public function __construct()
    {
        $this->packageService = new PackageManagerService(evolutionCMS());
    }

    public function index()
    {
        $installedPackages = $this->packageService->getInstalledPackages();
        $remotePackages = $this->getRemotePackages();
        
        // Подготавливаем данные для фильтров
        $filterData = $this->prepareFilterData($remotePackages);
        
        return view('PackageNavigator::index', [
            'packages' => $installedPackages,
            'remotePackages' => $remotePackages,
            'composerVersion' => $this->packageService->getComposerVersion(),
        ] + $filterData);
    }

    private function prepareFilterData($packages)
    {
        $allCategories = [];
        $allTags = [];
        $allTypes = [];
        
        $categoryCounts = [];
        $tagCounts = [];
        $typeCounts = [];

        foreach ($packages as $package) {
            // Категории
            if (isset($package['categories']) && is_array($package['categories'])) {
                foreach ($package['categories'] as $category) {
                    $allCategories[] = $category;
                    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
                }
            }
            
            // Теги
            if (isset($package['tags']) && is_array($package['tags'])) {
                foreach ($package['tags'] as $tag) {
                    $allTags[] = $tag;
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
            
            // Типы
            if (isset($package['type'])) {
                $types = array_map('trim', explode('/', $package['type']));
                foreach ($types as $type) {
                    $allTypes[] = $type;
                    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                }
            }
        }

        return [
            'allCategories' => array_unique($allCategories),
            'allTags' => array_unique($allTags),
            'allTypes' => array_unique($allTypes),
            'categoryCounts' => $categoryCounts,
            'tagCounts' => $tagCounts,
            'typeCounts' => $typeCounts,
        ];
    }

    public function getRemotePackages()
    {
        $cacheKey = 'remote_packages';
        $cacheTime = 3600;

        try {
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'EvolutionCMS-PackageNavigator/1.0',
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            
            $response = @file_get_contents($this->remoteRepositoryUrl, false, $context);
            
            if ($response === false) {
                $error = error_get_last();
                return [];
            }

            if (strpos($response, '<!DOCTYPE') !== false || strpos($response, '<html') !== false) {
                return [];
            }

            if (substr($response, 0, 3) == "\xEF\xBB\xBF") {
                $response = substr($response, 3);
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            
            if (isset($data['packages'])) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, $data['packages'], $cacheTime);
                return $data['packages'];
            }
            
            return [];
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function installRemotePackage(Request $request)
    {
        $packageName = $request->input('package');
        
        // Получаем информацию о пакете из удаленного репозитория
        $remotePackages = $this->getRemotePackages();
        $packageInfo = collect($remotePackages)->firstWhere('composer_name', $packageName);
        
        if (!$packageInfo) {
            return response()->json([
                'success' => false,
                'error' => 'Package not found in repository'
            ]);
        }
        
        // Устанавливаем через Composer
        $result = $this->packageService->installPackage($packageInfo['composer_name']);
        
        return response()->json($result);
    }

    public function install(Request $request)
    {
        $package = $request->input('package');
        $version = $request->input('version', '*');

        if (empty($package)) {
            return response()->json([
                'success' => false,
                'error' => 'Package name is required'
            ]);
        }

        $result = $this->packageService->installPackage($package, $version);
        return response()->json($result);
    }

    /**
     * Загрузка и установка модуля из архива
     */
    public function uploadModule(Request $request)
    {
        try {
            if (!$request->hasFile('archive')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Файл не загружен'
                ]);
            }

            $file = $request->file('archive');
            
            // Проверка типа файла
            $allowedMimes = ['zip', 'tar', 'tar.gz'];
            if (!in_array($file->getClientOriginalExtension(), $allowedMimes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Неподдерживаемый формат архива'
                ]);
            }

            // Проверка размера (макс 50MB)
            if ($file->getSize() > 50 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'error' => 'Размер файла превышает 50MB'
                ]);
            }

            $result = $this->packageService->installFromArchive($file);
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function remove(Request $request)
    {
        $package = $request->input('package');

        if (empty($package)) {
            return response()->json([
                'success' => false,
                'error' => 'Package name is required'
            ]);
        }

        $result = $this->packageService->removePackage($package);
        return response()->json($result);
    }

    public function getPackageInfo(Request $request)
    {
        $package = $request->input('package');
        
        if (empty($package)) {
            return response()->json([
                'success' => false,
                'error' => 'Package name is required'
            ]);
        }

        $packageInfo = $this->packageService->getPackageInfo($package);
        
        return response()->json([
            'success' => true,
            'packageInfo' => $packageInfo
        ]);
    }
}