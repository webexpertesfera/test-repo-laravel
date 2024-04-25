<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Warehouses\WarehouseRepository;
use App\Http\Requests\Warehouses\CreateWarehouseRequest;
use App\Http\Requests\Warehouses\UpdateWarehouseRequest;
use App\Models\ProductWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{

    public $warehouses;

    public function __construct(WarehouseRepository $warehouses)
    {
        $this->warehouses = $warehouses;
    }
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('limit', 10);
            $page = $request->input('page', 1);
            // $searchTerm = $request->input('search');
            // $searchLocation = $request->input('location');
            // $searchStatus = $request->input('status');
            // $publishStatus = $request->input('publish_status');
            // $userType = $request->input('user_type');
            // $query = Warehouse::orderBy('warehouses.id', 'desc');
            // if(Auth::user()->hasRole(["manufacturer", "seller", "retailer"]))
            // {
            //     $query->where('warehouses.user_id', Auth::id())->orWhereNull('user_id');
            // }
            // elseif (Auth::user()->created_by != null && !empty(Auth::user()->createdBy))
            // {
            //     $query->where('user_id', Auth::user()->created_by)->orWhereNull('user_id');
            // }
            // if ($searchTerm !== null) {
            //     $query->where('warehouses.name', 'ilike', '%' . $searchTerm . '%')
            //     ->orWhere('warehouses.warehouse_number', 'ilike', '%'.$searchTerm.'%');
            // }
            // if ($searchLocation !== null) {
            //     $query->where('warehouses.address_1', 'ilike', '%' . $searchLocation . '%')
            //     ->orWhere('warehouses.address_2', 'ilike', '%' . $searchLocation . '%');
            // }
            // if ($searchStatus !== null) {
            //     $query->where('warehouses.status', $searchStatus);
            // }
            // if ($publishStatus !== null) {
            //     $query->where('warehouses.publish_status', $publishStatus);
            // }
            // if($userType !== null){
            //     $query->where('user_type', $userType);
            // }

            $query = $this->warehouses->getWarehouses();
        
            $query = $query->select('warehouses.*');

            $warehouses = $query->paginate($perPage, ['*'], 'page', $page);
            
            if ($page > $warehouses->lastPage()) {
                return response()->json(['message' => 'Requested page does not exist', 'data' => []], 200);
            }

            if ($warehouses->isEmpty()) {
                return response()->json(['message' => "Warehouse Not Found", 'data' =>['warehouses'=>$warehouses->items(), 'count' => $warehouses->total(), 'rowsPerPage' => $warehouses->perPage(), 'currentPage' => $warehouses->currentPage()]], 200);
            }

            return response()->json(['message' => "Warehouses fetched successfully", 'data' =>['warehouses'=>$warehouses->items(), 'count' => $warehouses->total(), 'rowsPerPage' => $warehouses->perPage(), 'currentPage' => $warehouses->currentPage()]], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getCompanyWarehouses()
    {
        $warehouses = $this->warehouses->getSpecificCompanyWarehouses();

        return response()->json(['data' => $warehouses]);
    }


    public function store(CreateWarehouseRequest $request)
    {
        $data = $request->getAndFormatData();
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:255',
        //     'display_name' => 'string|max:255',
        //     'user_type' => 'nullable|string|max:255',
        //     'person_name' => 'string|max:255',
        //     'address_1' => 'required|string|max:255',
        //     'address_2' => 'nullable|string|max:255',
        //     'city' => 'required|string|max:255',
        //     'state' => 'required|string|max:255',
        //     'country' => 'required|string|max:255',
        //     'zipcode' => 'required|string|max:255',
        //     'company_name' => 'nullable|exists:users,id',
        //     'image' => 'nullable|string', // Assuming image will be uploaded as base64 encoded string
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['message' => $validator->errors()->first()], 422);
        // }

        try {
            
            // Generate a six-digit random number
            // $warehouseNumber = '';
            // for ($i = 0; $i < 6; $i++) {
            //     $warehouseNumber .= mt_rand(0, 9); // Append a random number between 0 and 9
            // }
            // if(Auth::user()->hasRole(["manufacturer", "seller", "retailer"]))
            // {
            //     $userId = Auth::id();
            // }
            // elseif (Auth::user()->created_by != null && !empty(Auth::user()->createdBy))
            // {
            //     $userId = Auth::user()->created_by;
            // }else{
            //     $userId = $request->company_name;
            // }

            // // Merge the warehouse number into the request data
            // $requestData = array_merge($request->all(), ['warehouse_number' => $warehouseNumber,'user_id'=> $userId]);

            // Create the warehouse
            $warehouse = Warehouse::create($data);

            return response()->json(['message' => 'Warehouse stored successfully', 'data' => $warehouse], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update($id, UpdateWarehouseRequest $request)
    {
        $data = $request->getAndFormatData();
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:255',
        //     'display_name' => 'string|max:255',
        //     'user_type' => 'nullable|string',
        //     'person_name' => 'string|max:255',
        //     'person_contact' => 'string|max:255',
        //     'address_1' => 'required|string|max:255',
        //     'address_2' => 'nullable|string|max:255',
        //     'city' => 'required|string|max:255',
        //     'state' => 'required|string|max:255',
        //     'country' => 'required|string|max:255',
        //     'zipcode' => 'required|string|max:255',
        //     'image' => 'nullable|string',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['message' => $validator->errors()->first()], 422);
        // }

        try {
            $warehouse = Warehouse::findOrFail($id);

            $warehouse->update($data);
            return response()->json(['message' => 'Warehouse updated successfully', 'data' => $warehouse], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Warehouse not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            
            $checkEntryExists = ProductWarehouse::where("warehouse_id", $id)->exists();

            if($checkEntryExists){
                throw ValidationException::withMessages(["error" => "Warehouse is already in-use."]);
            }

            $warehouse->delete();
            return response()->json(['message' => 'Warehouse deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Warehouse not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function show($id){
        try {
            // Attempt to find a warehouse by its ID
            $warehouse = Warehouse::with('user')->findOrFail($id);
            return response()->json(['message'=>'warehouse fetch successfully','data' => $warehouse], 200);
            
        } catch (ModelNotFoundException $e) {
                return response()->json(['message' => 'Warehouse not found'], 404);
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function changeStatus($id){
        try {
            // Attempt to find a warehouse by its ID
            $warehouse = Warehouse::findOrFail($id);
            $warehouse->status = !$warehouse->status;
            $warehouse->save();
            return response()->json(['message'=>'warehouse fetch successfully','data' => $warehouse], 200);
            
        } catch (ModelNotFoundException $e) {
                return response()->json(['message' => 'Warehouse not found'], 404);
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

}
