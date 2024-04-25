<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Orders\OrderRepository;
use App\Http\Requests\Orders\CreateOrderNoteRequest;
use App\Http\Requests\Orders\CreateOrderRequest;
use App\Http\Requests\Orders\UpdateOrderNoteRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Requests\Orders\UpdaterOrderActivityRequest;
use App\Http\Resources\OrderNoteResource;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public $order;

    public function __construct(OrderRepository $order)
    {
        $this->order = $order;
    }

    public function getAllPaginated()
    {
        $orders = $this->order->getAllPaginated();
        
        return OrderResource::collection($orders);
    }

    public function getCompanyOrdersPaginated()
    {
        $orders = $this->order->getCompanyOrdersPaginated();

        return OrderResource::collection($orders);
    } 

    public function getCompanySalesPaginated()
    {
        $sales = $this->order->getCompanySalesPaginated();

        return OrderResource::collection($sales);
    }

    public function getMyOrdersPaginated()
    {
        $orders = $this->order->getAllMyPaginated();

        return OrderResource::collection($orders);
    }

    public function getById($id)
    {
        $order = $this->order->getById($id);

        return OrderResource::make($order);
    }

    public function getByTrackId($id)
    {
        $order = $this->order->getByTrackId($id);
        
        return OrderResource::make($order);
    }

    public function createOrder(CreateOrderRequest $request)
    {
        $data = $request->getAndFormatData();

        $order = $this->order->create($data);

        return OrderResource::make($order);
    }

    public function updateOrder($id, UpdateOrderRequest $request)
    {
        $data = $request->getAndFormatData();

        $order = $this->order->update($id, $data);

        return OrderResource::make($order);
    }

    public function updateActivity($id, UpdaterOrderActivityRequest $request)
    {
        $data = $request->getAndFormatData();

        $order = $this->order->update($id, $data);

        return OrderResource::make($order);
    }

    public function destroy($id)
    {
        $order = $this->order->destroy($id);

        return OrderResource::make($order);
    }

    /**
     * 
     * Order Notes
     */

    public function createNote(CreateOrderNoteRequest $request)
    {
        $data = $request->getAndFormatData();

        $createOrderNote = $this->order->createOrderNote($data);

        return OrderNoteResource::make($createOrderNote);
    }

    public function getNoteById($id)
    {
        $orderNote = $this->order->getNoteById($id);

        return OrderNoteResource::make($orderNote);
    }

    public function updateNote($id, UpdateOrderNoteRequest $request)
    {
        $data = $request->getAndFormatData();

        $updateOrderNote = $this->order->updateOrderNote($id, $data);

        return OrderNoteResource::make($updateOrderNote);
    }

    public function deleteNote($id)
    {
        $deleteNote = $this->order->deleteOrderNote($id);

        return OrderNoteResource::make($deleteNote);
    }
}
