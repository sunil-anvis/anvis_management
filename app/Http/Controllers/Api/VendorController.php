<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class VendorController extends Controller
{
    public static function middleware():array{
        return [
            new Middleware('permissions:view-vendor', only:['index','show']),
            new Middleware('permissions:create-vendor', only:['store']),
            new Middleware('permissions:edit-vendor', only:['update']),
            new Middleware('permissions:delete-vendor', only:['destroy']),
        ];
    }

    public function index()
    {
        $vendor = Vendor::latest()->get();

        return response()->json([
            'success'=>True,
            'message'=>'Successfully fetched',
            'data'=>$vendor
        ],200);
    }

    public function store(Request $request)
    {
        $valid = Validator::make($request->all(),[
            'vendor_name'=> 'nullable|string',
            'vendor_address'=>'nullable|string',
            'vendor_gst_no'=>'nullable|string',
            'vendor_contact'=>'nullable|string',
            'vendor_email'=>'nullable|email'
        ]);

        if($valid->fails()){
            return response()->json([
                'success'=>false,
                'message'=>'Validation error',
                'error'=>$valid->errors()
            ],422);
        }

        $data = $valid->validated();

        $vendor = Vendor::create([
            'vendor_name'=> $data['vendor_name'] ?? null,
            'vendor_address'=> $data['vendor_address'] ?? null,
            'vendor_gst_no'=> $data['vendor_gst_no'] ?? null,
            'vendor_contact'=> $data['vendor_contact'] ?? null,
            'vendor_email'=> $data['vendor_email'] ?? null
        ]);

        return response()->json([
            'success'=>true,
            'message'=>'Vendor created successfully',
            'data'=>$vendor
        ],201);
    }

    public function show($id)
    {
        $vendor = Vendor::find($id);

        if(!$vendor){
            return response()->json([
                'success'=>False,
                'message'=>'Vendor not found'
            ],404);
        }

        return response()->json([
            'success'=>True,
            'message'=>'Successfully fetched',
            'data'=>$vendor
        ],200);
    }


    public function update(Request $request, $id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        $valid = Validator::make($request->all(),[
            'vendor_name'=> 'nullable|string',
            'vendor_address'=>'nullable|string',
            'vendor_gst_no'=>'nullable|string',
            'vendor_contact'=>'nullable|string',
            'vendor_email'=>'nullable|email'
        ]);

        if ($valid->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $valid->errors()
            ], 422);
        }

        $data = $valid->Validated();

        $vendor->update($data);

        return response()->json([
            'success'=>True,
            'message'=>'Data update successfully',
            'data'=>$vendor->fresh()
        ],200);
    }

    public function destroy($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted successfully'
        ], 200);
    }
}
