<?php

/*
 * This file is part of Pimple.
 *
 * Copyright (c) 2009 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Pimple main class.
 *
 * @package pimple
 * @author  Fabien Potencier
 */
class Pimple implements ArrayAccess
{
    private $values = array();

    /**
     * Sets a parameter or an object.
     *
     * @param string $id    The unique identifier for the parameter or object
     * @param mixed  $value The value of the parameter or a closure to defined an object
     */
    function offsetSet($id, $value)
    {
        $this->values[$id] = $value;
    }

    /**
     * Gets a parameter or an object.
     *
     * @param  string $id The unique identifier for the parameter or object
     *
     * @return mixed  The value of the parameter or an object
     *
     * @throws InvalidArgumentException if the identifier is not defined
     */
    function offsetGet($id)
    {
        if (!isset($this->values[$id])) {
            throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return is_callable($this->values[$id]) ? $this->values[$id]($this) : $this->values[$id];
    }

    /**
     * Checks if a parameter or an object is set.
     *
     * @return Boolean
     */
    function offsetExists($id)
    {
        return isset($this->values[$id]);
    }

    /**
     * Checks if a parameter or an object is set
     *
     * @throws InvalidArgumentException if the identifier is not defined
     */
    function offsetUnset($id)
    {
        unset($this->values[$id]);
    }

    /**
     * Returns a closure that stores the result of the given closure for
     * uniqueness in the scope of this instance of Pimple.
     *
     * @param Closure $callable A closure to wrap for uniqueness
     *
     * @return Closure The wrapped closure
     */
    function share(\Closure $callable)
    {
        return function ($c) use ($callable)
        {
            static $object;

            if (is_null($object)) {
                $object = $callable($c);
            }

            return $object;
        };
    }

    /**
     * Protects a callable from being interpreted as a service.
     *
     * This is useful when you want to store a callable as a parameter.
     *
     * @param Closure $callable A closure to protect from being evaluated
     *
     * @return Closure The protected closure
     */
    function protect(\Closure $callable)
    {
        return function ($c) use ($callable)
        {
            return $callable;
        };
    }
    

  /**
   * Make any values safe for seralisation
   * @author Sami at patabugen.co.uk
   */
  function __sleep()
  {
  	foreach ($this as &$value) {
  		if (is_callable($value)){
  			$value = $this->_serializeClosure($value);
  		}else{
			throw new Exception(print_r($value, true));  			
  		}
  	}
  	return array('values');
  }
  
  /**
   * Make restore any serialised values
   * @author Sami at patabugen.co.uk
   */
  function __wake()
  {
  	foreach ($this as &$value) {
  		if ($this->_isSerialized($value)){
  			$value = $this->_unserializeClosure($value);
  		}
  	}
  	return array('values');
  }
  
  /**
   * @param $str
   * @author chris AT cmbuckley DOT co DOT uk from PHP Docs
   */
  function _isSerialized($str) {
     return ($str == serialize(false) || @unserialize($str) !== false);
  }

  /**
   * 
   * @param $closure
   * @author     Jeremy Lindblom <http://webdevilaz.com>
   */
  function _serializeClosure($closure)
  {
  	$reflected = new ReflectionFunction($closure);
	if ( ! $reflected->isClosure())
		throw new RuntimeException();
	
	// Get the code
	$file = new SplFileObject($reflected->getFileName());
	$file->seek($reflected->getStartLine() - 1);
	$code = '';
	while ($file->key() < $reflected->getEndLine())
	{
		$code .= $file->current();
		$file->next();
	}
	$begin = strpos($code, 'function');
	$end   = strrpos($code, '}');
	$code  = substr($code, $begin, $end - $begin + 1);
	$context = $reflected->getStaticVariables();
	return serialize(array($code, $context));
  }
  
  /**
   * 
   * @param $closure
   * @author     Jeremy Lindblom <http://webdevilaz.com>
   */
  
  function _unserializeClosure($serialized)
  {
	list($code, $context) = unserialize($serialized);
	extract($context);
	@eval("\$_closure = $code;");
	if ( ! isset($_closure) OR ! is_callable($_closure))
		throw new RuntimeException();
	return $_closure;
  }
}
