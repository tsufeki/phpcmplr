<?php

namespace PhpCmplr\Symfony\Config;

class Config
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var Service[] id => Service.
     */
    private $services = [];

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addParameter($key, $value)
    {
        if ($key) {
            $this->parameters[$key] = $value;
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getParameter($key)
    {
        if (array_key_exists($key, $this->parameters)) {
            return $this->parameters[$key];
        }

        return null;
    }

    /**
     * @param Service $service
     *
     * @return $this
     */
    public function addService(Service $service)
    {
        $this->services[$service->getId()] = $service;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return Service|null
     */
    public function getService($id)
    {
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        return null;
    }

    /**
     * @return Service[]
     */
    public function getAllServices()
    {
        return array_values($this->services);
    }

    /**
     * @return Service[]
     */
    public function getPublicServices()
    {
        return array_values(array_filter(
            $this->getAllServices(),
            function (Service $service) { return $service->isPublic(); }
        ));
    }

    public function resolve()
    {
        foreach ($this->parameters as $key => $value) {
            // This ignores infinite loops silently.
            $this->parameters[$key] = '';
            $this->parameters[$key] = $this->resolveParam($value);
        }

        foreach ($this->getAllServices() as $service) {
            $service->setClass($this->resolveParam($service->getClass()));
        }
    }

    private function resolveParam($value)
    {
        if (is_string($value)) {
            $value = preg_replace_callback(
                '/%([^%]*)%/',
                function ($matches) {
                    $v = $this->resolveParam($this->getParameter($matches[1]));
                    return is_scalar($v) ? (string)$v : '';
                },
                $value);

        } elseif (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$this->resolveParam($key)] = $this->resolveParam($item);
            }
            $value = $result;
        }

        return $value;
    }
}
