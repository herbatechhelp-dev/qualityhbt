<?php

namespace App\Http\Controllers;

use App\Models\Capa;
use App\Models\Deviation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DeviationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Deviation::with('initiator');

        // Initiator hanya bisa melihat deviasi miliknya sendiri
        if ($user->role === 'initiator') {
            $query->where('initiator_id', $user->id);
        }
        // QA & superadmin melihat semua deviasi

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('deviation_number', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('initiator', function($uq) use ($request) {
                      $uq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('department')) {
            $query->where('department', 'like', '%' . $request->department . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $deviations = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Deviations/Index', [
            'deviations' => $deviations,
            'filters'    => $request->only(['search', 'department', 'status']),
        ]);
    }

    public function create()
    {
        // Hanya initiator (dan superadmin) yang bisa melaporkan deviasi
        if (!in_array(auth()->user()->role, ['initiator', 'superadmin'])) {
            abort(403, 'Unauthorized. Hanya Initiator yang dapat melaporkan Deviasi.');
        }
        $users = User::all(['id', 'name', 'role']);
        return Inertia::render('Deviations/Create', ['users' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'department'                  => 'required|string|max:255',
            'pic'                         => 'nullable|string|max:255',
            'tanggal_temuan'              => 'nullable|date',
            'description'                 => 'required|string',
            'jenis_penyimpangan'          => 'nullable|array',
            'identifikasi_penyimpangan'   => 'nullable|array',
            'is_other_batch_affected'     => 'required|boolean',
            'other_batch_affected_details'=> 'nullable|string',
            'deviation_frequency'         => 'nullable|string|in:Sering,Jarang,Tidak Pernah sebelumnya',
            'is_production_stopped'       => 'required|boolean',
            'immediate_action_details'    => 'nullable|string',
            'kepala_departemen'           => 'nullable|string|max:255',
            'attachment'                  => 'nullable|file|max:10240', // legacy single
            'attachment_description'      => 'nullable|string|max:255',
            'new_attachments'             => 'nullable|array',
            'new_attachments.*'           => 'nullable|file|max:10240',
            'new_attachment_descriptions' => 'nullable|array',
            'risk_analysis'               => 'nullable|array',
            'submit_type'                 => 'required|in:draft,submit',
        ]);

        // Generate deviation number only if submitting
        $status = $request->submit_type === 'submit' ? 'OPEN' : 'DRAFT';
        $deviationNumber = null;

        if ($status === 'OPEN') {
            $year     = now()->year;
            $count    = Deviation::whereYear('created_at', $year)->where('status', '!=', 'DRAFT')->count();
            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $deviationNumber = "DEV/{$year}/{$sequence}";
        } else {
            // Draft: generate a temp number (will be replaced on submit)
            $year     = now()->year;
            $count    = Deviation::whereYear('created_at', $year)->count();
            $deviationNumber = "DRAFT/{$year}/" . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        }

        // Handle multiple attachments
        $attachments = [];
        if ($request->hasFile('new_attachments')) {
            $files        = $request->file('new_attachments');
            $descriptions = $request->input('new_attachment_descriptions', []);
            foreach ($files as $index => $file) {
                if ($file) {
                    $path = $file->store('attachments/deviations', 'public');
                    $attachments[] = [
                        'path'        => $path,
                        'description' => $descriptions[$index] ?? '',
                    ];
                }
            }
        }

        // Legacy single attachment fallback
        $legacyPath = null;
        if ($request->hasFile('attachment')) {
            $legacyPath = $request->file('attachment')->store('attachments/deviations', 'public');
        }

        $deviation = Deviation::create([
            'deviation_number'            => $deviationNumber,
            'department'                  => $request->department,
            'pic'                         => $request->pic,
            'tanggal_temuan'              => $request->tanggal_temuan,
            'description'                 => $request->description,
            'jenis_penyimpangan'          => $request->jenis_penyimpangan ?? [],
            'identifikasi_penyimpangan'   => $request->identifikasi_penyimpangan ?? [],
            'is_other_batch_affected'     => $request->is_other_batch_affected,
            'other_batch_affected_details'=> $request->other_batch_affected_details,
            'deviation_frequency'         => $request->deviation_frequency,
            'is_production_stopped'       => $request->is_production_stopped,
            'immediate_action_details'    => $request->immediate_action_details,
            'kepala_departemen'           => $request->kepala_departemen,
            'attachment_path'             => $legacyPath,
            'attachment_description'      => $request->attachment_description,
            'attachments'                 => $attachments,
            'risk_analysis'               => $request->risk_analysis ?? [],
            'status'                      => $status,
            'initiator_id'                => $request->user()->id,
        ]);

        return redirect()->route('deviations.index')->with('success',
            $status === 'OPEN'
                ? "Laporan Deviasi {$deviationNumber} berhasil diajukan!"
                : 'Draft laporan deviasi berhasil disimpan.'
        );
    }

    public function show(Deviation $deviation)
    {
        $user = auth()->user();
        if ($user->role === 'initiator' && $deviation->initiator_id !== $user->id) {
            abort(403, 'Unauthorized. Anda tidak memiliki akses ke laporan deviasi ini.');
        }

        $deviation->load(['initiator', 'capa']);
        return Inertia::render('Deviations/Show', [
            'deviation' => $deviation
        ]);
    }

    public function edit(Deviation $deviation)
    {
        $user = auth()->user();
        // Hanya bisa edit jika status DRAFT atau REJECTED dan oleh initiator pembuat
        if (!in_array($deviation->status, ['DRAFT', 'REJECTED']) || $deviation->initiator_id !== $user->id) {
            abort(403, 'Unauthorized. Hanya pembuat laporan yang dapat memperbarui laporan yang ditolak atau masih draft.');
        }

        $users = User::all(['id', 'name', 'role']);
        return Inertia::render('Deviations/Edit', [
            'deviation' => $deviation,
            'users' => $users
        ]);
    }

    public function update(Request $request, Deviation $deviation)
    {
        $user = $request->user();
        // Hanya bisa diupdate jika status DRAFT atau REJECTED dan oleh initiator pembuat
        if (!in_array($deviation->status, ['DRAFT', 'REJECTED']) || $deviation->initiator_id !== $user->id) {
            abort(403, 'Unauthorized. Hanya pembuat laporan yang dapat memperbarui laporan yang ditolak atau masih draft.');
        }

        $request->validate([
            'department'                  => 'required|string|max:255',
            'pic'                         => 'nullable|string|max:255',
            'tanggal_temuan'              => 'nullable|date',
            'description'                 => 'required|string',
            'jenis_penyimpangan'          => 'nullable|array',
            'identifikasi_penyimpangan'   => 'nullable|array',
            'is_other_batch_affected'     => 'required|boolean',
            'other_batch_affected_details'=> 'nullable|string',
            'deviation_frequency'         => 'nullable|string|in:Sering,Jarang,Tidak Pernah sebelumnya',
            'is_production_stopped'       => 'required|boolean',
            'immediate_action_details'    => 'nullable|string',
            'kepala_departemen'           => 'nullable|string|max:255',
            'attachment'                  => 'nullable|file|max:10240',
            'attachment_description'      => 'nullable|string|max:255',
            'new_attachments'             => 'nullable|array',
            'new_attachments.*'           => 'nullable|file|max:10240',
            'new_attachment_descriptions' => 'nullable|array',
            'risk_analysis'               => 'nullable|array',
            'submit_type'                 => 'required|in:draft,submit',
        ]);

        $status = $request->submit_type === 'submit' ? 'OPEN' : 'DRAFT';

        // Regenerate deviation number if submitting from DRAFT
        $deviationNumber = $deviation->deviation_number;
        if ($status === 'OPEN' && str_starts_with($deviationNumber, 'DRAFT/')) {
            $year     = now()->year;
            $count    = Deviation::whereYear('created_at', $year)->where('status', '!=', 'DRAFT')->count();
            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $deviationNumber = "DEV/{$year}/{$sequence}";
        }

        // Handle new attachments (merge with existing)
        $existingAttachments = $deviation->attachments ?? [];
        if ($request->hasFile('new_attachments')) {
            $files        = $request->file('new_attachments');
            $descriptions = $request->input('new_attachment_descriptions', []);
            foreach ($files as $index => $file) {
                if ($file) {
                    $path = $file->store('attachments/deviations', 'public');
                    $existingAttachments[] = [
                        'path'        => $path,
                        'description' => $descriptions[$index] ?? '',
                    ];
                }
            }
        }

        $legacyPath = $deviation->attachment_path;
        if ($request->hasFile('attachment')) {
            if ($legacyPath) {
                Storage::disk('public')->delete($legacyPath);
            }
            $legacyPath = $request->file('attachment')->store('attachments/deviations', 'public');
        }

        $deviation->update([
            'deviation_number'            => $deviationNumber,
            'department'                  => $request->department,
            'pic'                         => $request->pic,
            'tanggal_temuan'              => $request->tanggal_temuan,
            'description'                 => $request->description,
            'jenis_penyimpangan'          => $request->jenis_penyimpangan ?? [],
            'identifikasi_penyimpangan'   => $request->identifikasi_penyimpangan ?? [],
            'is_other_batch_affected'     => $request->is_other_batch_affected,
            'other_batch_affected_details'=> $request->other_batch_affected_details,
            'deviation_frequency'         => $request->deviation_frequency,
            'is_production_stopped'       => $request->is_production_stopped,
            'immediate_action_details'    => $request->immediate_action_details,
            'kepala_departemen'           => $request->kepala_departemen,
            'attachment_path'             => $legacyPath,
            'attachment_description'      => $request->attachment_description,
            'attachments'                 => $existingAttachments,
            'risk_analysis'               => $request->risk_analysis ?? [],
            'status'                      => $status,
            'reject_reason'               => null,
        ]);

        return redirect()->route('deviations.index')->with('success',
            $status === 'OPEN'
                ? "Laporan Deviasi {$deviationNumber} berhasil diajukan!"
                : 'Draft laporan deviasi berhasil disimpan.'
        );
    }

    public function decide(Request $request, Deviation $deviation)
    {
        if (!$request->user()->isQaOrManagement()) {
            abort(403, 'Unauthorized. Only QA and Management can decide on deviations.');
        }

        $request->validate([
            'action'        => 'required|in:APPROVED,REJECTED',
            'reject_reason' => 'required_if:action,REJECTED|nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if ($request->action === 'REJECTED') {
                $deviation->update([
                    'status'        => 'REJECTED',
                    'reject_reason' => $request->reject_reason,
                ]);
            } else {
                // APPROVED
                $deviation->update([
                    'status'        => 'APPROVED',
                    'reject_reason' => null,
                ]);

                // Auto generate CAPA
                $year     = now()->year;
                $count    = Capa::whereYear('created_at', $year)->count();
                $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
                $capaNumber = "CAPA/{$year}/{$sequence}";

                Capa::create([
                    'capa_number'           => $capaNumber,
                    'deviation_id'          => $deviation->id,
                    'deviation_number_ref'  => $deviation->deviation_number,
                    'tanggal_penyimpangan'  => $deviation->created_at->toDateString(),
                    'type_capa'             => 'Deviasi',
                    'status'                => 'DRAFT',
                    'initiator_id'          => $deviation->initiator_id,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process decision: ' . $e->getMessage()]);
        }

        return redirect()->route('deviations.show', $deviation->id)->with('success', 'Deviation status updated.');
    }

    public function updateFmea(Request $request, Deviation $deviation)
    {
        if (!$request->user()->isQaOrManagement()) {
            abort(403, 'Unauthorized. Only QA and Management can edit FMEA.');
        }

        // Only editable before reject or approve (meaning status is 'OPEN' or 'IN REVIEW')
        if (!in_array($deviation->status, ['OPEN', 'IN REVIEW'])) {
            abort(400, 'Cannot edit FMEA at this stage.');
        }

        $request->validate([
            'risk_analysis' => 'nullable|array',
        ]);

        $deviation->update([
            'risk_analysis' => $request->risk_analysis ?? [],
        ]);

        return redirect()->route('deviations.show', $deviation->id)->with('success', 'Analisis Risiko FMEA berhasil diperbarui oleh QA.');
    }

    public function printDr(Deviation $deviation)
    {
        $user = auth()->user();
        if ($user->role === 'initiator' && $deviation->initiator_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $deviation->load(['initiator', 'capa']);

        $hu = \App\Models\User::where('role', 'head_of_quality')->first();
        $om = \App\Models\User::where('role', 'operational_manager')->first();
        $gm = \App\Models\User::where('role', 'general_manager')->first();

        return view('deviations.print-dr', [
            'deviation' => $deviation,
            'huUser' => $hu,
            'omUser' => $om,
            'gmUser' => $gm
        ]);
    }

    public function printInvestigation(Deviation $deviation)
    {
        $user = auth()->user();
        if ($user->role === 'initiator' && $deviation->initiator_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $deviation->load(['initiator', 'capa']);

        $hu = \App\Models\User::where('role', 'head_of_quality')->first();
        $om = \App\Models\User::where('role', 'operational_manager')->first();
        $gm = \App\Models\User::where('role', 'general_manager')->first();

        return view('deviations.print-investigation', [
            'deviation' => $deviation,
            'huUser' => $hu,
            'omUser' => $om,
            'gmUser' => $gm
        ]);
    }
}
