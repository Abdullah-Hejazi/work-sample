<?php

namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;

use App\User;
use App\Report;
use App\Encyclopedia;
use App\Law;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminRecieveReportNotification;

class ReportController extends Controller {
    public function lawreport(Request $request, Encyclopedia $encyclopedia, Law $law){
        $request->validate([
            'title'     =>  'required|string',
            'text'     =>  'required|string',
            'type'     =>  'required|string'
        ]);

        $types = ['general', 'suggestion', 'error', 'support', 'correction', 'note', 'thanking', 'other'];

        $report = new Report([
            'type'                  =>  in_array($request->type, $types) ? $request->type : 'note',
            'title'                 =>  $request->title,
            'text'                  =>  $request->text,
            'encyclopedia_id'       =>  $encyclopedia->id,
            'law_id'                =>  $law->id,
            'user_id'               =>  $request->user()->id
        ]);
        $report->save();

        Notification::send(User::where('role', 1)->where('employee', true)->get(), new AdminRecieveReportNotification('رسالة من أحد العملاء'));

        return response()->json([
            'status'        =>      'success',
            'data'          =>      [
                'message'       =>      [
                    'title'     =>      'تم إرسال الرسالة بنجاح',
                    'text'      =>      'سيتم الرد على رسالتكم من قبل فريق عمل شركة الراية في أقرب وقت ممكن . علما بأن الرد سيصل لحضراتكم عبر البريد الإلكتروني المرفق بالحساب.',
                    'icon'      =>      'success',
                    'button'    =>  [
                        'text'          =>  __('general.okay'),
                        'closeModal'    =>  true
                    ]
                ]
            ]
        ], 200);
    }

    public function report(Request $request){
        $request->validate([
            'title'     =>  'required|string',
            'text'     =>  'required|string',
            'type'     =>  'required|string'
        ]);

        $types = ['general', 'suggestion', 'error', 'support', 'correction', 'note', 'thanking', 'other'];

        $report = new Report([
            'type'     =>  in_array($request->type, $types) ? $request->type : 'support',
            'title'    =>  $request->title,
            'text'     =>  $request->text,
            'user_id'  =>  $request->user()->id
        ]);
        $report->save();

        Notification::send(User::where('role', 1)->where('employee', true)->get(), new AdminRecieveReportNotification('رسالة من أحد العملاء'));

        return response()->json([
            'status'        =>      'success',
            'data'          =>      [
                'message'       =>      [
                    'title'     =>      'تم إرسال الرسالة بنجاح',
                    'text'      =>      'سيتم الرد على رسالتكم من قبل فريق عمل شركة الراية في أقرب وقت ممكن . علما بأن الرد سيصل لحضراتكم عبر البريد الإلكتروني المرفق بالحساب.',
                    'icon'      =>      'success',
                    'button'    =>  [
                        'text'          =>  __('general.okay'),
                        'closeModal'    =>  true
                    ]
                ]
            ]
        ], 200);
    }
}
