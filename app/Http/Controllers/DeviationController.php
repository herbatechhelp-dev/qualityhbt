<?php

namespace App\Http\Controllers;

use App\Models\Capa;
use App\Models\Deviation;
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
            'filters' => $request->only(['search', 'department', 'status']),
        ]);
    }

    public function create()
    {
        // Hanya initiator (dan superadmin) yang bisa melaporkan deviasi
        if (!in_array(auth()->user()->role, ['initiator', 'superadmin'])) {
            abort(403, 'Unauthorized. Hanya Initiator yang dapat melaporkan Deviasi.');
        }
        return Inertia::render('Deviations/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'department' => 'required|string|max:255',
            'attachment' => 'nullable|file|max:10240', // 10MB
            'attachment_description' => 'nullable|string|max:255',
        ]);

        $year = now()->year;
        $count = Deviation::whereYear('created_at', $year)->count();
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $deviationNumber = "DEV/{$year}/{$sequence}";

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments/deviations', 'public');
        }

        $deviation = Deviation::create([
            'deviation_number' => $deviationNumber,
            'department' => $request->department,
            'description' => $request->description,
            'attachment_path' => $attachmentPath,
            'attachment_description' => $request->attachment_description,
            'status' => 'OPEN',
            'initiator_id' => $request->user()->id,
        ]);

        return redirect()->route('deviations.index')->with('success', 'Deviation report submitted successfully!');
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

    public function update(Request $request, Deviation $deviation)
    {
        $user = $request->user();
        // Hanya bisa diupdate jika status REJECTED dan oleh initiator pembuat
        if ($deviation->status !== 'REJECTED' || $deviation->initiator_id !== $user->id) {
            abort(403, 'Unauthorized. Hanya pembuat laporan yang dapat memperbarui laporan yang ditolak.');
        }

        $request->validate([
            'description' => 'required|string',
            'department' => 'required|string|max:255',
            'attachment' => 'nullable|file|max:10240', // 10MB
            'attachment_description' => 'nullable|string|max:255',
        ]);

        $attachmentPath = $deviation->attachment_path;
        if ($request->hasFile('attachment')) {
            if ($deviation->attachment_path) {
                Storage::disk('public')->delete($deviation->attachment_path);
            }
            $attachmentPath = $request->file('attachment')->store('attachments/deviations', 'public');
        }

        $deviation->update([
            'department' => $request->department,
            'description' => $request->description,
            'attachment_path' => $attachmentPath,
            'attachment_description' => $request->attachment_description,
            'status' => 'OPEN', // resubmit to QA
            'reject_reason' => null, // clear reject reason
        ]);

        return redirect()->route('deviations.index')->with('success', 'Laporan deviasi berhasil diperbarui dan dikirim kembali.');
    }

    public function decide(Request $request, Deviation $deviation)
    {
        if (!in_array($request->user()->role, ['qa', 'superadmin'])) {
            abort(403, 'Unauthorized. Only QA and Super Admin can decide on deviations.');
        }

        $request->validate([
            'action' => 'required|in:APPROVED,REJECTED',
            'reject_reason' => 'required_if:action,REJECTED|nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if ($request->action === 'REJECTED') {
                $deviation->update([
                    'status' => 'REJECTED',
                    'reject_reason' => $request->reject_reason,
                ]);
            } else {
                // APPROVED
                $deviation->update([
                    'status' => 'APPROVED',
                    'reject_reason' => null,
                ]);

                // Auto generate CAPA
                $year = now()->year;
                $count = Capa::whereYear('created_at', $year)->count();
                $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
                $capaNumber = "CAPA/{$year}/{$sequence}";

                Capa::create([
                    'capa_number' => $capaNumber,
                    'deviation_id' => $deviation->id,
                    'deviation_number_ref' => $deviation->deviation_number,
                    'tanggal_penyimpangan' => $deviation->created_at->toDateString(),
                    'type_capa' => 'Deviasi',
                    'status' => 'DRAFT',
                    'initiator_id' => $deviation->initiator_id,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process decision: ' . $e->getMessage()]);
        }

        return redirect()->route('deviations.show', $deviation->id)->with('success', 'Deviation status updated.');
    }
}

