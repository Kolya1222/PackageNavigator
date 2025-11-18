<?php

namespace roilafx\PackageNavigator\Controllers;

use roilafx\PackageNavigator\Services\PackageManagerService;
use Illuminate\Http\Request;

class Module
{
    protected $packageService;

    public function __construct()
    {
        $this->packageService = new PackageManagerService(evolutionCMS());
    }

    public function index()
    {
        $packages = $this->packageService->getInstalledPackages();
        $composerVersion = $this->packageService->getComposerVersion();
        
        return view('PackageNavigator::index', [
            'packages' => $packages,
            'composerVersion' => $composerVersion,
        ]);
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
            $allowedMimes = ['zip', 'tar', 'tar.gz', 'rar'];
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