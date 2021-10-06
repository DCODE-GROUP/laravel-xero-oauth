<?php

namespace Dcodegroup\LaravelXeroOauth;

use Exception;
use XeroPHP\Application;
use XeroPHP\Remote\Exception\NotFoundException;

class BaseXeroService
{
    public Application $xeroClient;

    public function __construct(Application $xeroClient)
    {
        $this->xeroClient = $xeroClient;
    }

    public function getModel($model, $guid = null, $parameter = null)
    {
        if ($guid) {
            $response = $this->xeroClient->loadByGUID($model, $guid);
        } else {
            $response = $this->xeroClient->load($model);
        }

        if ($parameter) {
            $response = $response->first()->{'get' . $parameter}();
        } else {
            if (! $guid) {
                $response = $response->execute();
            }
        }

        return ! $guid ? collect($response) : $response;
    }

    public function searchModel($model, array $where, $guids = null, $parameter = null)
    {
        if (! is_null($guids)) {
            $response = $this->xeroClient->loadByGUIDs($model, $guids);
        } else {
            $response = $this->xeroClient->load($model);
        }

        foreach ($where as $p => $value) {
            $response->where($p, $value);
        }

        if ($parameter) {
            return $response->first()->{'get' . $parameter}();
        }

        return $response->first();
    }

    public function saveModel($model, array $parameters = [], array $objects = [])
    {
        $request = new $model($this->xeroClient);

        foreach ($parameters as $parameter => $value) {
            $request->{'set' . $parameter}($value);
        }

        foreach ($objects as $object => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $request->{'add' . (is_string($key) ? $key : $object)}($item);
                }
            } else {
                $request->{'add' . $object}($value);
            }
        }

        try {
            $request->save();
        } catch (Exception | NotFoundException $e) {
            report($e);
        }

        return $request;
    }

    public function updateModel($model, $guid, array $parameters = [], array $objects = [])
    {
        if (is_object($guid)) {
            $request = new $model($this->xeroClient);
            $request->{'set' . $guid->identifier}($guid->guid);
        } else {
            $request = $this->xeroClient->loadByGUID($model, $guid);
        }

        foreach ($parameters as $parameter => $value) {
            $request->{'set' . $parameter}($value);
            $request->setDirty($parameter);
        }

        foreach ($objects as $object => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $request->{'add' . $key}($item);
                }
            } else {
                $request->{'add' . $object}($value);
            }

            $request->setDirty($object);
        }

        $request->save();

        return $request;
    }
}
