<?php

declare(strict_types=1);

namespace Uzwebline\Linecms\App\TransferObjects;

use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionProperty;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Fluent;
use Symfony\Component\HttpFoundation\Response;

class ResultBase implements Responsable
{
    /** @var boolean */
    public $success = true;

    /** @var ?string */
    public $error = null;

    /** @var int */
    protected $http_status = 200;

    /** @var array */
    protected $exceptKeys = [];

    /** @var array */
    protected $onlyKeys = [];

    public function __construct(array $parameters = [])
    {
        $fields = $this->getFields();

        foreach ($fields as $field => $validator) {

            $value = $parameters[$field] ?? $this->{$field} ?? null;

            $this->{$field} = $value;

            unset($parameters[$field]);
        }

        //$this->data = new Fluent($parameters);
    }

    public function setError(string $error)
    {
        $this->success = false;
        $this->error = $error;
        return $this;
    }

    /*public function setHttpStatus(int $http_status)
    {
        $this->success = false;
        $this->http_status = $http_status;
        return $this;
    }*/

    protected function getFields(): array
    {
        return DTOCache::resolve(static::class, function () {
            $class = new ReflectionClass(static::class);

            $properties = [];

            foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                // Skip static properties
                if ($reflectionProperty->isStatic()) {
                    continue;
                }

                $field = $reflectionProperty->getName();

                $properties[$field] = $reflectionProperty;//FieldValidator::fromReflection($reflectionProperty);
            }

            return $properties;
        });
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function all(): array
    {
        $data = [];

        $class = new ReflectionClass(static::class);

        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $reflectionProperty) {
            // Skip static properties
            if ($reflectionProperty->isStatic()) {
                continue;
            }

            $data[$reflectionProperty->getName()] = $reflectionProperty->getValue($this);
        }

        return $data;
    }


    public function except(string ...$keys): ResultBase
    {
        $valueObject = clone $this;

        $valueObject->exceptKeys = array_merge($this->exceptKeys, $keys);

        return $valueObject;
    }

    public function toArray(): array
    {
        if (count($this->onlyKeys)) {
            $array = Arr::only($this->all(), $this->onlyKeys);
        } else {
            $array = Arr::except($this->all(), $this->exceptKeys);
        }

        $array = $this->parseArray($array);

        return $array;
    }

    protected function parseArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if ($value instanceof ResultBase || $value instanceof ResultBase) {
                $array[$key] = $value->toArray();
                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            $array[$key] = $this->parseArray($value);
        }

        return $array;
    }

    public function toResponse($request): Response
    {
        return new JsonResponse([
            "success" => $this->success,
            "error" => $this->error,
            "data" => $this->success ? $this->except('success', 'error')->toArray() : null
        ], $this->http_status);
    }
}
