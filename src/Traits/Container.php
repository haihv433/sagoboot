<?php
/**
 * @author h2v23
 * @package SagoBoot | The mini-framework for scalable PHP application
 */

namespace SagoBoot\Traits;

if (!defined('SGB_PATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

use BadMethodCallException;
use ReflectionFunction;
use ReflectionMethod;
use SagoBoot\Framework\Illuminate\Support\Traits\Container as ContainerTrait;
use SagoBoot\Framework\Illuminate\Container\BindingResolutionException;

/**
 * Class Container.
 */
trait Container
{
	use ContainerTrait;

	/**
	 * Call the given callback and inject its dependencies
	 * @param $method
	 * @param array $parameters
	 *
	 * @return mixed
	 * @throws BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function call($method, array $parameters = [])
	{
		$func = $method;
		$inner = false;
		if (!is_callable($method) || (is_string($method) && method_exists($this, $method))) {
			if (is_array($method)) {
				throw new BadMethodCallException('method is not callable');
			}
			$func = [$this, $method];
			$inner = true;
		}
		/** @var ReflectionMethod|ReflectionFunction $reflector */
		$reflector = $this->getCallReflector($func);
		$dependencies = $this->getMethodDependencies(
			$reflector,
			$parameters
		);

		if ($inner) {
			$reflectionMethod = new ReflectionMethod($this, $method);
			if (!$reflectionMethod->isPublic()) {
				$reflectionMethod->setAccessible(true);
			}

			return $reflectionMethod->invokeArgs($this, $dependencies);
		} else {
			if ($func instanceof \Closure) {
				return call_user_func_array($func, $dependencies);
			} else {
				return $reflector instanceof ReflectionFunction ?
					$reflector->invokeArgs($dependencies) :
					$reflector->invokeArgs(sgb_app($reflector->class), $dependencies);
			}
		}
	}
}
