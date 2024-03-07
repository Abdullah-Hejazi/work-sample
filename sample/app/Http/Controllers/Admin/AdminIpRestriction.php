<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Str;
use App\Bundle;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationSuccessEmail;
use App\Models\IpRestriction;

class AdminIpRestriction extends Controller{
    public function index(User $user) {
        $ips = $user->ips()->get();

        return response()->json([
            'status'        =>      'success',
            'data'          =>      [
                'ips'  =>  $ips
            ]
        ]);
    }

    public function add(Request $request, User $user) {
        $request->validate([
            'ip' => 'required|ip'
        ]);

        $user->update([
            'ip_restricted'        =>      true
        ]);

        $user->ips()->create([
            'ip' => $request->ip
        ]);

        return response()->json(
            new MessageResource([
                'title'     =>      'تم إضافة معرف أيبي جديد لهذا المستخدم',
                'status'    =>      'success'
            ])
        );
    }

    public function remove(Request $request, User $user) {
        $request->validate([
            'ip' => 'required|ip'
        ]);

        $user->ips()->where('ip', $request->ip)->delete();

        if ($user->ips()->count() == 0) {
            $user->update([
                'ip_restricted'        =>      false
            ]);
        }

        return response()->json(
            new MessageResource([
                'title'     =>      'تم حذف معرف الأيبي للمستخدم',
                'status'    =>      'success'
            ])
        );
    }

    public function addrange(Request $request, User $user) {
        $request->validate([
            'start' => 'required|ip',
            'end'   => 'required|ip'
        ]);

        $user->update([
            'ip_restricted'        =>      true
        ]);

        $start = ip2long($request->start);
        $end = ip2long($request->end);

        for ($i = $start; $i <= $end; $i++) {
            $user->ips()->create([
                'ip' => long2ip($i)
            ]);
        }

        return response()->json(
            new MessageResource([
                'title'     =>      'تم إضافة جميع معرفين الأيبي بين الفترة المحددة لهذا المستخدم',
                'status'    =>      'success'
            ])
        );
    }

    public function removerange(Request $request, User $user) {
        $request->validate([
            'start' => 'required|ip',
            'end'   => 'required|ip'
        ]);

        $start = ip2long($request->start);
        $end = ip2long($request->end);

        for ($i = $start; $i <= $end; $i++) {
            $user->ips()->where('ip', long2ip($i))->delete();
        }

        if ($user->ips()->count() == 0) {
            $user->update([
                'ip_restricted'        =>      false
            ]);
        }

        return response()->json(
            new MessageResource([
                'title'     =>      'تم حذف معرف الأيبي للمستخدم',
                'status'    =>      'success'
            ])
        );
    }
}
