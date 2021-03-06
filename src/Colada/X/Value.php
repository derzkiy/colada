<?php

namespace Colada\X;

/**
 * @todo __isset()
 * @todo __unset()
 *
 * @todo \Iterator support.
 * @todo \Countable support.
 *
 * @author Alexey Shockov <alexey@shockov.com>
 *
 * @internal
 */
class Value implements \ArrayAccess
{
    private $value;

    private static $methods = array();

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __getValue()
    {
        return $this->value;
    }

    public static function registerHelper($context)
    {
        // Remove magic methods.
        $methods = array_filter(get_class_methods($context), function($method) { return !preg_match('/^__/', $method); });
        $methods = array_flip($methods);
        foreach ($methods as $method => $index) {
            $methods[$method] = $context;
        }

        // TODO Replace previous? Or not?
        static::$methods = array_merge(static::$methods, $methods);
    }

    public function offsetGet($key)
    {
        if (is_array($this->value) || (is_object($this->value) && ($this->value instanceof \ArrayAccess))) {
            return new static($this->value[$key]);
        }

        throw new \BadMethodCallException('ArrayAccess unsupported for current value.');
    }

    public function offsetUnset($key)
    {
        if (is_array($this->value) || (is_object($this->value) && ($this->value instanceof \ArrayAccess))) {
            unset($this->value[$key]);

            return new static(null);
        }

        throw new \BadMethodCallException('ArrayAccess unsupported for current value.');
    }

    public function offsetExists($key)
    {
        if (is_array($this->value) || (is_object($this->value) && ($this->value instanceof \ArrayAccess))) {
            return new static(isset($this->value[$key]));
        }

        throw new \BadMethodCallException('ArrayAccess unsupported for current value.');
    }

    public function offsetSet($key, $value)
    {
        if (is_array($this->value) || (is_object($this->value) && ($this->value instanceof \ArrayAccess))) {
            $this->value[$key] = $value;

            // Or return NULL (by specification)?
            return new static($value);
        }

        throw new \BadMethodCallException('ArrayAccess unsupported for current value.');
    }

    public function __get($property)
    {
        return new static($this->value->$property);
    }

    public function __set($property, $value)
    {
        $this->value->$property = $value;

        return new static($value);
    }

    public function __call($name, $arguments)
    {
        $method = null;
        if (is_object($this->value) && is_callable(array($this->value, $name))) {
            $method = array($this->value, $name);
        } elseif (isset(static::$methods[$name])) {
            $method = array(static::$methods[$name], $name);

            array_unshift($arguments, $this->value);
        } else {
            throw new \BadMethodCallException('Unknown method "'.$name.'".');
        }

        $result = call_user_func_array($method, $arguments);

        return new static($result);
    }

    public function __toString()
    {
        return (string) $this->value;
    }
}

// Register default helpers... Some better place for that?
Value::registerHelper(new \Colada\Helpers\TypeHelper());
Value::registerHelper(new \Colada\Helpers\NumericHelper());
Value::registerHelper(new \Colada\Helpers\StringHelper());
Value::registerHelper(new \Colada\Helpers\CollectionHelper());
Value::registerHelper(new \Colada\Helpers\ComparisonHelper());
