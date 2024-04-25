<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\User\UserAddresses\UserAddressesRepository;
use App\Http\Requests\Users\UserAddresses\CreateAnyUserAddressRequest;
use App\Http\Requests\Users\UserAddresses\CreateUserAddressRequest;
use App\Http\Requests\Users\UserAddresses\UpdateUserAddressRequest;
use App\Http\Resources\UserAddressesResource;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public $userAddress;

    public function __construct(UserAddressesRepository $userAddress)
    {
        $this->userAddress = $userAddress;
    }

    public function getMyAddresses()
    {
        $addresses = $this->userAddress->getMyAddresses();

        return UserAddressesResource::collection($addresses);
    }

    public function getUserAddresses($id)
    {
        $addresses = $this->userAddress->getUserAddresses($id);

        return UserAddressesResource::collection($addresses);
    }

    public function getById($id)
    {
        $address = $this->userAddress->getById($id);

        return UserAddressesResource::make($address);
    }

    public function create(CreateUserAddressRequest $request)
    {
        $data = $request->getAndFormatData();

        $userAddress = $this->userAddress->create($data);

        return UserAddressesResource::make($userAddress);
    }

    public function createAddressForAnyUser(CreateAnyUserAddressRequest $request)
    {
        $data = $request->getAndFormatData();

        $userAddress = $this->userAddress->create($data);

        return UserAddressesResource::make($userAddress);
    }

    public function update($id, UpdateUserAddressRequest $request)
    {
        $data = $request->getAndFormatData();

        $userAddress = $this->userAddress->update($id, $data);

        return UserAddressesResource::make($userAddress);
    }

    public function changeStatus($userId, $id)
    {
        $changeStatus = $this->userAddress
                        ->changeStatus(intval($userId), intval($id));

        return response()->json(['data' => $changeStatus]);
    }

    public function delete($id)
    {
        $userAddress = $this->userAddress->delete($id);

        return UserAddressesResource::make($userAddress);
    }
}
