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