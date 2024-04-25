<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Brands\BrandRepository;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\BrandProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

/**
 * Class BrandController
 * @package App\Http\Controllers\Api
 */
class BrandController extends Controller
{

    public $brands;

    public function __construct(BrandRepository $brands)
    {
        $this->brands = $brands;
    }
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('limit', 10);
            $page = $request->input('page', 1);
            
            $searchTerm = $request->input('search');
            $publicStatus = $request->input('publish_status'); 

            $query =  $this->brands->getAll();
            $query = $query->select('brands.*');

            $categories = $query->paginate($perPage, ['*'], 'page', $page);

            if ($page > $categories->lastPage()) {
                return response()->json(['message' => 'Requested page does not exist', 'data' => []], 200);
            }

            if ($categories->isEmpty()) {
                return response()->json(['message' => "No Brand Found", 'data' =>['brands'=>$categories->items(), 'count' => $categories->total(), 'rowsPerPage' => $categories->perPage(), 'currentPage' => $categories->currentPage()]], 200);
            }

            return response()->json(['message' => "Brands fetched successfully", 'data' =>['brands'=>$categories->items(), 'count' => $categories->total(), 'rowsPerPage' => $categories->perPage(), 'currentPage' => $categories->currentPage()]], 200);
        } 
        catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|string',
            'description' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $requestData = $request->all();

            if(Auth::user()->hasRole(["manufacturer", "seller", "retailer"]))
            {
                $requestData['user_id']=  Auth::id();
                $requestData['user_type'] = Auth::user()->roles[0]->name;
            }
            elseif (Auth::user()->created_by != null)
            {
                $requestData['user_id']= Auth::user()->created_by;
                $requestData['user_type'] = User::find(Auth::user()->createdBy)->roles[0]->name;
            }

            $data['slug'] = str::slug($requestData['name']);

            $brand = Brand::create($requestData);
            return response()->json(['message' => 'Brand stored successfully', 'data' => $brand], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return response()->json(['message'=>'Brand fetched successfully','data' => $brand], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Brand not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|string',
            'description' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $brand = Brand::findOrFail($id);
            $brand->update($request->all());
            return response()->json(['message' => 'Brand updated successfully', 'data' => $brand], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Brand not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Brand::findOrFail($id);

            $checkInUseExists = BrandProduct::where('brand_id', $id)->exists();

            if($checkInUseExists)
            {
                throw ValidationException::withMessages(["error" => "Brand is already in-use."]);
            }

            $category->delete();
            return response()->json(['message' => 'Brand deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Brand not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
     public function getAllBrands(Request $request){
        $brands = Brand::where('publish_status','accepted')->get();
        return response()->json(['message' => 'Brand feached successfully', 'data' =>["brands"=>$brands]], 200);
    }
   
}
