<?php

namespace roilafx\PackageNavigator\Controllers;

use roilafx\PackageNavigator\Services\PackageManagerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * Контроллер навигатора пакетов
 * 
 * Предоставляет веб-интерфейс для управления пакетами Evolution CMS,
 * включая установку из удаленного репозитория, загрузку архивов и удаление пакетов.
 * 
 * @package roilafx\PackageNavigator\Controllers
 */
class Module
{
    /**
     * @var PackageManagerService Сервис управления пакетами
     */
    protected $packageService;
    
    /**
     * @var string URL удаленного репозитория со списком пакетов
     */
    protected $remoteRepositoryUrl = 'https://raw.githubusercontent.com/Kolya1222/evolution-cms-extensions/main/packages.json';

    /**
     * Конструктор
     * 
     * Инициализирует сервис управления пакетами с экземпляром Evolution CMS
     */
    public function __construct()
    {
        $this->packageService = new PackageManagerService(evolutionCMS());
    }

    /**
     * Отображение главного интерфейса управления пакетами
     * 
     * Показывает установленные пакеты, доступные удаленные пакеты и данные
     * для фильтров интерфейса навигатора пакетов.
     * 
     * @return \Illuminate\Contracts\View\View
     */
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

    /**
     * Подготовка данных для фильтров из списка пакетов
     * 
     * Извлекает категории, теги и типы из данных пакетов для фильтрации
     * и генерирует счетчики для каждой опции фильтра.
     * 
     * @param array $packages Массив данных пакетов
     * @return array Данные фильтров включая категории, теги, типы и их счетчики
     */
    private function prepareFilterData($packages)
    {
        $allCategories = [];
        $allTags = [];
        $allTypes = [];
        
        $categoryCounts = [];
        $tagCounts = [];
        $typeCounts = [];

        foreach ($packages as $package) {
            // Обработка категорий
            if (isset($package['categories']) && is_array($package['categories'])) {
                foreach ($package['categories'] as $category) {
                    $allCategories[] = $category;
                    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
                }
            }
            
            // Обработка тегов
            if (isset($package['tags']) && is_array($package['tags'])) {
                foreach ($package['tags'] as $tag) {
                    $allTags[] = $tag;
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
            
            // Обработка типов
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

    /**
     * Получение списка пакетов из удаленного репозитория
     * 
     * Загружает список пакетов из удаленного репозитория с поддержкой кэширования.
     * Обрабатывает различные ошибки и возвращает пустой массив при неудаче.
     * 
     * @return array Список удаленных пакетов или пустой массив при ошибке
     */
    public function getRemotePackages()
    {
        $cacheKey = 'remote_packages';
        $cacheTime = 3600;

        try {
            // Проверяем кэш
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            // Настраиваем контекст для HTTP-запроса
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
            
            // Выполняем запрос к удаленному репозиторию
            $response = @file_get_contents($this->remoteRepositoryUrl, false, $context);
            
            if ($response === false) {
                $error = error_get_last();
                return [];
            }

            // Проверяем, что ответ не HTML-страница
            if (strpos($response, '<!DOCTYPE') !== false || strpos($response, '<html') !== false) {
                return [];
            }

            // Убираем BOM маркер если присутствует
            if (substr($response, 0, 3) == "\xEF\xBB\xBF") {
                $response = substr($response, 3);
            }
            
            // Декодируем JSON
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            
            // Возвращаем пакеты и кэшируем результат
            if (isset($data['packages'])) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, $data['packages'], $cacheTime);
                return $data['packages'];
            }
            
            return [];
            
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Обновление пакета
     */
    public function updateModule(Request $request): JsonResponse
    {
        $package = $request->input('package');

        if (empty($package)) {
            return response()->json([
                'success' => false,
                'error' => 'Имя пакета обязательно'
            ]);
        }

        $result = $this->packageService->updatePackage($package);
        return response()->json($result);
    }

    /**
     * Установка пакета из удаленного репозитория
     * 
     * Проверяет наличие пакета в удаленном репозитории и устанавливает его
     * с использованием сервиса управления пакетами.
     * 
     * @param Request $request HTTP-запрос содержащий имя пакета
     * @return JsonResponse Результат установки со статусом успеха и выводом
     */
    public function installRemotePackage(Request $request): JsonResponse
    {
        $packageName = $request->input('package');
        
        // Получаем информацию о пакете из удаленного репозитория
        $remotePackages = $this->getRemotePackages();
        $packageInfo = collect($remotePackages)->firstWhere('composer_name', $packageName);
        
        if (!$packageInfo) {
            return response()->json([
                'success' => false,
                'error' => 'Пакет не найден в репозитории'
            ]);
        }
        
        // Устанавливаем через Composer
        $result = $this->packageService->installPackage($packageInfo['composer_name']);
        
        return response()->json($result);
    }

    /**
     * Установка пакета по имени и версии
     * 
     * Устанавливает пакет с использованием Composer с опциональным указанием версии.
     * Проверяет имя пакета и возвращает результат установки.
     * 
     * @param Request $request HTTP-запрос содержащий имя пакета и версию
     * @return JsonResponse Результат установки со статусом успеха и выводом
     */
    public function install(Request $request): JsonResponse
    {
        $package = $request->input('package');
        $version = $request->input('version', '*');

        if (empty($package)) {
            return response()->json([
                'success' => false,
                'error' => 'Имя пакета обязательно'
            ]);
        }

        $result = $this->packageService->installPackage($package, $version);
        return response()->json($result);
    }

    /**
     * Загрузка и установка модуля из архивного файла
     * 
     * Обрабатывает загрузку архивного файла, проверяет тип и размер файла,
     * затем устанавливает модуль с использованием сервиса управления пакетами.
     * 
     * @param Request $request HTTP-запрос содержащий архивный файл
     * @return JsonResponse Результат установки со статусом успеха и выводом
     * @throws Exception Если обработка архива не удалась
     */
    public function uploadModule(Request $request): JsonResponse
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

            // Проверка размера (максимум 50MB)
            if ($file->getSize() > 50 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'error' => 'Размер файла превышает 50MB'
                ]);
            }

            $result = $this->packageService->installFromArchive($file);
            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Удаление установленного пакета
     * 
     * Удаляет пакет через Composer и очищает связанные сервис-провайдеры и файлы.
     * 
     * @param Request $request HTTP-запрос содержащий имя пакета для удаления
     * @return JsonResponse Результат удаления со статусом успеха и выводом
     */
    public function remove(Request $request): JsonResponse
    {
        $package = $request->input('package');

        if (empty($package)) {
            return response()->json([
                'success' => false,
                'error' => 'Имя пакета обязательно'
            ]);
        }

        $result = $this->packageService->removePackage($package);
        return response()->json($result);
    }

    /**
     * Получение детальной информации о пакете
     * 
     * Получает подробную информацию об установленном пакете включая описание,
     * версию, провайдеры и конфигурацию автозагрузки.
     * 
     * @param Request $request HTTP-запрос содержащий имя пакета
     * @return JsonResponse Информация о пакете или ошибка если пакет не найден
     */
    public function getPackageInfo(Request $request): JsonResponse
    {
        $package = $request->input('package');
        
        if (empty($package)) {
            return response()->json([
                'success' => false,
                'error' => 'Имя пакета обязательно'
            ]);
        }

        $packageInfo = $this->packageService->getPackageInfo($package);
        
        return response()->json([
            'success' => true,
            'packageInfo' => $packageInfo
        ]);
    }
}