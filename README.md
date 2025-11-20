# Установка
Выполните команды из директории `/core`:
1. Установка пакета
```
php artisan package:installrequire roilafx/packagenavigator "*"
```
2. Публикация стилей и скриптов
```
php artisan vendor:publish --provider="roilafx\PackageNavigator\PackageNavigatorServiceProvider"
```
