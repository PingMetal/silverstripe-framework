<?php

namespace SilverStripe\Core\Manifest;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\Deprecation;

/**
 * A class that handles loading classes and interfaces from a class manifest
 * instance.
 */
class ClassLoader
{

    /**
     * @var ClassLoader
     */
    private static $instance;

    /**
     * @var array Map of 'instance' (ClassManifest) and other options.
     */
    protected $manifests = array();

    /**
     * @return ClassLoader
     */
    public static function instance()
    {
        return self::$instance ? self::$instance : self::$instance = new self();
    }

    /**
     * Returns the currently active class manifest instance that is used for
     * loading classes.
     *
     * @return ClassManifest
     */
    public function getManifest()
    {
        return $this->manifests[count($this->manifests) - 1]['instance'];
    }

    /**
     * Returns true if this class loader has a manifest.
     */
    public function hasManifest()
    {
        return (bool)$this->manifests;
    }

    /**
     * Pushes a class manifest instance onto the top of the stack.
     *
     * @param ClassManifest $manifest
     * @param bool $exclusive Marks the manifest as exclusive. If set to FALSE, will
     * look for classes in earlier manifests as well.
     */
    public function pushManifest(ClassManifest $manifest, $exclusive = true)
    {
        $this->manifests[] = array('exclusive' => $exclusive, 'instance' => $manifest);
    }

    /**
     * @return ClassManifest
     */
    public function popManifest()
    {
        $manifest = array_pop($this->manifests);
        return $manifest['instance'];
    }

    public function registerAutoloader()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Loads a class or interface if it is present in the currently active
     * manifest.
     *
     * @param string $class
     * @return String
     */
    public function loadClass($class)
    {
        if ($path = $this->getItemPath($class)) {
            require_once $path;
        }
        return $path;
    }

    /**
     * Returns the path for a class or interface in the currently active manifest,
     * or any previous ones if later manifests aren't set to "exclusive".
     *
     * @param string $class
     * @return string|false
     */
    public function getItemPath($class)
    {
        foreach (array_reverse($this->manifests) as $manifest) {
            /** @var ClassManifest $manifestInst */
            $manifestInst = $manifest['instance'];
            if ($path = $manifestInst->getItemPath($class)) {
                return $path;
            }
            if ($manifest['exclusive']) {
                break;
            }
        }
        return false;
    }

    /**
     * Returns true if a class or interface name exists in the manifest.
     *
     * @param  string $class
     * @return bool
     */
    public function classExists($class)
    {
        Deprecation::notice('4.0', 'Use ClassInfo::exists.');
        return ClassInfo::exists($class);
    }
}
