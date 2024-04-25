<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Attributes\AttributeRepository;
use App\Http\Requests\Attributes\CreateAttributeRequest;
use App\Http\Requests\Attributes\UpdateAttributeRequest;
use App\Http\Resources\AttributeResource;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public $attributes;

    public function __construct(AttributeRepository $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAll()
    {
        $attributes = $this->attributes->getAll();

        return AttributeResource::collection($attributes);
    }

    public function getAllPaginated()
    {
        $attributes = $this->attributes->getAllPaginated();

        return AttributeResource::collection($attributes);
    }

    public function getById($id)
    {
        $attribute = $this->attributes->getById($id);
        
        return AttributeResource::make($attribute);
    }

    public function create(CreateAttributeRequest $request)
    {
        $data = $request->getAndFormatData();

        $attribute = $this->attributes->create($data);

        return AttributeResource::make($attribute);
    }

    public function update($id, UpdateAttributeRequest $request)
    {
        $data = $request->getAndFormatData();

        $attribute = $this->attributes->update($id, $data);

        return AttributeResource::make($attribute);
    }

    public function delete($id)
    {
        $attribute = $this->attributes->delete($id);
        
        return AttributeResource::make($attribute);
    }
    
}
