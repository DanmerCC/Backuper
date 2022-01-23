<?php

namespace DanmerCC\Backuper\Core;


use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

abstract class Backuper
{

    protected static $temp_path = '/temp';

    public static $diskTemp = 'local';

    static function getAbsTempPath()
    {

        $absTemp = storage_path('app') . self::$temp_path;
        if (!is_dir($absTemp)) {
            mkdir($absTemp);
        }
        return $absTemp;
    }

    static function getAbsPathTempByNameId($name_id)
    {

        $absTemp = storage_path('app') . "/backups/" . $name_id . ".zip";
        return $absTemp;
    }

    static function clearTempPath($name_id)
    {

        return Storage::deleteDirectory(self::$temp_path . "/" . $name_id);
    }


    static function getRelTempPath()
    {

        $absTemp = storage_path('app') . self::$temp_path;
        if (!is_dir($absTemp)) {
            mkdir($absTemp);
        }
        return self::$temp_path;
    }

    static function runMysqlBackup($filename)
    {
        $time = time();
        $filename = storage_path() . "/temp/" . $time . "_" . $filename . ".sql";

        $connection =  config('backups.default');
        error_log("coneccion :" . $connection);
        $username = config("database.$connection.mysql.username");
        $password = config("database.$connection.mysql.password");
        $database = config("database.$connection.mysql.database");
        $host = config("database.$connection.mysql.host");
        $port = config("database.$connection.mysql.port");

        $command = "mysqldump -P $port -h $host -u $username --password=$password $database > " . $filename;
        exec($command);
        return $filename;
    }

    static function runFilesBackups($filename, $includeDataBase = false)
    {
        error_log("Iniciando backup de archivos " . ($includeDataBase ? " y base da datos" : ""));
        // Get real path for our folder
        $storageExcludes = ['temp', 'framework', 'debugbar'];

        /**
         * @var array storageFolders
         */
        $storageFolders = Storage::disk('storage')->directories();

        $extenalPathFolder = realpath(config('filesystems.disks.inscripciones.root'));

        // Initialize archive object
        $zip = new \ZipArchive();
        $timehash = time();
        $relativepathForNewFile = $timehash . '_' . $filename . '.zip';
        $newFileZip = storage_path('temp') . '/' . $relativepathForNewFile;
        $newFileGitVersion = storage_path('temp') . '/' . $timehash . 'commit-hash.txt';
        $fp = fopen($newFileGitVersion, 'w');
        //fwrite($fp, trim(exec('git log --pretty="%h" -n1 HEAD')));
        fwrite($fp, trim(exec('git rev-parse HEAD')));
        fclose($fp);

        if ($zip->open($newFileZip, \ZipArchive::CREATE || \ZipArchive::OVERWRITE) === TRUE) {

            $zip->addEmptyDir('uploads');
            $zip->addEmptyDir('storage');
            if ($includeDataBase) {
                $zip->addEmptyDir('database');
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extenalPathFolder),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($extenalPathFolder) + 1);

                    $zip->addFile($filePath, 'uploads/' . $relativePath);
                }
            }

            foreach ($storageFolders as $name) {
                if (!in_array($name, $storageExcludes)) {

                    $basePath = storage_path() . "/" . $name;
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($basePath),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    foreach ($files as $subname => $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($basePath) + 1);
                            $zip->addFile($filePath, 'storage/' . $name . '/' . $relativePath);
                        }
                    }
                }
            }

            if ($includeDataBase) {
                $sqlbackup = self::runMysqlBackup('databasescript');
                $zip->addFile($sqlbackup, 'database/database.sql');
            }


            $zip->addFile($newFileGitVersion, 'commit-hash.txt');

            if ($zip->close()) {
                error_log("Terminado de crear se zip");
            } else {
                error_log("No se creo zip");
            }
            unlink($newFileGitVersion);
            if ($includeDataBase) {
                unlink($sqlbackup);
            }

            error_log("Enviando backup $relativepathForNewFile a google drive");
            \Storage::disk('google')->put($filename, \Storage::disk('storage')->get("temp/" . $relativepathForNewFile));

            return $newFileZip;
        } else {
            throw new Exception("Error al generar una backup con nombre " . $filename);
        }

        throw new Exception("Error al generar una backup con nombre " . $filename);
    }

    static function list()
    {
        return \Storage::disk('local')->allFiles('/backups');
    }
}