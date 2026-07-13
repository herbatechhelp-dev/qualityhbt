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

        // Initiator hanya bisa melihat CR miliknya sendiri atau di mana ia sebagai PIC atau ditunjuk di assessments
        if ($user->role === 'initiator') {
            $query->where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id)
                  ->orWhereJsonContains('qa_verification_data->qa_1->assessments', ['user_id' => $user->id])
                  ->orWhereJsonContains('qa_verification_data->qa_1->assessments', [['user_id' => $user->id]])
                  ->orWhere('qa_verification_data', 'like', '%"user_id":' . $user->id . '%')
                  ->orWhere('qa_verification_data', 'like', '%"user_id": ' . $user->id . '%');
            });
        } elseif (in_array($user->role, ['head_of_quality', 'operational_manager', 'general_manager'])) {
            // Management: lihat CR miliknya, di mana ia PIC, ATAU yang sudah diajukan oleh QA (submitted = true) atau ditunjuk di assessments
            $query->where(function($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhere('pic_id', $user->id)
                  ->orWhereJsonContains('qa_verification_data->qa_1->assessments', ['user_id' => $user->id])
                  ->orWhereJsonContains('qa_verification_data->qa_1->assessments', [['user_id' => $user->id]])
                  ->orWhere('qa_verification_data', 'like', '%"user_id":' . $user->id . '%')
                  ->orWhere('qa_verification_data', 'like', '%"user_id": ' . $user->id . '%')
                  ->orWhere(function($sub) {
                      $sub->where('status', '!=', 'DRAFT')
                          ->where('qa_verification_data->qa_1->submitted', true);
                  });
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
            'department' => 'required|string|max:255',
            'awal_sebelum_perubahan' => 'required|string',
            'usulan_perubahan' => 'required|string',
            'alasan_perubahan' => 'required|string',
            'analisis_dampak' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB
            'attachment_description' => 'nullable|string|max:255',
            'submit_type' => 'required|in:draft,submit',
        ];

        if ($request->type === 'CRA') {
            $rules = array_merge($rules, [
                'sifat_perubahan' => 'required|string|max:255',
                'sifat_perubahan_custom' => 'required_if:sifat_perubahan,Lain - lain|nullable|string|max:255',
                'severity' => 'required|integer|in:1,3,9',
                'occurrence' => 'required|integer|in:1,3,9',
                'detection' => 'required|integer|in:1,3,9',
            ]);
        } else {
            $rules = array_merge($rules, [
                'sifat_perubahan' => 'nullable|string|max:255',
                'sifat_perubahan_custom' => 'nullable|string|max:255',
            ]);
        }

        $validated = $request->validate($rules);

        // Calculate RPN, and prepare risk parameters
        $rpn = null;
        $sifatPerubahan = null;
        $severity = null;
        $occurrence = null;
        $detection = null;

        if ($request->type === 'CRA') {
            $rpn = intval($request->severity) * intval($request->occurrence) * intval($request->detection);
            $sifatPerubahan = $request->sifat_perubahan;
            if ($sifatPerubahan === 'Lain - lain') {
                $sifatPerubahan = $request->sifat_perubahan_custom;
            }
            $severity = $request->severity;
            $occurrence = $request->occurrence;
            $detection = $request->detection;
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
            'sifat_perubahan' => $sifatPerubahan,
            'department' => $request->department,
            'severity' => $severity,
            'occurrence' => $occurrence,
            'detection' => $detection,
            'rpn' => $rpn,
            'awal_sebelum_perubahan' => $request->awal_sebelum_perubahan,
            'usulan_perubahan' => $request->usulan_perubahan,
            'alasan_perubahan' => $request->alasan_perubahan,
            'analisis_dampak' => $request->analisis_dampak,
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
        
        $isAssessor = false;
        if (isset($changeRequest->qa_verification_data['qa_1']['assessments'])) {
            foreach ($changeRequest->qa_verification_data['qa_1']['assessments'] as $ast) {
                if (($ast['user_id'] ?? null) == $user->id) {
                    $isAssessor = true;
                    break;
                }
            }
        }

        if ($user->role === 'initiator' && $changeRequest->initiator_id !== $user->id && $changeRequest->pic_id !== $user->id && !$isAssessor) {
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
                'department' => 'required|string|max:255',
                'awal_sebelum_perubahan' => 'required|string',
                'usulan_perubahan' => 'required|string',
                'alasan_perubahan' => 'required|string',
                'analisis_dampak' => 'required|string',
                'attachment' => 'nullable|file|max:10240',
                'attachment_description' => 'nullable|string|max:255',
                'submit_type' => 'required|in:draft,submit',
            ];

            if ($request->type === 'CRA') {
                $rules = array_merge($rules, [
                    'sifat_perubahan' => 'required|string|max:255',
                    'sifat_perubahan_custom' => 'required_if:sifat_perubahan,Lain - lain|nullable|string|max:255',
                    'severity' => 'required|integer|in:1,3,9',
                    'occurrence' => 'required|integer|in:1,3,9',
                    'detection' => 'required|integer|in:1,3,9',
                ]);
            } else {
                $rules = array_merge($rules, [
                    'sifat_perubahan' => 'nullable|string|max:255',
                    'sifat_perubahan_custom' => 'nullable|string|max:255',
                ]);
            }

            $validated = $request->validate($rules);

            // Calculate RPN, and prepare risk parameters
            $rpn = null;
            $sifatPerubahan = null;
            $severity = null;
            $occurrence = null;
            $detection = null;

            if ($request->type === 'CRA') {
                $rpn = intval($request->severity) * intval($request->occurrence) * intval($request->detection);
                $sifatPerubahan = $request->sifat_perubahan;
                if ($sifatPerubahan === 'Lain - lain') {
                    $sifatPerubahan = $request->sifat_perubahan_custom;
                }
                $severity = $request->severity;
                $occurrence = $request->occurrence;
                $detection = $request->detection;
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
                'sifat_perubahan' => $sifatPerubahan,
                'department' => $request->department,
                'severity' => $severity,
                'occurrence' => $occurrence,
                'detection' => $detection,
                'rpn' => $rpn,
                'awal_sebelum_perubahan' => $request->awal_sebelum_perubahan,
                'usulan_perubahan' => $request->usulan_perubahan,
                'alasan_perubahan' => $request->alasan_perubahan,
                'analisis_dampak' => $request->analisis_dampak,
                'attachment_description' => $request->attachment_description,
                'status' => $status,
            ]);

            return redirect()->route('change-requests.index')->with('success', 'Change Request updated successfully!');
        }

        abort(403, 'Unauthorized.');
    }

    public function evaluate(Request $request, ChangeRequest $changeRequest)
    {
        // Only QA and management can evaluate
        if (!$request->user()->isQaOrManagement()) {
            abort(403, 'Unauthorized. Only QA and Management can evaluate Change Requests.');
        }

        $request->validate([
            'rencana_tindakan' => 'nullable|string',
            'pic_id' => 'nullable|exists:users,id',
            'timeline' => 'nullable|date',
            'hasil_verifikasi' => 'nullable|string',
            'status' => 'required|in:IN REVIEW,APPROVED,IN PROGRESS,COMPLETE,REJECT',
            'qa_verification_data' => 'nullable|array',
            'qa_3_files.*' => 'nullable|file|max:10240',
        ]);

        $verificationData = $request->input('qa_verification_data', []);

        $user = $request->user();
        $isSuperAdmin = $user->role === 'superadmin';

        $oldData = $changeRequest->qa_verification_data ?? [];

        $mapApprovalsBack = function ($data) {
            if ($data === true || $data === 'APPROVED') return 'APPROVED';
            if ($data === 'REJECTED') return 'REJECTED';
            return 'PENDING';
        };

        $oldHu = $mapApprovalsBack($oldData['qa_1']['hu_approved'] ?? null);
        $newHu = $mapApprovalsBack($verificationData['qa_1']['hu_approved'] ?? null);

        $oldOm = $mapApprovalsBack($oldData['qa_1']['om_approved'] ?? null);
        $newOm = $mapApprovalsBack($verificationData['qa_1']['om_approved'] ?? null);

        $oldGm = $mapApprovalsBack($oldData['qa_1']['gm_approved'] ?? null);
        $newGm = $mapApprovalsBack($verificationData['qa_1']['gm_approved'] ?? null);

        $oldSubmitted = $oldData['qa_1']['submitted'] ?? false;
        $newSubmitted = $verificationData['qa_1']['submitted'] ?? false;

        if ($newSubmitted !== $oldSubmitted && !$isSuperAdmin && $user->role !== 'qa') {
            abort(403, 'Hanya QA Officer yang dapat mengajukan persetujuan ke manajemen.');
        }

        // Sequential validation (in case bypassed via raw HTTP client)
        if ($newOm === 'APPROVED' && $newHu !== 'APPROVED') {
            abort(422, 'Persetujuan Operational Manager memerlukan persetujuan Head of Quality.');
        }

        if ($newGm === 'APPROVED' && $newOm !== 'APPROVED') {
            abort(422, 'Persetujuan General Manager memerlukan persetujuan Operational Manager.');
        }

        // Reset cascading downwards if unchecked or not approved
        if ($newHu !== 'APPROVED') {
            $newOm = 'PENDING';
            $newGm = 'PENDING';
            if (isset($verificationData['qa_1'])) {
                $verificationData['qa_1']['om_approved'] = 'PENDING';
                $verificationData['qa_1']['gm_approved'] = 'PENDING';
            }
        }
        if ($newOm !== 'APPROVED') {
            $newGm = 'PENDING';
            if (isset($verificationData['qa_1'])) {
                $verificationData['qa_1']['gm_approved'] = 'PENDING';
            }
        }

        // Role-based security validation
        if ($newHu !== $oldHu && !$isSuperAdmin && $user->role !== 'head_of_quality') {
            abort(403, 'Hanya Head of Quality yang dapat mengubah persetujuan HU.');
        }

        if ($newOm !== $oldOm && !$isSuperAdmin && $user->role !== 'operational_manager') {
            abort(403, 'Hanya Operational Manager yang dapat mengubah persetujuan OM.');
        }

        if ($newGm !== $oldGm && !$isSuperAdmin && $user->role !== 'general_manager') {
            abort(403, 'Hanya General Manager yang dapat mengubah persetujuan GM.');
        }

        // Process attachments in qa_3 if any
        if ($request->hasFile('qa_3_files')) {
            $files = $request->file('qa_3_files');
            foreach ($files as $index => $file) {
                if ($file) {
                    $path = $file->store('attachments/qa', 'public');
                    // Inject into JSON data
                    $verificationData['qa_3']['implementations'][$index]['bukti_dokumen_path'] = $path;
                }
            }
        }

        // Automated status transition logic
        $status = $request->status;

        if ($status !== 'REJECT') {
            if ($newHu === 'REJECTED' || $newOm === 'REJECTED' || $newGm === 'REJECTED') {
                $status = 'REJECT';
            } elseif ($newGm === 'APPROVED') {
                // If GM approved Stage 1, automatically set to IN PROGRESS if currently OPEN or IN REVIEW
                if (in_array($changeRequest->status, ['OPEN', 'IN REVIEW'])) {
                    $status = 'IN PROGRESS';
                }
            } else {
                // If GM is not approved, it must not be IN PROGRESS or COMPLETE
                if (in_array($changeRequest->status, ['IN PROGRESS', 'COMPLETE'])) {
                    $status = 'IN REVIEW';
                }
            }

            // If Stage 3 completed and GM is approved
            $verifikasiCompleted = $verificationData['qa_3']['verifikasi_completed'] ?? false;
            if ($verifikasiCompleted && $newGm === 'APPROVED') {
                $status = 'COMPLETE';
            }
        }

        $changeRequest->update([
            'rencana_tindakan' => $request->rencana_tindakan,
            'pic_id' => $request->pic_id ? intval($request->pic_id) : null,
            'timeline' => $request->timeline,
            'hasil_verifikasi' => $request->hasil_verifikasi,
            'status' => $status,
            'qa_verification_data' => $verificationData,
        ]);

        return redirect()->route('change-requests.show', $changeRequest->id)->with('success', 'Change Request evaluation updated!');
    }

    public function submitAssessment(Request $request, ChangeRequest $changeRequest)
    {
        $user = $request->user();
        
        $request->validate([
            'kajian' => 'required|string',
            'paraf' => 'required|boolean',
            'tanggal' => 'required|date',
        ]);

        $data = $changeRequest->qa_verification_data ?? [];
        if (!isset($data['qa_1'])) {
            $data['qa_1'] = [];
        }
        if (!isset($data['qa_1']['assessments'])) {
            $data['qa_1']['assessments'] = [];
        }

        $found = false;
        foreach ($data['qa_1']['assessments'] as $key => $assessment) {
            if (($assessment['user_id'] ?? null) == $user->id) {
                $data['qa_1']['assessments'][$key]['kajian'] = $request->kajian;
                $data['qa_1']['assessments'][$key]['paraf'] = $request->paraf;
                $data['qa_1']['assessments'][$key]['tanggal'] = $request->tanggal;
                $found = true;
                break;
            }
        }

        if (!$found) {
            abort(403, 'Anda tidak ditunjuk untuk melakukan pengkajian pada Change Request ini.');
        }

        $changeRequest->update([
            'qa_verification_data' => $data,
        ]);

        return redirect()->back()->with('success', 'Pengkajian berhasil disimpan!');
    }

    public function print(ChangeRequest $changeRequest)
    {
        $user = auth()->user();
        
        $isAssessor = false;
        if (isset($changeRequest->qa_verification_data['qa_1']['assessments'])) {
            foreach ($changeRequest->qa_verification_data['qa_1']['assessments'] as $ast) {
                if (($ast['user_id'] ?? null) == $user->id) {
                    $isAssessor = true;
                    break;
                }
            }
        }

        if ($user->role === 'initiator' && $changeRequest->initiator_id !== $user->id && $changeRequest->pic_id !== $user->id && !$isAssessor) {
            abort(403, 'Unauthorized.');
        }

        $changeRequest->load(['initiator', 'pic']);
        
        $viewName = $changeRequest->type === 'CRA' ? 'change-requests.print-cra' : 'change-requests.print-crb';
        
        $hu = \App\Models\User::where('role', 'head_of_quality')->first();
        $om = \App\Models\User::where('role', 'operational_manager')->first();
        $gm = \App\Models\User::where('role', 'general_manager')->first();
        
        return view($viewName, [
            'changeRequest' => $changeRequest,
            'huUser' => $hu,
            'omUser' => $om,
            'gmUser' => $gm
        ]);
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
