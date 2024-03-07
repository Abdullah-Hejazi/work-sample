<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Report;
use App\User;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReportResponse;

class AdminReports extends Controller{
    public function index() {
        return Report::with('user:id,full_name,email')
            ->with('response.user:id,full_name')
            ->with('encyclopedia:id,name')
            ->with('law:id,law_number')
            ->orderBy('created_at', 'desc')
            ->paginate(5);
    }

    public function reply(Request $request, Report $report) {
        $request->validate([
            'body'    =>  'required'
        ]);

        if (! $report->user_id && ! $report->email) {
            return response()->json(
                new MessageResource([
                    'title'     =>      'حدث خطأ أثناء الرد',
                    'status'    =>      'error'
                ]),
                450
            );
        }

        $email = $report->user_id ? User::findOrFail($report->user_id)->email : $report->email;

        Mail::to($email)->queue(new ReportResponse($request->body));

        $report->response()->create([
            'user_id'       =>      $request->user()->id,
            'text'          =>      $request->body
        ]);

        return response()->json(
            new MessageResource([
                'title'     =>      'تم إرسال الرد بنجاح',
                'status'    =>      'success'
            ])
        );
    }
}
