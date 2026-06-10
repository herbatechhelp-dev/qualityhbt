<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->user()->role !== 'superadmin') {
                abort(403, 'Unauthorized. Only Super Admins can access these settings.');
            }
            return $next($request);
        });
    }
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('role', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('SuperAdmin/Users', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:initiator,qa,superadmin',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->back()->with('success', 'User created successfully!');
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:initiator,qa,superadmin',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()->back()->with('success', 'User updated successfully!');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->withErrors(['error' => 'You cannot delete your own account!']);
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully!');
    }

    public function settings()
    {
        return Inertia::render('SuperAdmin/Settings', [
            'settings' => [
                'app_name' => Setting::getValue('app_name', 'QMS Portal'),
                'app_logo' => Setting::getValue('app_logo', '✨ QMS Portal'),
                'app_logo_type' => Setting::getValue('app_logo_type', 'text'),
                'app_logo_path' => Setting::getValue('app_logo_path'),
                'app_favicon_path' => Setting::getValue('app_favicon_path'),
                'google_spreadsheet_id' => Setting::getValue('google_spreadsheet_id', env('GOOGLE_SPREADSHEET_ID')),
                'google_service_account_json' => Setting::getValue('google_service_account_json', env('GOOGLE_SERVICE_ACCOUNT_JSON')),
            ]
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_logo_type' => 'required|string|in:text,image',
            'app_logo' => 'nullable|string|max:255',
            'app_logo_image' => 'nullable|image|max:2048',
            'app_favicon_image' => 'nullable|image|mimes:png,jpg,jpeg,ico,svg|max:512',
            'google_spreadsheet_id' => 'nullable|string|max:255',
            'google_service_account_json' => 'nullable|string',
        ]);

        Setting::setValue('app_name', $request->app_name);
        Setting::setValue('app_logo_type', $request->app_logo_type);
        Setting::setValue('app_logo', $request->app_logo);

        if ($request->app_logo_type === 'image') {
            if ($request->hasFile('app_logo_image')) {
                // Delete old logo file if exists
                $oldPath = Setting::getValue('app_logo_path');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                // Store new logo file
                $path = $request->file('app_logo_image')->store('logos', 'public');
                Setting::setValue('app_logo_path', $path);
            }
        }

        // Handle favicon upload
        if ($request->hasFile('app_favicon_image')) {
            // Delete old favicon file if exists
            $oldFavicon = Setting::getValue('app_favicon_path');
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }

            // Store new favicon file
            $faviconPath = $request->file('app_favicon_image')->store('favicons', 'public');
            Setting::setValue('app_favicon_path', $faviconPath);
        }

        Setting::setValue('google_spreadsheet_id', $request->google_spreadsheet_id);
        Setting::setValue('google_service_account_json', $request->google_service_account_json);

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan!');
    }
}
