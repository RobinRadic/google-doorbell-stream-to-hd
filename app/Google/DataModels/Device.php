<?php

namespace App\Google\DataModels;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use IteratorAggregate;
use JsonSerializable;

class Device extends AbstractModel implements Arrayable, ArrayAccess, JsonSerializable, Jsonable
{
    const TRAIT_PREFIX = 'smd.devices.traits.';
    public const DOORBELL_TYPE='sdm.devices.types.DOORBELL';

    protected string $assignee;

    protected string $name;

    protected array $parentRelations = [];

    protected array $traits = [];

    protected string $type;

    protected string $projectId;

    protected string $deviceId;

    protected string $deviceType;

    protected string $structureId;

    protected string $roomId;

    protected string $roomName;

    public function __construct(array $data)
    {
        $this->hydrate($data);
        [ $_, $this->projectId, $_, $this->deviceId ] = explode('/', $this->name);
        $this->deviceType = last(explode('.', $this->name));
        [ $_, $this->projectId, $_, $this->structureId, $_, $this->roomId ] = explode('/', $this->assignee);
        $this->roomName = $this->parentRelations[ 0 ][ 'displayName' ];
    }

    protected function traitName(string &$name)
    {
        if ( ! Str::startsWith($name, static::TRAIT_PREFIX)) {
            $name = static::TRAIT_PREFIX . $name;
        }
    }

    public function hasTrait(string $name)
    {
        $this->traitName($name);
        return isset($this->traits[ $name ]);
    }

    public function getTrait(string $name)
    {
        $this->traitName($name);
        return isset($this->traits[ $name ]);
    }

    public function getAssignee()
    {
        return $this->assignee;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParentRelations()
    {
        return $this->parentRelations;
    }

    public function getTraits()
    {
        return $this->traits;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function getDeviceType()
    {
        return $this->deviceType;
    }

    public function getStructureId()
    {
        return $this->structureId;
    }

    public function getRoomId()
    {
        return $this->roomId;
    }

    public function getRoomName()
    {
        return $this->roomName;
    }

    public function toArray()
    {
        return [
            'assignee'        => $this->assignee,
            'name'            => $this->name,
            'parentRelations' => $this->parentRelations,
            'traits'          => $this->traits,
            'type'            => $this->type,
            'projectId'       => $this->projectId,
            'deviceId'        => $this->deviceId,
            'deviceType'      => $this->deviceType,
            'structureId'     => $this->structureId,
            'roomId'          => $this->roomId,
            'roomName'        => $this->roomName,
        ];
    }

    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }

    public function jsonSerialize()
    {
        return $this->getOriginalData();
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR | $options);
    }
}
