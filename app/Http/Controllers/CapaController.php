<?php

namespace App\Http\Controllers;

use App\Models\Capa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CapaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Capa::with(['initiator', 'pic', 'deviation']);

        // Initiator hanya melihat CAPA miliknya sendiri atau yang ia jadi PIC
        if ($user->role === 'initiator') {
            $query->where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id);
            });
        }
        // QA & superadmin melihat semua CAPA

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('capa_number', 'like', '%' . $request->search . '%')
                  ->orWhere('deviation_number_ref', 'like', '%' . $request->search . '%')
                  ->orWhereHas('initiator', function($uq) use ($request) {
                      $uq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $capas = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $users = User::all(['id', 'name', 'role']);

        return Inertia::render('Capas/Index', [
            'capas' => $capas,
            'users' => $users,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Capa $capa)
    {
        $capa->load(['initiator', 'pic', 'deviation']);
        $users = User::all(['id', 'name', 'role']);

        return Inertia::render('Capas/Show', [
            'capa' => $capa,
            'users' => $users
        ]);
    }

    public function update(Request $request, Capa $capa)
    {
        // Users can edit CAPA if DRAFT
        if ($capa->status !== 'DRAFT') {
            return redirect()->back()->withErrors(['error' => 'You can only update CAPA when it is in DRAFT status.']);
        }

        $request->validate([
            'tindakan_capa' => 'required|string',
            'pic_id' => 'required|exists:users,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'submit_type' => 'required|in:draft,submit',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal Selesai tidak boleh lebih lampau dari Tanggal Mulai.',
        ]);

        $status = $request->submit_type === 'submit' ? 'IN PROGRESS' : 'DRAFT';

        $capa->update([
            'tindakan_capa' => $request->tindakan_capa,
            'pic_id' => $request->pic_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'status' => $status,
        ]);

        return redirect()->route('capas.show', $capa->id)->with('success', 'CAPA details updated successfully.');
    }

    public function uploadProof(Request $request, Capa $capa)
    {
        $user = $request->user();

        // Status harus IN PROGRESS
        if ($capa->status !== 'IN PROGRESS') {
            return redirect()->back()->withErrors(['error' => 'Bukti hanya dapat diunggah saat CAPA berstatus IN PROGRESS.']);
        }

        // Hanya PIC, Initiator, atau QA/Management yang boleh upload bukti
        $isAuthorized = $user->isQaOrManagement()
            || $capa->initiator_id === $user->id
            || $capa->pic_id === $user->id;

        if (!$isAuthorized) {
            abort(403, 'Unauthorized. Hanya PIC, Inisiator, atau QA yang dapat mengunggah bukti.');
        }

        $request->validate([
            'bukti_lapangan' => 'required|file|max:10240', // 10MB
        ]);

        if ($request->hasFile('bukti_lapangan')) {
            if ($capa->bukti_lapangan_path) {
                Storage::disk('public')->delete($capa->bukti_lapangan_path);
            }
            $path = $request->file('bukti_lapangan')->store('bukti_lapangan', 'public');
            
            $capa->update([
                'bukti_lapangan_path' => $path,
                'status' => 'APPROVED', // Moves to approved, waiting for QA verification close
            ]);

            return redirect()->route('capas.show', $capa->id)->with('success', 'Proof of completion uploaded. Sent to QA for review.');
        }

        return redirect()->back()->withErrors(['error' => 'No file uploaded.']);
    }

    public function verify(Request $request, Capa $capa)
    {
        // Only QA/Management can verify
        if (!$request->user()->isQaOrManagement()) {
            abort(403, 'Unauthorized. Only QA and Management can verify CAPA.');
        }

        if ($capa->status !== 'APPROVED') {
            return redirect()->back()->withErrors(['error' => 'CAPA must be APPROVED (with uploaded proof) before verification.']);
        }

        $request->validate([
            'action' => 'required|in:CLOSE,REJECTED',
            'hasil_verifikasi_qa' => 'required|string',
        ]);

        if ($request->action === 'CLOSE') {
            $capa->update([
                'hasil_verifikasi_qa' => $request->hasil_verifikasi_qa,
                'status' => 'CLOSE',
            ]);
            $msg = 'CAPA verified and CLOSED successfully.';
        } else {
            // REJECTED -> status back to IN PROGRESS
            $capa->update([
                'hasil_verifikasi_qa' => $request->hasil_verifikasi_qa,
                'status' => 'IN PROGRESS',
            ]);
            $msg = 'CAPA proof rejected. Status reverted to IN PROGRESS.';
        }

        return redirect()->route('capas.show', $capa->id)->with('success', $msg);
    }
}
