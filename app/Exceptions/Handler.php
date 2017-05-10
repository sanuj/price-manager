<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Response;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     *
     * @return void
     */
    public function report(Exception $exception)
    {
        if (App::environment('production') and $this->shouldReport($exception)) {
            resolve('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $exception
     *
     * @return \Illuminate\Http\Response|mixed
     */
    public function render($request, Exception $exception)
    {
        if ($request->is('api/*')) {
            return $this->renderForApi($request, $exception);
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated()
    {
        return redirect()->guest(route('login'));
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $exception
     *
     * @return \Illuminate\Http\Response|mixed
     */
    protected function renderForApi($request, Exception $exception)
    {
        $exception = $this->prepareException($exception);

        if ($exception instanceof ValidationException) {
            return Response::json([
                'status' => 422,
                'message' => 'There is some problem with your input.',
                'errors' => $exception->validator->getMessageBag()->toArray(),
            ], 422);
        } elseif ($exception instanceof HttpException) {
            return Response::json([
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        } elseif (!app()->environment('production')) {
            return Response::json([
                'status' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'trace' => FlattenException::create($exception)->toArray(),
            ], 500);
        }

        return Response::json([
            'status' => 500,
            'message' => 'Unknown error! Try again after some time.',
        ], 500);
    }
}
