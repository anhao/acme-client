<?php

declare(strict_types=1);
/**
 * This file is part of ALAPI.
 *
 * @package  ALAPI\Acme
 * @link     https://www.alapi.cn
 * @license  MIT License
 * @copyright ALAPI <im@alone88.cn>
 */

namespace ALAPI\Acme\Data;

use ReflectionClass;
use ReflectionProperty;

abstract class AbstractData
{
    /**
     * Convert object to array.
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $result = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($this);

            // If value is also AbstractData instance, convert recursively
            if ($value instanceof AbstractData) {
                $result[$name] = $value->toArray();
            } elseif (is_array($value)) {
                // Handle AbstractData objects in array
                $result[$name] = array_map(function ($item) {
                    return $item instanceof AbstractData ? $item->toArray() : $item;
                }, $value);
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert object to JSON string.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | $options);
    }

    /**
     * Create object instance from array.
     */
    public static function from(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            /* @phpstan-ignore-next-line */
            return new static();
        }

        $parameters = $constructor->getParameters();
        $args = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = $data[$name] ?? null;

            // If parameter has default value and no corresponding value in data, use default
            if ($value === null && $parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            }

            $args[] = $value;
        }

        /* @phpstan-ignore-next-line */
        return new static(...$args);
    }

    /**
     * Create object instance from JSON string.
     */
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true, 512, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return static::from($data);
    }
}
