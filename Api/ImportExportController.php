<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\ImportExport\ImportExportRepository;
use App\Http\Requests\MasterService\Brands\ImportBrandRequest;
use App\Http\Requests\MasterService\Categories\ImportCategoryRequest;
use App\Http\Requests\MasterService\Products\ImportProductsRequest;
use Illuminate\Http\Request;

class ImportExportController extends Controller
{
    public $service;

    public function __construct(ImportExportRepository $service)
    {
        $this->service = $service;
    }

    public function exportBrands()
    {
        $this->service->exportBrands();
    }

    public function exportCategories()
    {
        $this->service->exportCategories();
    }

    public function exportProducts()
    {
        $this->service->exportProducts();
    }

    public function importBrands(ImportBrandRequest $request)
    {
        $data = $request->getAndFormatData();

        $importBrands = $this->service->importBrands($data);

        return response()
        ->json([ "message" => "Brand Sheet Uploaded Successfully.", "data" => $importBrands], 200);
    }

    public function importCategories(ImportCategoryRequest $request)
    {
        $data = $request->getAndFormatData();

        $importCategories = $this->service->importCategories($data);

        return response()
        ->json(["message" => "Category Sheet Uploaded Successfully.", "data" => $importCategories], 200);
    }

    public function importProducts(ImportProductsRequest $request)
    {
        $data = $request->getAndFormatData();

        $importProducts = $this->service->importProducts($data);

        return response()
        ->json(["message" => "Products Sheet Uploaded Successfully.","data" => $importProducts], 200);
    }
    
}
