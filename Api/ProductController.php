<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Products\ProductRepository;
use App\Http\Resources\ProductResource;
use App\Models\BrandProduct;
use App\Models\CategoryProduct;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductWarehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class ProductController extends Controller
{

    public $product;

    public function __construct(ProductRepository $product)
    {
        $this->product = $product;
    }

    public function index(Request $request)
    {
        $products = $this->product->getAllPaginated();

        return ProductResource::collection($products);
        // try {
        //     $perPage = $request->input('limit', 10);
        //     $page = $request->input('page', 1);
        //     $searchTerm = $request->input('search');
        //     $brandIds = $request->input('brand_id');
        //     $categoryIds = $request->input('category_id');
        //     $warehouseIds = $request->input('warehouse_id');
        //     $fromDate = $request->input('from');
        //     $toDate = $request->input('to');
        //     $publishedStatus = $request->input('publish_status'); // Assuming it's a boolean value (true/false)
        //     $query = Product::with('user');
        //     if (!Auth::user()->hasRole('admin')) {
        //         $query->where('user_id', Auth::id());
        //     }
        //     if ($searchTerm !== null) {
        //         $query->where('name', 'ILIKE', '%' . $searchTerm . '%');
        //     }
        //     if ($brandIds !== null) {
        //         $query->whereHas('brands', function ($q) use ($brandIds) {
        //             $q->whereIn('brand_id', $brandIds);
        //         });
        //     }
        //     if ($categoryIds !== null) {
        //         $query->whereHas('categories', function ($q) use ($categoryIds) {
        //             $q->whereIn('category_id', $categoryIds);
        //         });
        //     }
        //     if ($warehouseIds !== null) {
        //         $query->whereHas('warehouses', function ($q) use ($warehouseIds) {
        //             $q->whereIn('warehouse_id', $warehouseIds);
        //         });
        //     }
        //     if ($fromDate !== null && $toDate !== null) {
        //         $query->whereBetween('created_at', [$fromDate, $toDate]);
        //     }
        //     if ($fromDate !== null && $toDate == null) {
        //         $query->where('created_at', $fromDate);
        //     }
        //     if ($fromDate == null && $toDate !== null) {
        //         $query->where('created_at', $toDate);
        //     }
        //     if ($publishedStatus !== null) {
        //         $query->where('publish_status', $publishedStatus);
        //     }
        //     $query->orderBy('created_at', 'desc');
        //     $products = $query->paginate($perPage, ['*'], 'page', $page);
        //     if ($page > $products->lastPage()) {
        //         return response()->json(['message' => 'Requested page does not exist', 'data' => []], 200);
        //     }
        //     if ($products->isEmpty()) {
        //         return response()->json(['message' => "No Product Found", 'data' => [
        //             "products" => $products->items(),
        //             'count' => $products->total(),
        //             'rowsPerPage' => $products->perPage(),
        //             'currentPage' => $products->currentPage()
        //         ]], 200);
        //     }
        //     return response()->json(['message' => "Products fetched successfully", 'data' => [
        //         "products" => $products->items(),
        //         'count' => $products->total(),
        //         'rowsPerPage' => $products->perPage(),
        //         'currentPage' => $products->currentPage()
        //     ]], 200);
        // } catch (\Exception $e) {
        //     return response()->json(['message' => $e->getMessage()], 500);
        // }
    }

    public function getPaginatedForAdmin()
    {
        $products = $this->product->getPaginatedProductsForAdmin();

        return ProductResource::collection($products);
    }
 
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug',
            'description' => 'required|string|max:150',
            'sku' => 'required|string|unique:products,sku',
            'price' => 'required|numeric',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'nullable|integer|exists:brands,id',
            'warehouse_stock' => 'required|array',
            'warehouse_stock.*.warehouse_id' => 'required|numeric|exists:warehouses,id',
            'warehouse_stock.*.stock' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try
        {
            $product = new Product();
            $product->name = $request->input('name');
            $product->slug = Str::slug($request->input('slug'));
            $product->description = $request->input('description');
            $product->sku = $request->input('sku');
            $product->price = $request->input('price');
            // Company Id
            if(Auth::user()->hasRole(["manufacturer", "seller", "retailer"]))
            {
                $product->user_id = Auth::id();
            }
            elseif (Auth::user()->created_by != null && !empty(Auth::user()->createdBy))
            {
                $product->user_id = Auth::user()->created_by;
            }
            //$product->user_id = Auth::id();   //company Id
            $product->image = $request->input('image');
            $product->gallery = implode(',', $request->input('gallery'));
            $product->save();

            // Attach categories, brands to the product
            $product->categories()->attach($request->input('category_ids'));
            $product->brands()->attach($request->input('brand_ids'));

            // Save warehouse stock for the product
            foreach ($request->input('warehouse_stock') as $warehouseStock)
            {
                ProductWarehouse::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseStock['warehouse_id'],
                    'stock' => $warehouseStock['stock'],
                ]);
            }
            return response()->json(['message' => 'Product created successfully', 'data' => $product], 201);
        }
        catch (\Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    public function show($id)
    {
        try {
            $product = Product::with('warehouses','categories','brands')->findOrFail($id);
            return response()->json(['message' => 'Product fetched successfully', 'data' => $product], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string',
                'description' => 'nullable|string|max:150',
                'sku' => 'nullable|string',
                'price' => 'nullable|numeric',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'integer|exists:categories,id',
                'brand_ids' => 'nullable|array',
                'brand_ids.*' => 'nullable|integer|exists:brands,id',
                'warehouse_stock' => 'nullable|array',
                'warehouse_stock.*.warehouse_id' => 'nullable|integer|exists:warehouses,id',
                'warehouse_stock.*.stock' => 'nullable|integer|min:0',
                'image' => 'nullable|string',
                'gallery' => 'nullable|array',
                'gallery.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $product = Product::findOrFail($id);
            $product->name = $request->input('name');
            $product->slug = $request->input('slug');
            $product->description = $request->input('description');
            $product->sku = $request->input('sku');
            $product->price = $request->input('price');
            //$product->user_id = Auth::id();
            $product->image = $request->input('image');
            $product->gallery = implode(',', $request->input('gallery'));
            $product->save();

            // Sync categories and brands with the product
            //$product->categories()->sync($request->input('category_ids'));
           // $product->brands()->sync($request->input('brand_ids'));

           // Brand Assigned
           if($request->has('brand_ids') && !empty($request->brand_ids)){
                BrandProduct::where('product_id', $id)->delete();
                foreach($request->brand_ids as $brand){
                 //   DB::table('brand_product')->insert(['product_id', $id, 'brand_id'=> $brand]);
                    BrandProduct::create(['product_id'=> $id, 'brand_id'=> $brand]);
                }
           }
           
           if($request->has('category_ids') && !empty($request->category_ids)){
                CategoryProduct::where('product_id', $id)->delete();
                foreach($request->category_ids as $category){
                CategoryProduct::create(['product_id' => $id, 'category_id' => $category]);
                }
           }

            // Update warehouse stock for the product
            ProductWarehouse::where('product_id', $id)->delete(); // Delete existing warehouse stock records for the product
            foreach ($request->input('warehouse_stock') as $warehouseStock) {
                ProductWarehouse::create([
                    'product_id' => $id,
                    'warehouse_id' => $warehouseStock['warehouse_id'],
                    'stock' => $warehouseStock['stock'],
                ]);
            }

            return response()->json(['message' => 'Product updated successfully', 'data' => $product], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function changeStatus($id)
    {
        $product = Product::find($id);

        if($product->visibility_status == 0){
            $product->update(['visibility_status' => 1]);
        }else{
            $product->update(['visibility_status' => 0]);
        }

        return ProductResource::make($product); 
    }

    public function destroy($id)
    {
        $product = $this->product->destroy($id);

        return ProductResource::make($product);
        // try {
        //     $Product = Product::findOrFail($id);
        //     $Product->delete();
        //     return response()->json(['message' => 'Product deleted successfully'], 200);
        // } catch (ModelNotFoundException $e) {
        //     return response()->json(['message' => 'Product not found'], 404);
        // } catch (\Exception $e) {
        //     return response()->json(['message' => $e->getMessage()], 500);
        // }
    }

    public function getAllProducts(Request $request){
        $categories = Product::where('publish_status','accepted')->get();
        return response()->json(['data' => $categories], 200);
    }

}
