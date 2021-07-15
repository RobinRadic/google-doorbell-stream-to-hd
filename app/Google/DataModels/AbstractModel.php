<?php

namespace App\Google\DataModels;

class AbstractModel
{
    private array $data;

    public function hydrate(array $data)
    {
        $this->data = $data;
        foreach($data as $key => $value){
            $this->{$key} = $value;
        }
        return $this;
    }

    public function getOriginalData()
    {
        return $this->data;
    }
}
