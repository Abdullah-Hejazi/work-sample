<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Research;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MessageResource;

class AdminResearches extends Controller{
    public function index() {
        return Research::orderBy('created_at', 'desc')->paginate(10, ['id', 'title', 'created_at']);
    }

    public function get(Research $research) {
        return response()->json([
            'status'        =>      'success',
            'data'          =>      [
                'research'  =>  $research
            ]
        ]);
    }

    public function edit(Request $request, Research $research) {
        $validator = Validator::make($request->all(), [
            'title'         =>  'sometimes',
            'text'          =>  'sometimes',
            'normal_text'   =>  'sometimes',
            'remove_image'  =>  'required'
        ], [
            'required'              =>      __('auth.missing_field')
        ]);

        if($validator->fails()) {
            return response()->json(
                new MessageResource([
                    'title'     =>      $validator->errors()->first(),
                    'text'      =>      '',
                    'status'    =>      'error'
                ]),
                420
            );
        }

        $image = $research->image;

        if($request->remove_image == 'true') {
            $image = null;
        } else {
            if ($request->hasFile('image')) {
                $image = $request->file('image')->storePublicly('researches', 'public');
            }
        }

        $research->update([
            'title'         =>  $request->title,
            'text'          =>  $request->text,
            'normal_text'   =>  $request->normal_text,
            'image'         =>  $image
        ]);

        return response()->json(
            new MessageResource([
                'title'     =>      'تم حفظ البحث بنجاح',
                'text'      =>      '',
                'status'    =>      'success'
            ])
        );
    }

    public function new(Request $request) {
        $validator = Validator::make($request->all(), [
            'title'         =>  'required',
            'text'          =>  'required',
            'normal_text'   =>  'required'
        ], [
            'required'              =>      __('auth.missing_field')
        ]);

        if($validator->fails()) {
            return response()->json(
                new MessageResource([
                    'title'     =>      $validator->errors()->first(),
                    'text'      =>      '',
                    'status'    =>      'error'
                ]),
                420
            );
        }

        $image = null;
        if($request->hasFile('image')) {
            $image = $request->file('image')->storePublicly('researches');
        }

        $research = new Research($validator->validated());
        $research->image = $image;

        $research->save();

        return response()->json(
            new MessageResource([
                'title'     =>      'تم حفظ البحث بنجاح',
                'text'      =>      '',
                'status'    =>      'success'
            ])
        );
    }

    public function delete(Request $request, Research $research) {
        $research->delete();

        return response()->json(
            new MessageResource([
                'title'     =>      'تم حذف البحث بنجاح',
                'text'      =>      '',
                'status'    =>      'success'
            ])
        );
    }
}
