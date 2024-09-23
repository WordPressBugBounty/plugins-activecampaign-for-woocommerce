<?php

namespace AcVendor\Composer;

use AcVendor\Composer\Autoload\ClassLoader;
use AcVendor\Composer\Semver\VersionParser;
class InstalledVersions
{
    private static $installed = array('root' => array('pretty_version' => '1.5.0', 'version' => '1.5.0.0', 'aliases' => array(), 'reference' => NULL, 'name' => 'activecampaign/activecampaign-for-woocommerce'), 'versions' => array('activecampaign/activecampaign-for-woocommerce' => array('pretty_version' => '1.5.0', 'version' => '1.5.0.0', 'aliases' => array(), 'reference' => NULL), 'brick/math' => array('pretty_version' => '0.10.2', 'version' => '0.10.2.0', 'aliases' => array(), 'reference' => '459f2781e1a08d52ee56b0b1444086e038561e3f'), 'brick/money' => array('pretty_version' => '0.6.0', 'version' => '0.6.0.0', 'aliases' => array(), 'reference' => '7074e1bd463f517fb78447dff63454f4b1523c1b'), 'guzzlehttp/guzzle' => array('pretty_version' => '7.8.1', 'version' => '7.8.1.0', 'aliases' => array(), 'reference' => '41042bc7ab002487b876a0683fc8dce04ddce104'), 'guzzlehttp/promises' => array('pretty_version' => '2.0.2', 'version' => '2.0.2.0', 'aliases' => array(), 'reference' => 'bbff78d96034045e58e13dedd6ad91b5d1253223'), 'guzzlehttp/psr7' => array('pretty_version' => '2.6.2', 'version' => '2.6.2.0', 'aliases' => array(), 'reference' => '45b30f99ac27b5ca93cb4831afe16285f57b8221'), 'laravel/serializable-closure' => array('pretty_version' => 'v1.3.3', 'version' => '1.3.3.0', 'aliases' => array(), 'reference' => '3dbf8a8e914634c48d389c1234552666b3d43754'), 'php-di/invoker' => array('pretty_version' => '2.3.4', 'version' => '2.3.4.0', 'aliases' => array(), 'reference' => '33234b32dafa8eb69202f950a1fc92055ed76a86'), 'php-di/php-di' => array('pretty_version' => '6.4.0', 'version' => '6.4.0.0', 'aliases' => array(), 'reference' => 'ae0f1b3b03d8b29dff81747063cbfd6276246cc4'), 'php-di/phpdoc-reader' => array('pretty_version' => '2.2.1', 'version' => '2.2.1.0', 'aliases' => array(), 'reference' => '66daff34cbd2627740ffec9469ffbac9f8c8185c'), 'psr/container' => array('pretty_version' => '1.0.0', 'version' => '1.0.0.0', 'aliases' => array(), 'reference' => 'b7ce3b176482dbbc1245ebf52b181af44c2cf55f'), 'psr/container-implementation' => array('provided' => array(0 => '^1.0')), 'psr/http-client' => array('pretty_version' => '1.0.3', 'version' => '1.0.3.0', 'aliases' => array(), 'reference' => 'bb5906edc1c324c9a05aa0873d40117941e5fa90'), 'psr/http-client-implementation' => array('provided' => array(0 => '1.0')), 'psr/http-factory' => array('pretty_version' => '1.0.2', 'version' => '1.0.2.0', 'aliases' => array(), 'reference' => 'e616d01114759c4c489f93b099585439f795fe35'), 'psr/http-factory-implementation' => array('provided' => array(0 => '1.0')), 'psr/http-message' => array('pretty_version' => '2.0', 'version' => '2.0.0.0', 'aliases' => array(), 'reference' => '402d35bcb92c70c026d1a6a9883f06b2ead23d71'), 'psr/http-message-implementation' => array('provided' => array(0 => '1.0')), 'psr/log' => array('pretty_version' => '1.1.4', 'version' => '1.1.4.0', 'aliases' => array(), 'reference' => 'd49695b909c3b7628b6289db5479a1c204601f11'), 'ralouphie/getallheaders' => array('pretty_version' => '3.0.3', 'version' => '3.0.3.0', 'aliases' => array(), 'reference' => '120b605dfeb996808c31b6477290a714d356e822'), 'symfony/deprecation-contracts' => array('pretty_version' => 'v2.5.2', 'version' => '2.5.2.0', 'aliases' => array(), 'reference' => 'e8b495ea28c1d97b5e0c121748d6f9b53d075c66')));
    private static $canGetVendors;
    private static $installedByVendor = array();
    public static function getInstalledPackages()
    {
        $packages = array();
        foreach (self::getInstalled() as $installed) {
            $packages[] = \array_keys($installed['versions']);
        }
        if (1 === \count($packages)) {
            return $packages[0];
        }
        return \array_keys(\array_flip(\call_user_func_array('array_merge', $packages)));
    }
    public static function isInstalled($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (isset($installed['versions'][$packageName])) {
                return \true;
            }
        }
        return \false;
    }
    public static function satisfies(VersionParser $parser, $packageName, $constraint)
    {
        $constraint = $parser->parseConstraints($constraint);
        $provided = $parser->parseConstraints(self::getVersionRanges($packageName));
        return $provided->matches($constraint);
    }
    public static function getVersionRanges($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }
            $ranges = array();
            if (isset($installed['versions'][$packageName]['pretty_version'])) {
                $ranges[] = $installed['versions'][$packageName]['pretty_version'];
            }
            if (\array_key_exists('aliases', $installed['versions'][$packageName])) {
                $ranges = \array_merge($ranges, $installed['versions'][$packageName]['aliases']);
            }
            if (\array_key_exists('replaced', $installed['versions'][$packageName])) {
                $ranges = \array_merge($ranges, $installed['versions'][$packageName]['replaced']);
            }
            if (\array_key_exists('provided', $installed['versions'][$packageName])) {
                $ranges = \array_merge($ranges, $installed['versions'][$packageName]['provided']);
            }
            return \implode(' || ', $ranges);
        }
        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }
    public static function getVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }
            if (!isset($installed['versions'][$packageName]['version'])) {
                return null;
            }
            return $installed['versions'][$packageName]['version'];
        }
        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }
    public static function getPrettyVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }
            if (!isset($installed['versions'][$packageName]['pretty_version'])) {
                return null;
            }
            return $installed['versions'][$packageName]['pretty_version'];
        }
        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }
    public static function getReference($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }
            if (!isset($installed['versions'][$packageName]['reference'])) {
                return null;
            }
            return $installed['versions'][$packageName]['reference'];
        }
        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }
    public static function getRootPackage()
    {
        $installed = self::getInstalled();
        return $installed[0]['root'];
    }
    public static function getRawData()
    {
        return self::$installed;
    }
    public static function reload($data)
    {
        self::$installed = $data;
        self::$installedByVendor = array();
    }
    private static function getInstalled()
    {
        if (null === self::$canGetVendors) {
            self::$canGetVendors = \method_exists('AcVendor\\Composer\\Autoload\\ClassLoader', 'getRegisteredLoaders');
        }
        $installed = array();
        if (self::$canGetVendors) {
            foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
                if (isset(self::$installedByVendor[$vendorDir])) {
                    $installed[] = self::$installedByVendor[$vendorDir];
                } elseif (\is_file($vendorDir . '/composer/installed.php')) {
                    $installed[] = self::$installedByVendor[$vendorDir] = (require $vendorDir . '/composer/installed.php');
                }
            }
        }
        $installed[] = self::$installed;
        return $installed;
    }
}
