<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1467e64556b32d1f27c2a65f0994e96d
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Picqer\\Barcode\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Picqer\\Barcode\\' => 
        array (
            0 => __DIR__ . '/..' . '/picqer/php-barcode-generator/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1467e64556b32d1f27c2a65f0994e96d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1467e64556b32d1f27c2a65f0994e96d::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
