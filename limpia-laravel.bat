@echo off
cls

echo.
echo --------------------------------------------------------------------------------
echo Iniciando limpieza de caches de Laravel...
echo.

echo --------------------------------------------------------------------------------
echo Eliminando carpeta bootstrap\cache para evitar datos cacheados obsoletos...
rmdir /s /q bootstrap\cache

echo --------------------------------------------------------------------------------
echo Recreando carpeta bootstrap\cache...
mkdir bootstrap\cache
(
echo *
echo !.gitignore
) > bootstrap\cache\.gitignore


echo --------------------------------------------------------------------------------
echo Limpiando cache de configuracion: php8 artisan config:clear
REM pause
php8 artisan config:clear

echo --------------------------------------------------------------------------------
echo Limpiando cache de rutas: php8 artisan route:clear
REM pause
php8 artisan route:clear

echo --------------------------------------------------------------------------------
echo Limpiando vistas Blade compiladas: php8 artisan view:clear
REM pause
php8 artisan view:clear

echo --------------------------------------------------------------------------------
echo Limpiando cache de eventos: php8 artisan event:clear
REM pause
php8 artisan event:clear

echo --------------------------------------------------------------------------------
echo Limpiando cache general de la aplicacion: php8 artisan cache:clear
REM pause
php8 artisan cache:clear

echo --------------------------------------------------------------------------------
echo Limpiando archivos compilados: php8 artisan clear-compiled
REM pause
php8 artisan clear-compiled

echo --------------------------------------------------------------------------------
echo Asegurandose de eliminar todo: php8 artisan optimize:clear
REM pause
php8 artisan optimize:clear

echo.
echo --------------------------------------------------------------------------------
echo Todos los comandos de limpieza han sido ejecutados.
REM pause

echo.
echo --------------------------------------------------------------------------------
echo Generando cache de configuracion: php8 artisan config:cache
REM pause
php8 artisan config:cache