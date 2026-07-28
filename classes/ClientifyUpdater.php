<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ClientifyUpdater
{
    const GITHUB_REPO = 'emersonaly/prestashop-clientify'; // TODO: cambiar a ClientifySL/clientify-addons-prestashop
    const GITHUB_API  = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';
    const CACHE_KEY   = 'CLIENTIFY_UPDATE_CACHE';
    const CACHE_TTL   = 3600; // 1 hora

    /**
     * Retorna info de la última release o null si hay error.
     * Resultado cacheado en PS Configuration para no abusar de la API.
     */
    public static function getLatestRelease()
    {
        $cached = Configuration::get(self::CACHE_KEY);
        if ($cached) {
            $data = json_decode($cached, true);
            if ($data && isset($data['fetched_at']) && (time() - $data['fetched_at']) < self::CACHE_TTL) {
                return $data;
            }
        }

        $ch = curl_init(self::GITHUB_API);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => 'PrestaShop-Clientify-Updater',
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $release = json_decode($response, true);
        if (!isset($release['tag_name'])) {
            return null;
        }

        $zipUrl = null;
        foreach ($release['assets'] as $asset) {
            if (strpos($asset['name'], '.zip') !== false) {
                $zipUrl = $asset['browser_download_url'];
                break;
            }
        }

        $data = [
            'version'    => ltrim($release['tag_name'], 'v'),
            'tag'        => $release['tag_name'],
            'zip_url'    => $zipUrl,
            'body'       => $release['body'] ?? '',
            'html_url'   => $release['html_url'],
            'fetched_at' => time(),
        ];

        Configuration::updateValue(self::CACHE_KEY, json_encode($data));
        return $data;
    }

    /**
     * Compara versión remota con la instalada.
     */
    public static function hasUpdate($currentVersion)
    {
        $release = self::getLatestRelease();
        if (!$release) {
            return false;
        }
        return version_compare($release['version'], $currentVersion, '>');
    }

    /**
     * Descarga el ZIP de la release, extrae y reemplaza los archivos del módulo.
     * Retorna ['success' => bool, 'message' => string]
     */
    public static function doUpdate($currentVersion)
    {
        $release = self::getLatestRelease();

        if (!$release) {
            return ['success' => false, 'message' => 'No se pudo obtener información de la última versión.'];
        }

        if (!version_compare($release['version'], $currentVersion, '>')) {
            return ['success' => false, 'message' => 'Ya tienes la versión más reciente instalada.'];
        }

        if (!$release['zip_url']) {
            return ['success' => false, 'message' => 'No se encontró el archivo ZIP en la release.'];
        }

        // Directorio temporal
        $tmpDir  = sys_get_temp_dir() . '/clientify_update_' . time();
        $zipFile = $tmpDir . '/release.zip';
        mkdir($tmpDir, 0755, true);

        // Descargar ZIP
        $ch = curl_init($release['zip_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $zipData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$zipData) {
            self::cleanTmp($tmpDir);
            return ['success' => false, 'message' => 'Error al descargar el archivo de actualización (HTTP ' . $httpCode . ').'];
        }

        file_put_contents($zipFile, $zipData);

        // Extraer ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            self::cleanTmp($tmpDir);
            return ['success' => false, 'message' => 'No se pudo abrir el archivo ZIP descargado.'];
        }

        $extractDir = $tmpDir . '/extracted';
        mkdir($extractDir, 0755, true);
        $zip->extractTo($extractDir);
        $zip->close();

        // El ZIP tiene carpeta clientify/ dentro — buscarla
        $moduleSource = $extractDir . '/clientify';
        if (!is_dir($moduleSource)) {
            // Buscar cualquier subcarpeta
            $dirs = glob($extractDir . '/*', GLOB_ONLYDIR);
            if (!empty($dirs)) {
                $moduleSource = $dirs[0];
            }
        }

        if (!is_dir($moduleSource)) {
            self::cleanTmp($tmpDir);
            return ['success' => false, 'message' => 'Estructura del ZIP inválida: no se encontró la carpeta del módulo.'];
        }

        // Copiar archivos al directorio del módulo
        $moduleDir = _PS_MODULE_DIR_ . 'clientify/';
        self::copyDirectory($moduleSource, $moduleDir);

        // Limpiar tmp y caché
        self::cleanTmp($tmpDir);
        Configuration::deleteByName(self::CACHE_KEY);

        return ['success' => true, 'message' => 'Módulo actualizado a la versión ' . $release['version'] . ' correctamente.'];
    }

    private static function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            if (is_dir($srcPath)) {
                self::copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        closedir($dir);
    }

    private static function cleanTmp($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($dir);
    }
}
