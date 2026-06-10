<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ChangeRequest::with(['initiator', 'pic']);

        // Initiator hanya bisa melihat CR miliknya sendiri atau di mana ia sebagai PIC
        if ($user->role === 'initiator') {
            $query->where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id);
            });
        } else {
            // QA & superadmin: lihat semua CR kecuali DRAFT milik inisiator lain
            $query->where(function($q) use ($user) {
                $q->where('status', '!=', 'DRAFT')
                  ->orWhere('initiator_id', $user->id);
            });
        }

        // Filter search
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('cr_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('initiator', function($uq) use ($request) {
                      $uq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('department')) {
            $query->where('department', 'like', '%' . $request->department . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $changeRequests = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $users = User::all(['id', 'name', 'role']);

        return Inertia::render('ChangeRequests/Index', [
            'changeRequests' => $changeRequests,
            'users' => $users,
            'filters' => $request->only(['search', 'department', 'type', 'status']),
        ]);
    }


    public function create()
    {
        // Hanya initiator (dan superadmin) yang bisa membuat CR baru
        if (!in_array(auth()->user()->role, ['initiator', 'superadmin'])) {
            abort(403, 'Unauthorized. Hanya Initiator yang dapat membuat Change Request.');
        }

        $users = User::where('role', 'initiator')->get(['id', 'name']);
        return Inertia::render('ChangeRequests/Create', [
            'users' => $users
        ]);
    }


    public function store(Request $request)
    {
        $rules = [
            'type' => 'required|in:CRA,CRB',
            'sifat_perubahan' => 'required|in:Permanen,Sementara',
            'department' => 'required|string|max:255',
            'attachment' => 'nullable|file|max:10240', // 10MB
            'attachment_description' => 'nullable|string|max:255',
            'submit_type' => 'required|in:draft,submit',
        ];

        if ($request->type === 'CRA') {
            $rules = array_merge($rules, [
                'risk_identification' => 'required|string',
                'potential_cause' => 'required|string',
                'severity' => 'required|integer|min:1|max:10',
                'occurrence' => 'required|integer|min:1|max:10',
                'detection' => 'required|integer|min:1|max:10',
                'risk_control' => 'required|string',
                'action' => 'required|string',
            ]);
        }

        $validated = $request->validate($rules);

        // Calculate RPN if CRA
        $rpn = null;
        if ($request->type === 'CRA') {
            $rpn = intval($request->severity) * intval($request->occurrence) * intval($request->detection);
        }

        // Generate cr_number
        $year = now()->year;
        $count = ChangeRequest::whereYear('created_at', $year)->count();
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $crNumber = "CR/{$year}/{$sequence}";

        // Handle attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $status = $request->submit_type === 'submit' ? 'OPEN' : 'DRAFT';

        $cr = ChangeRequest::create([
            'cr_number' => $crNumber,
            'type' => $request->type,
            'sifat_perubahan' => $request->sifat_perubahan,
            'department' => $request->department,
            'risk_identification' => $request->risk_identification,
            'potential_cause' => $request->potential_cause,
            'severity' => $request->severity,
            'occurrence' => $request->occurrence,
            'detection' => $request->detection,
            'rpn' => $rpn,
            'risk_control' => $request->risk_control,
            'action' => $request->action,
            'attachment_path' => $attachmentPath,
            'attachment_description' => $request->attachment_description,
            'status' => $status,
            'initiator_id' => $request->user()->id,
        ]);

        return redirect()->route('change-requests.index')->with('success', 'Change Request created successfully!');
    }

    public function show(ChangeRequest $changeRequest)
    {
        $user = auth()->user();
        if ($user->role === 'initiator' && $changeRequest->initiator_id !== $user->id && $changeRequest->pic_id !== $user->id) {
            abort(403, 'Unauthorized. Anda tidak memiliki akses ke Change Request ini.');
        }

        $changeRequest->load(['initiator', 'pic']);
        $users = User::all(['id', 'name', 'role']);

        return Inertia::render('ChangeRequests/Show', [
            'changeRequest' => $changeRequest,
            'users' => $users
        ]);
    }

    public function update(Request $request, ChangeRequest $changeRequest)
    {
        // Initiator can update if DRAFT or REJECT
        if (in_array($changeRequest->status, ['DRAFT', 'REJECT']) && $changeRequest->initiator_id === $request->user()->id) {
            $rules = [
                'type' => 'required|in:CRA,CRB',
                'sifat_perubahan' => 'required|in:Permanen,Sementara',
                'department' => 'required|string|max:255',
                'attachment' => 'nullable|file|max:10240',
                'attachment_description' => 'nullable|string|max:255',
                'submit_type' => 'required|in:draft,submit',
            ];

            if ($request->type === 'CRA') {
                $rules = array_merge($rules, [
                    'risk_identification' => 'required|string',
                    'potential_cause' => 'required|string',
                    'severity' => 'required|integer|min:1|max:10',
                    'occurrence' => 'required|integer|min:1|max:10',
                    'detection' => 'required|integer|min:1|max:10',
                    'risk_control' => 'required|string',
                    'action' => 'required|string',
                ]);
            }

            $validated = $request->validate($rules);

            $rpn = null;
            if ($request->type === 'CRA') {
                $rpn = intval($request->severity) * intval($request->occurrence) * intval($request->detection);
            }

            if ($request->hasFile('attachment')) {
                // Delete old file
                if ($changeRequest->attachment_path) {
                    Storage::disk('public')->delete($changeRequest->attachment_path);
                }
                $changeRequest->attachment_path = $request->file('attachment')->store('attachments', 'public');
            }

            $status = $request->submit_type === 'submit' ? 'OPEN' : 'DRAFT';

            $changeRequest->update([
                'type' => $request->type,
                'sifat_perubahan' => $request->sifat_perubahan,
                'department' => $request->department,
                'risk_identification' => $request->risk_identification,
                'potential_cause' => $request->potential_cause,
                'severity' => $request->severity,
                'occurrence' => $request->occurrence,
                'detection' => $request->detection,
                'rpn' => $rpn,
                'risk_control' => $request->risk_control,
                'action' => $request->action,
                'attachment_description' => $request->attachment_description,
                'status' => $status,
            ]);

            return redirect()->route('change-requests.index')->with('success', 'Change Request updated successfully!');
        }

        abort(403, 'Unauthorized.');
    }

    public function evaluate(Request $request, ChangeRequest $changeRequest)
    {
        // Only QA can evaluate
        if (!in_array($request->user()->role, ['qa', 'superadmin'])) {
            abort(403, 'Unauthorized. Only QA and Super Admin can evaluate Change Requests.');
        }

        $request->validate([
            'rencana_tindakan' => 'required|string',
            'pic_id' => 'required|exists:users,id',
            'timeline' => 'required|date',
            'hasil_verifikasi' => 'required|string',
            'status' => 'required|in:IN REVIEW,APPROVED,IN PROGRESS,COMPLETE,REJECT',
        ]);

        $changeRequest->update([
            'rencana_tindakan' => $request->rencana_tindakan,
            'pic_id' => $request->pic_id,
            'timeline' => $request->timeline,
            'hasil_verifikasi' => $request->hasil_verifikasi,
            'status' => $request->status,
        ]);

        return redirect()->route('change-requests.show', $changeRequest->id)->with('success', 'Change Request evaluation updated!');
    }

    public function destroyAttachment(ChangeRequest $changeRequest)
    {
        // Only before submit (DRAFT)
        if ($changeRequest->status === 'DRAFT') {
            if ($changeRequest->attachment_path) {
                Storage::disk('public')->delete($changeRequest->attachment_path);
                $changeRequest->update([
                    'attachment_path' => null,
                    'attachment_description' => null
                ]);
            }
            return redirect()->back()->with('success', 'Attachment deleted successfully.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot delete attachment after document is submitted.']);
    }
}
