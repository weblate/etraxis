<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App;

/**
 * A trait to access protected parts of an object.
 */
trait ReflectionTrait
{
    /**
     * Calls specified protected method of the object.
     *
     * @param mixed  $object Target object
     * @param string $name   Name of the method
     * @param array  $args   Optional arguments to pass to the method call
     *
     * @return null|mixed Method result
     */
    public function callMethod(mixed $object, string $name, array $args = []): mixed
    {
        try {
            $reflection = new \ReflectionMethod($object::class, $name);

            return $reflection->invokeArgs($object, $args);
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Sets specified protected property of the object.
     *
     * @param mixed  $object Target object
     * @param string $name   Name of the property
     * @param mixed  $value  New value to be set
     */
    public function setProperty(mixed $object, string $name, mixed $value): void
    {
        try {
            $reflection = new \ReflectionProperty($object::class, $name);
            $reflection->setValue($object, $value);
        } catch (\ReflectionException) {
        }
    }

    /**
     * Gets specified protected property of the object.
     *
     * @param mixed  $object Target object
     * @param string $name   Name of the property
     *
     * @return mixed Current value of the property
     */
    public function getProperty(mixed $object, string $name): mixed
    {
        try {
            $reflection = new \ReflectionProperty($object::class, $name);

            return $reflection->getValue($object);
        } catch (\ReflectionException) {
            return null;
        }
    }
}
