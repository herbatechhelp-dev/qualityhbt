<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\DeviationController;
use App\Http\Controllers\CapaController;
use App\Models\MasterDocument;
use App\Models\ChangeRequest;
use App\Models\Deviation;
use App\Models\Capa;

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    
    if ($user->role === 'initiator') {
        // Stats for initiator
        $stats = [
            'documents_count' => MasterDocument::count(),
            'cr_open_count' => ChangeRequest::where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id);
            })->where('status', 'OPEN')->count(),
            'cr_total_count' => ChangeRequest::where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id);
            })->count(),
            'deviations_pending_count' => Deviation::where('initiator_id', $user->id)->where('status', 'OPEN')->count(),
            'deviations_total_count' => Deviation::where('initiator_id', $user->id)->count(),
            'capa_in_progress_count' => Capa::where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id);
            })->where('status', 'IN PROGRESS')->count(),
            'capa_total_count' => Capa::where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id);
            })->count(),
        ];

        // Recent items for initiator
        $recentCr = ChangeRequest::with(['initiator', 'pic'])
            ->where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id);
            })->orderBy('created_at', 'desc')->take(5)->get();

        $recentDeviations = Deviation::with(['initiator'])
            ->where('initiator_id', $user->id)
            ->orderBy('created_at', 'desc')->take(5)->get();

        $recentCapas = Capa::with(['initiator', 'pic'])
            ->where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id);
            })->orderBy('created_at', 'desc')->take(5)->get();
    } else {
        // Stats for QA / Superadmin (Global)
        $stats = [
            'documents_count' => MasterDocument::count(),
            'cr_open_count' => ChangeRequest::where('status', 'OPEN')->count(),
            'cr_total_count' => ChangeRequest::count(),
            'deviations_pending_count' => Deviation::where('status', 'OPEN')->count(),
            'deviations_total_count' => Deviation::count(),
            'capa_in_progress_count' => Capa::where('status', 'IN PROGRESS')->count(),
            'capa_total_count' => Capa::count(),
        ];

        // Recent items for QA / Superadmin
        $recentCr = ChangeRequest::with(['initiator', 'pic'])
            ->orderBy('created_at', 'desc')->take(5)->get();

        $recentDeviations = Deviation::with(['initiator'])
            ->orderBy('created_at', 'desc')->take(5)->get();

        $recentCapas = Capa::with(['initiator', 'pic'])
            ->orderBy('created_at', 'desc')->take(5)->get();
    }

    $recentDocuments = MasterDocument::orderBy('created_at', 'desc')->take(5)->get();

    return Inertia::render('Dashboard', [
        'stats' => $stats,
        'recentCr' => $recentCr,
        'recentDeviations' => $recentDeviations,
        'recentCapas' => $recentCapas,
        'recentDocuments' => $recentDocuments,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Master list routes
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents/import', [DocumentController::class, 'import'])->name('documents.import');
    Route::post('/documents/sync-sheets', [DocumentController::class, 'syncSheets'])->name('documents.sync-sheets');


    // Change Request routes
    Route::get('/change-requests', [ChangeRequestController::class, 'index'])->name('change-requests.index');
    Route::get('/change-requests/create', [ChangeRequestController::class, 'create'])->name('change-requests.create');
    Route::post('/change-requests', [ChangeRequestController::class, 'store'])->name('change-requests.store');
    Route::get('/change-requests/{changeRequest}', [ChangeRequestController::class, 'show'])->name('change-requests.show');
    Route::post('/change-requests/{changeRequest}', [ChangeRequestController::class, 'update'])->name('change-requests.update');
    Route::post('/change-requests/{changeRequest}/evaluate', [ChangeRequestController::class, 'evaluate'])->name('change-requests.evaluate');
    Route::delete('/change-requests/{changeRequest}/attachment', [ChangeRequestController::class, 'destroyAttachment'])->name('change-requests.destroy-attachment');

    // Deviation routes
    Route::get('/deviations', [DeviationController::class, 'index'])->name('deviations.index');
    Route::get('/deviations/create', [DeviationController::class, 'create'])->name('deviations.create');
    Route::post('/deviations', [DeviationController::class, 'store'])->name('deviations.store');
    Route::get('/deviations/{deviation}', [DeviationController::class, 'show'])->name('deviations.show');
    Route::post('/deviations/{deviation}', [DeviationController::class, 'update'])->name('deviations.update');
    Route::post('/deviations/{deviation}/decide', [DeviationController::class, 'decide'])->name('deviations.decide');

    // CAPA routes
    Route::get('/capas', [CapaController::class, 'index'])->name('capas.index');
    Route::get('/capas/{capa}', [CapaController::class, 'show'])->name('capas.show');
    Route::post('/capas/{capa}', [CapaController::class, 'update'])->name('capas.update');
    Route::post('/capas/{capa}/proof', [CapaController::class, 'uploadProof'])->name('capas.upload-proof');
    Route::post('/capas/{capa}/verify', [CapaController::class, 'verify'])->name('capas.verify');

    // Super Admin routes
    Route::get('/superadmin/users', [\App\Http\Controllers\SuperAdminController::class, 'users'])->name('superadmin.users');
    Route::post('/superadmin/users', [\App\Http\Controllers\SuperAdminController::class, 'storeUser'])->name('superadmin.users.store');
    Route::put('/superadmin/users/{user}', [\App\Http\Controllers\SuperAdminController::class, 'updateUser'])->name('superadmin.users.update');
    Route::delete('/superadmin/users/{user}', [\App\Http\Controllers\SuperAdminController::class, 'destroyUser'])->name('superadmin.users.destroy');

    Route::get('/superadmin/settings', [\App\Http\Controllers\SuperAdminController::class, 'settings'])->name('superadmin.settings');
    Route::post('/superadmin/settings', [\App\Http\Controllers\SuperAdminController::class, 'updateSettings'])->name('superadmin.settings.update');
});

require __DIR__.'/auth.php';
