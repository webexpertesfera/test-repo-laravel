<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\NewProducts\ProductRepository;
use App\Http\Requests\Products\CreateNewProductRequest;
use App\Http\Requests\Products\ProductImageUploadRequest;
use App\Http\Requests\Products\UpdateNewProductRequest;
use App\Http\Requests\Products\VariationProductCreateRequest;
use App\Http\Resources\NewProductResource;
use App\Http\Resources\ProductImageResource;
use App\Http\Resources\ProductUsableAttributeResource;
use Illuminate\Http\Request;

class NewProductController extends Controller
{
    public $products;

    public function __construct(ProductRepository $products)
    {
        $this->products = $products;
    }

    public function getForAdminPaginated()
    {
        $products = $this->products->getForAdminPaginated();

        return ["data" => NewProductResource::collection($products)];
    }

    public function getProductsThroughCompany()
    {
        $products = $this->products->getProductsThroughCompanyPaginated();

        return response()->json($products);
    }

    public function buy()
    {
        $products = $this->products->buyProducts();
    }

    public function getShoppingProducts()
    {
        $products = $this->products->getShoppingProducts();

        return ["data" => NewProductResource::collection($products)];
    }

    public function getCompanyProducts()
    {
        $products = $this->products->getCompanyProducts();

        return ["data" =>  NewProductResource::collection($products)];
    }

    public function getThresholdProducts()
    {
        $products = $this->products->getThresholdProducts();

        return ["data" => NewProductResource::collection($products)];
    }

    public function getBySlug($slug)
    {
        $product = $this->products->getBySlug($slug);

        return ["data" => NewProductResource::make($product)];
    }

    public function getUsableAtrributes($id)
    {
        $productUsableAttributes = $this->products->getProductUsableAtrributes($id);

        return ["data" => ProductUsableAttributeResource::make($productUsableAttributes)];
    }

    public function getById($id)
    {
        $product = $this->products->getById($id);

        return["data" => NewProductResource::make($product)];
    }

    /**
     * @param mixed $request  Request Payload is sufficient to create simple product or
     * Bio-graphy with usable attributes for variational products.
     */
    public function create(CreateNewProductRequest $request)
    {
        $data = $request->getAndFormatData();

        $product = $this->products->create($data);

        return ["data" => NewProductResource::make($product)];
    }

    public function uploadImages(ProductImageUploadRequest $request)
    {
        $data = $request->getAndFormatData();

        $imageUpload = $this->products->uploadImages($data);

        return response()->json(["data"=> $imageUpload]);
    }

    public function changeStatus($id)
    {
        $product = $this->products->changeStatus($id);

        return ["data" => NewProductResource::make($product)];
    }

    /**
     * 
     * @param mixed $request  Its is responsible to create or update Variation Product Details with images.
     */
    public function variationProductUpsert($id, VariationProductCreateRequest $request)
    {
        $data = $request->getAndFormatData();

        $product = $this->products->VariationProductUpserter($id, $data);

        return ["data" => NewProductResource::make($product) ];
    }

    /**
     * @param mixed $request  Request payload is responsible to update simple 
     * product or Bio-graphy of variational product and their usable attributes.
     */
    public function conciseUpdate($id, UpdateNewProductRequest $request)
    {
        $data = $request->getAndFormatData();

        $product = $this->products->conciseUpdate($id, $data);

        return ["data" => NewProductResource::make($product)];
    }

    public function removeVariation($id)
    {
        $product = $this->products->removeVariation($id);

        return response()
                ->json([
                        'data' => $product, 
                        "message" => "Variation Product Removed", 
                        "status"=> 200
                    ]);
    }

    public function deleteImage($id)
    {
        $productImage = $this->products->deleteImage($id);
        
        return ["data" =>  ProductImageResource::make($productImage)];
    }

    public function delete($id)
    {
        $product = $this->products->delete($id);

        return ["data" => NewProductResource::make($product)];
    }
}
