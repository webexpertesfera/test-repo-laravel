<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\ImageUploader;
use App\Http\Repositories\Pages\PageRepository;
use App\Http\Requests\Pages\CreatePageRequest;
use App\Http\Requests\Pages\UpdatePageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PageController extends Controller
{
    public $page;

    public $imageUploader;

    public function __construct(PageRepository $page, ImageUploader $imageUploader)
    {
        $this->page = $page;

        $this->imageUploader = $imageUploader;
    }

    public function getAllPaginated()
    {
        $pages = $this->page->getAllPaginated();

        return PageResource::collection($pages);
    }

    public function getById($id)
    {
        $page = $this->page->getById($id);

        return PageResource::make($page);
    }

    public function getBySlug($slug)
    {
        $page = $this->page->getBySlug($slug);

        return PageResource::make($page);
    }

    public function create(CreatePageRequest $request)
    {
        $data = $request->getAndFormatData();

        if($request->hasFile('image'))
        {
            $imageName = time().'_-_'.$request->file('image')->getClientOriginalName();
            
            $folder = Page::IMAGEFOLDER;

            $this->imageUploader->uploadImage($folder, $request->file('image'), $imageName);
            
            $data['image'] = $imageName;
        }

        $page = $this->page->create($data);

        return PageResource::make($page);
    }

    public function update($id, UpdatePageRequest $request)
    {
        $data = $request->getAndFormatData();

        unset($data['image']);
        try{
            if(request()->hasFile('image'))
            {
                $existedImage = Page::find($id);

                if($existedImage->image != null)
                {
                    $imageName = time().'_-_'.request()->file('image')->getClientOriginalName();
        
                    $folder = Page::IMAGEFOLDER;
        
                    $this->imageUploader->removeAndUploadImage($folder, $request->file('image'), $existedImage->image, $imageName);
                    
                    $data['image'] = $imageName;
                }
            }
    
            $page = $this->page->update($id, $data);
    
            return PageResource::make($page);
        }
        catch(\Exception $e)
        {
            Log::info($e->getMessage());
            throw ValidationException::withMessages(["error" => $e->getMessage()]);
        }
    }

    public function updateStatus($id)
    {
        $pageStatus = $this->page->changeStatus($id);

        return PageResource::make($pageStatus);
    }

    public function delete($id)
    {
        $page = $this->page->delete($id);

        return PageResource::make($page);
    }

    public function states()
    {
        $json = File::json(base_path('/public/state.json'));
        return response()->json(["message"=> "Got states", "data" => $json]);
    }
}
