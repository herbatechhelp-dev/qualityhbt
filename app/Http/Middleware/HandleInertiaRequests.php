<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = parent::handle($request, $next);

        if ($request->header('X-Inertia')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'settings' => [
                'app_name' => \App\Models\Setting::getValue('app_name', 'QMS Portal'),
                'app_logo' => \App\Models\Setting::getValue('app_logo', '✨ QMS Portal'),
                'app_logo_type' => \App\Models\Setting::getValue('app_logo_type', 'text'),
                'app_logo_path' => \App\Models\Setting::getValue('app_logo_path'),
                'app_favicon_path' => \App\Models\Setting::getValue('app_favicon_path'),
                'google_spreadsheet_id' => \App\Models\Setting::getValue('google_spreadsheet_id', env('GOOGLE_SPREADSHEET_ID')),
                'google_service_account_json_exists' => !empty(\App\Models\Setting::getValue('google_service_account_json', env('GOOGLE_SERVICE_ACCOUNT_JSON'))),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error') ?? $request->session()->get('errors')?->first(),
            ],
        ];
    }
}
