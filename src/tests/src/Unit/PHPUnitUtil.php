<?php

/**
 * Description of PHPUnitUtil
 *
 * @author nczirjak
 */

class PHPUnitUtil
{
    public static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }
}
