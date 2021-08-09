<?php
namespace Swift\Framework\Utils;

class File
{
    /**
     * 递归删除目录
     *
     * @param string $dir
     */
    public static function removeDirRecursive(string $dir)
    {
        $dirHandle = opendir($dir);

        if (!$dirHandle) return;

        while(false !== ( $file = readdir($dirHandle)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $dir . '/' . $file;
                if ( is_dir($full) ) {
                    self::removeDirRecursive($full);
                }
                else {
                    unlink($full);
                }
            }
        }

        closedir($dirHandle);
        rmdir($dir);
    }

    /**
     * 获取根目录
     * @return string
     */
    public static function getRootDir(?string $dir = __DIR__): string
    {
//        define('rootDir',str_replace('\\','/',realpath(dirname($dir).'/')));
        define('rootDir', realpath(getcwd()));
        return rootDir;
    }

    public static function resolve(string $path = '')
    {
        if (substr($path, 0, 1) === '/') {
            return $path;
        }
        return File::getRootDir() . '/' . $path;
    }
}
