<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\ImageUploader;
use App\Http\Repositories\Slides\SlideRepository;
use App\Http\Requests\Slides\CreateSlideRequest;
use App\Http\Requests\Slides\UpdateSlideRequest;
use App\Http\Resources\SlideResource;
use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SlideController extends Controller
{
    public $slide;

    public $imageUploader;

    public function __construct(SlideRepository $slide, ImageUploader $imageUploader)
    {
        $this->slide = $slide;
        $this->imageUploader = $imageUploader;
    }

    public function getAllPaginatedCollection()
    {
        $slides = $this->slide->getAllPaginatedCollection();

        return SlideResource::collection($slides);
    }

    public function getAll()
    {
        $slides = $this->slide->getAll();

        return SlideResource::collection($slides);
    }

    public function GetById($id)
    {
        $slide = $this->slide->getById($id);

        return SlideResource::make($slide);
    }

    public function create(CreateSlideRequest $request)
    {
        $data = $request->getAndFormatData();

        if($request->hasFile('image'))
        {
            $imageName = time()."_-_".$request->file('image')->getClientOriginalName();

            $folder = Slide::IMAGEFOLDER;

            $this->imageUploader->uploadImage($folder, $request->file('image'), $imageName);

            $data['image'] = $imageName;
        }

        $slide = $this->slide->create($data);

        return SlideResource::make($slide);
    }

    public function update($id, UpdateSlideRequest $request)
    {
        $data = $request->getAndFormatData();

        unset($data['image']);

       try
       {
            if(request()->hasFile('image'))
            {
                $folder = Slide::IMAGEFOLDER;

                $oldImageName = Slide::find($id)->image;

                if($oldImageName != null){

                    $imageName = request()->file('image')->getClientOriginalName();

                    $this->imageUploader->removeAndUploadImage($folder, $request->file('image'), $oldImageName, $imageName);
    
                    $data['image'] = $imageName;

                }else{
                    unset($data['image']);
                }

            }
            
           $slide = $this->slide->update($id, $data);

            return SlideResource::make($slide);
        }
        catch(\Exception $e)
        {
            Log::info($e->getMessage());

           throw ValidationException::withMessages(["error" => $e->getMessage()]);
        }
    }

    public function updateStatus($id)
    {
        $slideStatus = $this->slide->changeStatus($id);

        return SlideResource::make($slideStatus);
    }

    public function delete($id)
    {
        $slide = $this->slide->delete($id);

        return SlideResource::make($slide);
    }
}
