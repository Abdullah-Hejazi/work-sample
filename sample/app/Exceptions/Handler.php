<?php

namespace App\Exceptions;

use App\Http\Resources\MessageResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler {
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $exception) {
        if(env('APP_ENV') === 'testing'){
            throw $exception;
        }

        if ($exception instanceof ModelNotFoundException) {
            if ($request->wantsJson()) {
                return response()->json(
                    new MessageResource([
                        'title'     =>      __('general.model_not_found'),
                        'text'      =>      __('general.model_not_found_text'),
                        'status'    =>      'warning'
                    ]),
                    404
                );
            }
            abort(404);
        }

        return parent::render($request, $exception);
    }

    public function unauthenticated($request, AuthenticationException $exception) {
        return response()->json([
            'status'    =>      'failed',
            'data'      =>      [
                'message'       =>      [
                    'title'     =>      __('auth.unauth_title'),
                    'text'      =>      __('auth.unauth_text'),
                    'icon'      =>      'error'
                ]
            ]
        ], 401);
    }

}
