<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\ImageUploader;
use App\Http\Repositories\Businesses\BusinessRepository;
use App\Http\Requests\BusinessDocuments\ApprovalRequest;
use App\Http\Requests\BusinessDocuments\ReplaceBusinessDocumentRequest;
use App\Http\Requests\BusinessDocuments\UploadBusinessDocumentRequest;
use App\Http\Resources\BusinessDocumentResource;
use App\Models\CompanyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BusinessDocumentController extends Controller
{

    public $business;

    public $imageUploader;

    public function __construct(BusinessRepository $business, ImageUploader $imageUploader)
    {
        $this->business = $business;

        $this->imageUploader = $imageUploader;
    }

    public function getAllCompanyDocuments()
    {
        $documents = $this->business->getAllDocumentsPaginated();

        return BusinessDocumentResource::collection($documents);
    }

    public function getMyBusinessDocuments()
    {
        $documents = $this->business->getMyBusinessDocuments();
    
        return BusinessDocumentResource::collection($documents);
    }

    public function getDocumentById($id)
    {
        return  $this->business->getDocumentById($id);
    }
    
    public function uploadDocument(UploadBusinessDocumentRequest $request)
    {
        $data = $request->getAndFormatData();

        $documentFoler = CompanyDocument::COMPANY_DOCUMENT;
            
        //Registeration Certificate
        if(request()->hasFile('registeration_certificate'))
        {
            $registerationCertificateName = mt_rand(1, 9999999)."_document_-_".request()->file('registeration_certificate')->getClientOriginalName();

            $this->imageUploader->uploadImage($documentFoler, request()->file('registeration_certificate'), $registerationCertificateName);

            $data['registeration_certificate'] = $registerationCertificateName;
        }

        //Company License
        if(request()->hasFile('company_license'))
        { 
            $companyLicenseName = mt_rand(1, 9999999)."_document_-_".request()->file('company_license')->getClientOriginalName();

            $this->imageUploader->uploadImage($documentFoler, request()->file('company_license'), $companyLicenseName);

            $data['company_license'] = $companyLicenseName;
        }

        //Address Proof
        if(request()->hasFile('address_proof'))
        { 
            $address_proof = mt_rand(1, 9999999)."_document_-_".request()->file('address_proof')->getClientOriginalName();

            $this->imageUploader->uploadImage($documentFoler, request()->file('address_proof'), $address_proof);

            $data['address_proof'] = $address_proof;
        }

        //Identitication Document
        if(request()->hasFile('identification_document'))
        { 
            $identificationDocumentName = mt_rand(1, 9999999)."_document_-_".request()->file('identification_document')->getClientOriginalName();

            $this->imageUploader->uploadImage($documentFoler, request()->file('identification_document'), $identificationDocumentName);

            $data['identification_document'] = $identificationDocumentName;
        }

        $upload = $this->business->uploadVerificationDocument($data);

        return BusinessDocumentResource::make($upload);
    }

    public function replaceDocument( ReplaceBusinessDocumentRequest $request)
    {
        $data = $request->getAndFormatData();

        unset($data['registeration_certificate']);
        unset($data['company_license']);
        unset($data['address_proof']);
        unset($data['identification_document']);

        $actualDocument = CompanyDocument::where('company_id', Auth::id())->first();

        //Registeration Certificate
        if(request()->hasFile('registeration_certificate'))
        {
            $registerationCertificateName = mt_rand(1, 9999999)."_document_".request()->file('registeration_certificate')->getClientOriginalName();

            if($actualDocument->registeration_certificate != null)
            {
                $documentFolder = CompanyDocument::COMPANY_DOCUMENT;

                if(Storage::disk('public')->exists($documentFolder.$actualDocument->registeration_certificate))
                {
                    Storage::disk('public')->delete($documentFolder.$actualDocument->registeration_certificate);
                }

                Storage::disk('public')->putFileAs($documentFolder, request()->file('registeration_certificate'), $registerationCertificateName);

                //$this->imageUploader->removeAndUploadImage($documentFolder,request()->file('document_image'),$actualDocument->registeration_certificate,$registerationCertificateName);

                $data['registeration_certificate'] = $registerationCertificateName;
            }
        }

        //Company License
        if(request()->hasFile('company_license'))
        {
            $companyLicenseName = mt_rand(1, 9999999)."_document_".request()->file('company_license')->getClientOriginalName();

            if($actualDocument->company_license != null)
            {
                $documentFolder = CompanyDocument::COMPANY_DOCUMENT;

                if(Storage::disk('public')->exists($documentFolder.$actualDocument->company_license))
                {
                    Storage::disk('public')->delete($documentFolder.$actualDocument->company_license);
                }

                Storage::disk('public')->putFileAs($documentFolder, request()->file('company_license'), $companyLicenseName);

               // $this->imageUploader->removeAndUploadImage($documentFolder,request()->file('company_license'),$actualDocument->company_license,$companyLicenseName);

                $data['company_license'] = $companyLicenseName;
            }
        }

        //Address Proof
        if(request()->hasFile('address_proof'))
        {
            $addressProofName = mt_rand(1, 9999999)."_document_".request()->file('address_proof')->getClientOriginalName();

            if($actualDocument->address_proof != null)
            {
                $documentFolder = CompanyDocument::COMPANY_DOCUMENT;

                if(Storage::disk('public')->exists($documentFolder.$actualDocument->address_proof))
                {
                    Storage::disk('public')->delete($documentFolder.$actualDocument->address_proof);
                }

                Storage::disk('public')->putFileAs($documentFolder, request()->file('address_proof'), $addressProofName);

                //$this->imageUploader->removeAndUploadImage($documentFolder,request()->file('address_proof'),$actualDocument->address_proof,$addressProofName);

                $data['address_proof'] = $addressProofName;
            }
        }

        //Identitication Document
        if(request()->hasFile('identification_document'))
        {
            $identificationDocumentName = mt_rand(1, 9999999)."_document_".request()->file('identification_document')->getClientOriginalName();

            if($actualDocument->identification_document != null)
            {
                $documentFolder = CompanyDocument::COMPANY_DOCUMENT;

                if(Storage::disk('public')->exists($documentFolder.$actualDocument->identification_document))
                {
                    Storage::disk('public')->delete($documentFolder.$actualDocument->identification_document);
                }

                Storage::disk('public')->putFileAs($documentFolder, request()->file('identification_document'), $identificationDocumentName);

               // $this->imageUploader->removeAndUploadImage($documentFolder,request()->file('identification_document'),$actualDocument->identification_document,$identificationDocumentName);

                $data['identification_document'] = $identificationDocumentName;
            }
        }

        $replaceDocument = $this->business->replaceVerificationDocument($actualDocument->company_id, $data);

        return BusinessDocumentResource::make($replaceDocument);
    }


    public function Verification($id, ApprovalRequest $request)
    {
        $data = $request->getAndFormatData();

        $verification = $this->business->verificationRequest($id, $data);

        return BusinessDocumentResource::make($verification);
    }

    public function removeDocument($id)
    {
        $removeDocument = $this->business->removeDocument($id);

        return BusinessDocumentResource::make($removeDocument);
    }
}
