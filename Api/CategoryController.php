<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Categories\CategoryRepository;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Brand;
use App\Models\CategoryProduct;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ResourceStatusUpdated;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{

    public $category;

    public function __construct(CategoryRepository $category)
    {
        $this->category = $category;
    }


    public function index(Request $request)
    {
        try {
            $perPage = $request->input('limit', 10);
            $page = $request->input('page', 1);

            $searchTerm = $request->input('search');
            $publicStatus = $request->input('publish_status'); 

            $query = $this->category->getAllCategories();

            $query = $query->select('categories.*');

            $categories = $query->paginate($perPage, ['*'], 'page', $page);

            if ($page > $categories->lastPage())
            {
                return response()
                ->json([
                    'message' => 'Requested page does not exist', 
                    'data' => []
                    ], 200);
            }

            // Check if there are any categories available
            if ($categories->isEmpty())
            {
                return response()
                ->json([
                'message' => "No Category Found ", 'data' => [
                "categories" => $categories->items(),
                'count' => $categories->total(),
                'rowsPerPage' => $categories->perPage(),
                'currentPage' => $categories->currentPage()
                ]], 200);
            }

            return response()
                ->json([
                'message' => "Categories fetched successfully",
                'data' => [
                    "categories" => $categories->items(),
                    'count' => $categories->total(),
                    'rowsPerPage' => $categories->perPage(),
                    'currentPage' => $categories->currentPage()
                    ]
                ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getIndependentCategories()
    {
        $categories = $this->category->getIndependentCategories();

        return CategoryResource::collection($categories);
    }

    public function testIndex()
    {
        $categories = $this->category->getAllPaginated();

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
            'description' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            // Add the authenticated user's ID to the request data
            $requestData = $request->all();

            // Company Id
            if(Auth::user()->hasRole(["manufacturer", "seller", "retailer"]))
            {
                $requestData['user_id'] = Auth::id();
                $requestData['user_type'] = Auth::user()->roles[0]->name;
            }
            elseif (Auth::user()->created_by != null && !empty(Auth::user()->createdBy))
            {
                $requestData['user_id'] = Auth::user()->created_by;
                $requestData['user_type'] = User::find(Auth::user()->created_by)->roles[0]->name ?? null;
            }else
            {
                $requestData['user_type'] = "admin";
            }
           // $requestData['user_id'] = Auth::id(); // or auth()->id()
            
            $category = Category::create($requestData);
            
            return response()->json(['message' => 'Category stored successfully', 'data' => $category], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json(['message' => 'Category fetched successfully', 'data' => $category], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
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
            $category = Category::findOrFail($id);
            $category->update($request->all());
            return response()->json(['message' => 'Category updated successfully', 'data' => $category], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);

            $checkInUseExists = CategoryProduct::where('category_id', $id)->exists();

            if($checkInUseExists){
                throw ValidationException::withMessages(["error" => "Category in already in-use."]);
            }
            
            $category->delete();
            return response()->json(['message' => 'Category deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updatePublishStatus($type, $id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:requested,accepted,rejected',
        ]);

        $model = null;
        if ($type === 'brand') {
            $model = Brand::find($id);
        } elseif ($type === 'category') {
            $model = Category::find($id);
        } elseif ($type === 'product') {
            $model = Product::find($id);
        } elseif ($type === "warehouse") {
            $model = Warehouse::find($id);
        }

        if (!$model) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        $model->publish_status = $request->status;
        $model->save();
        $user = $model->user; // Assuming the user is associated with the resource
        if ($user) {
            $user->notify(new ResourceStatusUpdated($type,$request->status));
        }

        // Return a success response
        return response()->json(['message' => ucfirst($type) . ' status updated successfully'], 200);
    }
      
    public function getAllCategories(Request $request){
        $categories = Category::where('publish_status','accepted')->get();
        return response()->json(['message' => 'categories feached successfully', 'data' =>["categories"=>$categories]], 200);
    }

}
