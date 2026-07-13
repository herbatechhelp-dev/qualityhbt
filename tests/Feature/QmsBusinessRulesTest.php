<?php

namespace Tests\Feature;

use App\Models\Capa;
use App\Models\Deviation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QmsBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rpn_is_calculated_automatically_for_cra()
    {
        $user = User::factory()->create(['role' => 'initiator']);

        $response = $this->actingAs($user)->post(route('change-requests.store'), [
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'department' => 'Produksi',
            'awal_sebelum_perubahan' => 'Kondisi awal sebelum dirubah',
            'usulan_perubahan' => 'Rencana usulan perubahan baru',
            'alasan_perubahan' => 'Alasan dilakukannya perubahan',
            'analisis_dampak' => 'Analisis dampak risiko kualitas',
            'severity' => 9,
            'occurrence' => 3,
            'detection' => 1,
            'submit_type' => 'submit',
        ]);

        $response->assertRedirect(route('change-requests.index'));
        $this->assertDatabaseHas('change_requests', [
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'awal_sebelum_perubahan' => 'Kondisi awal sebelum dirubah',
            'usulan_perubahan' => 'Rencana usulan perubahan baru',
            'alasan_perubahan' => 'Alasan dilakukannya perubahan',
            'analisis_dampak' => 'Analisis dampak risiko kualitas',
            'severity' => 9,
            'occurrence' => 3,
            'detection' => 1,
            'rpn' => 27, // 9 * 3 * 1
            'status' => 'OPEN',
        ]);
    }

    public function test_rpn_is_null_for_crb()
    {
        $user = User::factory()->create(['role' => 'initiator']);

        $response = $this->actingAs($user)->post(route('change-requests.store'), [
            'type' => 'CRB',
            'department' => 'HRD',
            'awal_sebelum_perubahan' => 'Kondisi awal sebelum dirubah',
            'usulan_perubahan' => 'Rencana usulan perubahan baru',
            'alasan_perubahan' => 'Alasan dilakukannya perubahan',
            'analisis_dampak' => 'Analisis dampak risiko kualitas',
            'submit_type' => 'submit',
        ]);

        $response->assertRedirect(route('change-requests.index'));
        $this->assertDatabaseHas('change_requests', [
            'type' => 'CRB',
            'rpn' => null,
            'sifat_perubahan' => null,
            'awal_sebelum_perubahan' => 'Kondisi awal sebelum dirubah',
            'usulan_perubahan' => 'Rencana usulan perubahan baru',
            'alasan_perubahan' => 'Alasan dilakukannya perubahan',
            'analisis_dampak' => 'Analisis dampak risiko kualitas',
            'status' => 'OPEN',
        ]);
    }

    public function test_qa_can_evaluate_cr_with_multi_stage_verification_data()
    {
        $qa = User::factory()->create(['role' => 'superadmin']);
        $initiator = User::factory()->create(['role' => 'initiator']);

        $cr = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0001',
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'department' => 'Produksi',
            'status' => 'OPEN',
            'initiator_id' => $initiator->id,
        ]);

        $verificationData = [
            'qa_1' => [
                'pengerjaan_sesuai' => true,
                'ulasan' => 'Pengerjaan sesuai dengan standar operasional.',
                'diulas_oleh' => $qa->name,
                'tanggal' => '2026-07-03',
                'paraf' => true,
                'submitted' => true,
                'hu_approved' => 'APPROVED',
                'om_approved' => 'APPROVED',
                'gm_approved' => 'APPROVED',
            ],
            'qa_2' => [
                'no_registrasi' => 'REG/CR/2026/0001',
                'nama_produk' => 'Paracetamol 500mg',
                'pom_status' => 'option1',
                'documents' => [
                    [
                        'jenis' => 'Spesifikasi',
                        'no_dokumen' => 'SP-05/RND/001-127.00',
                        'tanggal_berlaku' => '2025-08-25',
                        'pic' => 'RND',
                        'timeline' => 'Akhir Mei',
                    ]
                ]
            ],
            'qa_3' => [
                'no_pengendalian' => 'CTRL/CR/2026/0001',
                'implementations' => [
                    [
                        'pic' => 'RND',
                        'perubahan' => 'Uji klinis tahap akhir',
                        'tanggal_dilakukan' => '2026-07-01',
                        'bukti_dokumen_path' => 'attachments/qa/bukti.pdf',
                        'tanggal_berlaku' => '2026-07-05',
                    ]
                ],
                'verifikasi_completed' => true,
            ]
        ];

        $response = $this->actingAs($qa)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'COMPLETE',
            'rencana_tindakan' => 'Rencana tindakan verifikasi akhir',
            'pic_id' => $initiator->id,
            'timeline' => '2026-07-10',
            'hasil_verifikasi' => 'Hasil verifikasi lengkap',
            'qa_verification_data' => $verificationData,
        ]);

        $response->assertRedirect(route('change-requests.show', $cr->id));
        
        $this->assertDatabaseHas('change_requests', [
            'id' => $cr->id,
            'status' => 'COMPLETE',
            'rencana_tindakan' => 'Rencana tindakan verifikasi akhir',
            'pic_id' => $initiator->id,
            'timeline' => '2026-07-10',
            'hasil_verifikasi' => 'Hasil verifikasi lengkap',
        ]);

        $cr->refresh();
        $this->assertEquals($verificationData, $cr->qa_verification_data);
    }

    public function test_sequential_approval_validation_and_role_restrictions()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $hu = User::factory()->create(['role' => 'head_of_quality']);
        $om = User::factory()->create(['role' => 'operational_manager']);
        $gm = User::factory()->create(['role' => 'general_manager']);
        $qa = User::factory()->create(['role' => 'qa']);

        $cr = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0002',
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'department' => 'Produksi',
            'status' => 'OPEN',
            'initiator_id' => $initiator->id,
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'PENDING',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'PENDING',
                ]
            ]
        ]);

        // 1. QA Officer tries to approve HU -> Should fail (403)
        $response = $this->actingAs($qa)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN REVIEW',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'APPROVED',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'PENDING',
                ]
            ]
        ]);
        $response->assertStatus(403);

        // 2. Head of Quality (HU) approves HU -> Should succeed
        $response = $this->actingAs($hu)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN REVIEW',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'APPROVED',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'PENDING',
                ]
            ]
        ]);
        $response->assertRedirect(route('change-requests.show', $cr->id));
        $cr->refresh();
        $this->assertEquals('APPROVED', $cr->qa_verification_data['qa_1']['hu_approved']);

        // 3. Operational Manager tries to approve OM -> Should succeed because HU is approved
        $response = $this->actingAs($om)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN REVIEW',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'APPROVED',
                    'om_approved' => 'APPROVED',
                    'gm_approved' => 'PENDING',
                ]
            ]
        ]);
        $response->assertRedirect(route('change-requests.show', $cr->id));
        $cr->refresh();
        $this->assertEquals('APPROVED', $cr->qa_verification_data['qa_1']['om_approved']);

        // 4. Try to bypass sequence: General Manager checks GM but OM is false/PENDING -> should fail validation
        // Let's reset OM to PENDING first (as OM)
        $this->actingAs($om)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN REVIEW',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'APPROVED',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'PENDING',
                ]
            ]
        ]);
        $cr->refresh();
        $this->assertEquals('PENDING', $cr->qa_verification_data['qa_1']['om_approved']);

        // Now GM tries to check GM (while OM is PENDING) -> should abort with 422
        $response = $this->actingAs($gm)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN REVIEW',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'APPROVED',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'APPROVED',
                ]
            ]
        ]);
        $response->assertStatus(422); // Prerequisite fails
    }

    public function test_submission_is_restricted_to_qa_and_superadmin()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $hu = User::factory()->create(['role' => 'head_of_quality']);
        $qa = User::factory()->create(['role' => 'qa']);

        $cr = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0099',
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'department' => 'Produksi',
            'status' => 'OPEN',
            'initiator_id' => $initiator->id,
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => false,
                    'hu_approved' => 'PENDING',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'PENDING',
                ]
            ]
        ]);

        // 1. Head of Quality (HU) tries to submit to management -> Should fail with 403
        $response = $this->actingAs($hu)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN REVIEW',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'PENDING',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'PENDING',
                ]
            ]
        ]);
        $response->assertStatus(403);

        // 2. QA Officer submits -> Should succeed
        $response = $this->actingAs($qa)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN REVIEW',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'PENDING',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'PENDING',
                ]
            ]
        ]);
        $response->assertRedirect(route('change-requests.show', $cr->id));
        $cr->refresh();
        $this->assertTrue($cr->qa_verification_data['qa_1']['submitted']);
    }

    public function test_management_only_sees_submitted_change_requests_in_list()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $hu = User::factory()->create(['role' => 'head_of_quality']);
        $qa = User::factory()->create(['role' => 'qa']);

        // CR 1: Not submitted yet
        $crNotSubmitted = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0881',
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'department' => 'Produksi',
            'status' => 'OPEN',
            'initiator_id' => $initiator->id,
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => false,
                    'hu_approved' => 'PENDING',
                ]
            ]
        ]);

        // CR 2: Already submitted by QA
        $crSubmitted = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0882',
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'department' => 'Produksi',
            'status' => 'IN REVIEW',
            'initiator_id' => $initiator->id,
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'PENDING',
                ]
            ]
        ]);

        // HU views the listing page
        $response = $this->actingAs($hu)->get(route('change-requests.index'));
        $response->assertStatus(200);

        // HU should NOT see CR 1 (not submitted), but should see CR 2 (submitted)
        $response->assertInertia(fn ($page) => $page
            ->has('changeRequests.data', 1)
            ->where('changeRequests.data.0.cr_number', 'CR/2026/0882')
        );

        // QA views the listing page -> should see BOTH
        $responseQa = $this->actingAs($qa)->get(route('change-requests.index'));
        $responseQa->assertStatus(200);
        $responseQa->assertInertia(fn ($page) => $page
            ->has('changeRequests.data', 2)
        );
    }

    public function test_automated_status_transitions()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $hu = User::factory()->create(['role' => 'head_of_quality']);
        $om = User::factory()->create(['role' => 'operational_manager']);
        $gm = User::factory()->create(['role' => 'general_manager']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $cr = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0003',
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'department' => 'Produksi',
            'status' => 'OPEN',
            'initiator_id' => $initiator->id,
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'PENDING',
                    'om_approved' => 'PENDING',
                    'gm_approved' => 'PENDING',
                ],
                'qa_3' => [
                    'verifikasi_completed' => false,
                ]
            ]
        ]);

        // 1. GM approves Stage 1 (acting as superadmin) -> status transitions to 'IN PROGRESS'
        $response = $this->actingAs($superadmin)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN REVIEW',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'APPROVED',
                    'om_approved' => 'APPROVED',
                    'gm_approved' => 'APPROVED',
                ],
                'qa_3' => [
                    'verifikasi_completed' => false,
                ]
            ]
        ]);
        $response->assertRedirect(route('change-requests.show', $cr->id));
        $cr->refresh();
        $this->assertEquals('IN PROGRESS', $cr->status);

        // 2. Stage 3 verification completed -> status transitions to 'COMPLETE'
        $response = $this->actingAs($superadmin)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'IN PROGRESS',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'APPROVED',
                    'om_approved' => 'APPROVED',
                    'gm_approved' => 'APPROVED',
                ],
                'qa_3' => [
                    'verifikasi_completed' => true,
                ]
            ]
        ]);
        $response->assertRedirect(route('change-requests.show', $cr->id));
        $cr->refresh();
        $this->assertEquals('COMPLETE', $cr->status);

        // 3. GM unchecked/PENDING -> status rolls back to 'IN REVIEW'
        $response = $this->actingAs($superadmin)->post(route('change-requests.evaluate', $cr->id), [
            'status' => 'COMPLETE',
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'hu_approved' => 'APPROVED',
                    'om_approved' => 'APPROVED',
                    'gm_approved' => 'PENDING',
                ],
                'qa_3' => [
                    'verifikasi_completed' => true,
                ]
            ]
        ]);
        $response->assertRedirect(route('change-requests.show', $cr->id));
        $cr->refresh();
        $this->assertEquals('IN REVIEW', $cr->status);
    }

    public function test_deviation_approval_auto_generates_capa()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $qa = User::factory()->create(['role' => 'qa']);

        // Create deviation
        $deviation = Deviation::create([
            'deviation_number' => 'DEV/2026/0001',
            'department' => 'QC',
            'description' => 'Hasil uji stabilitas di luar spesifikasi',
            'status' => 'OPEN',
            'initiator_id' => $initiator->id,
        ]);

        // Act as QA, approve deviation
        $response = $this->actingAs($qa)->post(route('deviations.decide', $deviation->id), [
            'action' => 'APPROVED',
        ]);

        $response->assertRedirect(route('deviations.show', $deviation->id));
        $this->assertDatabaseHas('deviations', [
            'id' => $deviation->id,
            'status' => 'APPROVED',
        ]);

        // Check if CAPA was auto generated
        $this->assertDatabaseHas('capas', [
            'deviation_id' => $deviation->id,
            'deviation_number_ref' => 'DEV/2026/0001',
            'status' => 'DRAFT',
            'initiator_id' => $initiator->id,
        ]);
    }

    public function test_deviation_rejection_requires_reason()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $qa = User::factory()->create(['role' => 'qa']);

        $deviation = Deviation::create([
            'deviation_number' => 'DEV/2026/0001',
            'department' => 'QC',
            'description' => 'Kelembaban ruangan melebihi batas',
            'status' => 'OPEN',
            'initiator_id' => $initiator->id,
        ]);

        // Reject without reason (should fail validation)
        $response = $this->actingAs($qa)->post(route('deviations.decide', $deviation->id), [
            'action' => 'REJECTED',
            'reject_reason' => '',
        ]);

        $response->assertSessionHasErrors('reject_reason');
        $this->assertDatabaseHas('deviations', [
            'id' => $deviation->id,
            'status' => 'OPEN', // Remains open
        ]);

        // Reject with reason
        $response2 = $this->actingAs($qa)->post(route('deviations.decide', $deviation->id), [
            'action' => 'REJECTED',
            'reject_reason' => 'Deskripsi deviasi kurang detail',
        ]);

        $response2->assertRedirect(route('deviations.show', $deviation->id));
        $this->assertDatabaseHas('deviations', [
            'id' => $deviation->id,
            'status' => 'REJECTED',
            'reject_reason' => 'Deskripsi deviasi kurang detail',
        ]);
    }

    public function test_capa_end_date_cannot_be_before_start_date()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $pic = User::factory()->create(['role' => 'initiator']);
        
        $deviation = Deviation::create([
            'deviation_number' => 'DEV/2026/0001',
            'department' => 'QC',
            'description' => 'Kelembaban ruangan melebihi batas',
            'status' => 'APPROVED',
            'initiator_id' => $initiator->id,
        ]);

        $capa = Capa::create([
            'capa_number' => 'CAPA/2026/0001',
            'deviation_id' => $deviation->id,
            'deviation_number_ref' => 'DEV/2026/0001',
            'tanggal_penyimpangan' => '2026-06-09',
            'type_capa' => 'Deviasi',
            'status' => 'DRAFT',
            'initiator_id' => $initiator->id,
        ]);

        // Submit invalid dates (end date before start date)
        $response = $this->actingAs($initiator)->post(route('capas.update', $capa->id), [
            'tindakan_capa' => 'Pasang dehumidifier',
            'pic_id' => $pic->id,
            'tanggal_mulai' => '2026-06-10',
            'tanggal_selesai' => '2026-06-09', // Earlier than start date!
            'submit_type' => 'submit',
        ]);

        $response->assertSessionHasErrors('tanggal_selesai');

        // Submit valid dates
        $response2 = $this->actingAs($initiator)->post(route('capas.update', $capa->id), [
            'tindakan_capa' => 'Pasang dehumidifier',
            'pic_id' => $pic->id,
            'tanggal_mulai' => '2026-06-10',
            'tanggal_selesai' => '2026-06-12', // Valid
            'submit_type' => 'submit',
        ]);

        $response2->assertRedirect(route('capas.show', $capa->id));
        $this->assertDatabaseHas('capas', [
            'id' => $capa->id,
            'tindakan_capa' => 'Pasang dehumidifier',
            'tanggal_mulai' => '2026-06-10',
            'tanggal_selesai' => '2026-06-12',
            'status' => 'IN PROGRESS',
        ]);
    }

    public function test_initiator_can_see_cr_if_creator_or_pic()
    {
        $initiator1 = User::factory()->create(['role' => 'initiator']);
        $initiator2 = User::factory()->create(['role' => 'initiator']);

        // CR created by initiator1
        $cr1 = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0001',
            'type' => 'CRB',
            'sifat_perubahan' => 'Permanen',
            'department' => 'QA',
            'status' => 'OPEN',
            'initiator_id' => $initiator1->id,
        ]);

        // CR created by initiator2 but assigned to initiator1 as PIC
        $cr2 = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0002',
            'type' => 'CRB',
            'sifat_perubahan' => 'Permanen',
            'department' => 'QC',
            'status' => 'IN PROGRESS',
            'initiator_id' => $initiator2->id,
            'pic_id' => $initiator1->id,
        ]);

        // CR created by initiator2 with no connection to initiator1
        $cr3 = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0003',
            'type' => 'CRB',
            'sifat_perubahan' => 'Permanen',
            'department' => 'QC',
            'status' => 'OPEN',
            'initiator_id' => $initiator2->id,
        ]);

        // Acting as initiator1 - should see cr1 and cr2, but not cr3
        $response = $this->actingAs($initiator1)->get(route('change-requests.index'));
        $response->assertStatus(200);
        $crData = $response->original->getData()['page']['props']['changeRequests']['data'];

        $ids = collect($crData)->pluck('id');
        $this->assertTrue($ids->contains($cr1->id));
        $this->assertTrue($ids->contains($cr2->id));
        $this->assertFalse($ids->contains($cr3->id));
    }

    public function test_initiator_can_edit_and_resubmit_rejected_cr()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);

        $cr = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0001',
            'type' => 'CRB',
            'department' => 'QA',
            'status' => 'REJECT',
            'initiator_id' => $initiator->id,
            'awal_sebelum_perubahan' => 'Kondisi awal sebelum dirubah',
            'usulan_perubahan' => 'Rencana usulan perubahan baru',
            'alasan_perubahan' => 'Alasan dilakukannya perubahan',
            'analisis_dampak' => 'Analisis dampak risiko kualitas',
        ]);

        // Resubmit the rejected CR
        $response = $this->actingAs($initiator)->post(route('change-requests.update', $cr->id), [
            'type' => 'CRB',
            'department' => 'QC',
            'awal_sebelum_perubahan' => 'Kondisi awal diubah',
            'usulan_perubahan' => 'Rencana usulan perubahan diubah',
            'alasan_perubahan' => 'Alasan dilakukannya perubahan diubah',
            'analisis_dampak' => 'Analisis dampak risiko kualitas diubah',
            'submit_type' => 'submit',
        ]);

        $response->assertRedirect(route('change-requests.index'));
        $this->assertDatabaseHas('change_requests', [
            'id' => $cr->id,
            'sifat_perubahan' => null,
            'department' => 'QC',
            'awal_sebelum_perubahan' => 'Kondisi awal diubah',
            'usulan_perubahan' => 'Rencana usulan perubahan diubah',
            'alasan_perubahan' => 'Alasan dilakukannya perubahan diubah',
            'analisis_dampak' => 'Analisis dampak risiko kualitas diubah',
            'status' => 'OPEN', // Resubmitted!
        ]);
    }

    public function test_initiator_can_edit_and_resubmit_rejected_deviation()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);

        $deviation = Deviation::create([
            'deviation_number' => 'DEV/2026/0001',
            'department' => 'QC',
            'description' => 'First description',
            'status' => 'REJECTED',
            'reject_reason' => 'Need detail',
            'initiator_id' => $initiator->id,
            'is_other_batch_affected' => false,
            'is_production_stopped' => false,
        ]);

        // Resubmit the rejected deviation
        $response = $this->actingAs($initiator)->post(route('deviations.update', $deviation->id), [
            'department' => 'QA',
            'description' => 'Revised description with detail',
            'is_other_batch_affected' => false,
            'is_production_stopped' => false,
            'submit_type' => 'submit',
        ]);

        $response->assertRedirect(route('deviations.index'));
        $this->assertDatabaseHas('deviations', [
            'id' => $deviation->id,
            'department' => 'QA',
            'description' => 'Revised description with detail',
            'status' => 'OPEN', // Resubmitted!
            'reject_reason' => null, // Cleared!
        ]);
    }

    public function test_qa_can_approve_or_reject_capa_verification()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $pic = User::factory()->create(['role' => 'initiator']);
        $qa = User::factory()->create(['role' => 'qa']);

        $deviation = Deviation::create([
            'deviation_number' => 'DEV/2026/0001',
            'department' => 'QC',
            'description' => 'Deviation desc',
            'status' => 'APPROVED',
            'initiator_id' => $initiator->id,
        ]);

        $capa = Capa::create([
            'capa_number' => 'CAPA/2026/0001',
            'deviation_id' => $deviation->id,
            'deviation_number_ref' => 'DEV/2026/0001',
            'tanggal_penyimpangan' => '2026-06-09',
            'type_capa' => 'Deviasi',
            'status' => 'APPROVED', // Ready for verification
            'initiator_id' => $initiator->id,
            'pic_id' => $pic->id,
        ]);

        // Rejection of proof
        $response1 = $this->actingAs($qa)->post(route('capas.verify', $capa->id), [
            'action' => 'REJECTED',
            'hasil_verifikasi_qa' => 'Bukti foto kurang jelas',
        ]);

        $response1->assertRedirect(route('capas.show', $capa->id));
        $this->assertDatabaseHas('capas', [
            'id' => $capa->id,
            'status' => 'IN PROGRESS', // Reverted
            'hasil_verifikasi_qa' => 'Bukti foto kurang jelas',
        ]);

        // Refresh model to sync with database, then set status back to APPROVED
        $capa->refresh();
        $capa->status = 'APPROVED';
        $capa->save();

        // Approval of proof (Close)
        $response2 = $this->actingAs($qa)->post(route('capas.verify', $capa->id), [
            'action' => 'CLOSE',
            'hasil_verifikasi_qa' => 'Mitigasi terbukti efektif',
        ]);

        $response2->assertRedirect(route('capas.show', $capa->id));
        $this->assertDatabaseHas('capas', [
            'id' => $capa->id,
            'status' => 'CLOSE', // Closed!
            'hasil_verifikasi_qa' => 'Mitigasi terbukti efektif',
        ]);
    }

    public function test_dashboard_statistics_and_recent_items_are_role_adjusted()
    {
        $initiator1 = User::factory()->create(['role' => 'initiator']);
        $initiator2 = User::factory()->create(['role' => 'initiator']);
        $qa = User::factory()->create(['role' => 'qa']);

        // Create Change Requests
        $cr1 = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0001',
            'type' => 'CRB',
            'sifat_perubahan' => 'Permanen',
            'department' => 'QA',
            'status' => 'OPEN',
            'initiator_id' => $initiator1->id,
        ]);

        $cr2 = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0002',
            'type' => 'CRB',
            'sifat_perubahan' => 'Permanen',
            'department' => 'QC',
            'status' => 'OPEN',
            'initiator_id' => $initiator2->id,
        ]);

        // As Initiator 1
        $response = $this->actingAs($initiator1)->get(route('dashboard'));
        $response->assertStatus(200);
        $pageProps = $response->original->getData()['page']['props'];

        $this->assertEquals(1, $pageProps['stats']['cr_total_count']);
        $this->assertCount(1, $pageProps['recentCr']);

        // As QA
        $responseQA = $this->actingAs($qa)->get(route('dashboard'));
        $responseQA->assertStatus(200);
        $pagePropsQA = $responseQA->original->getData()['page']['props'];

        $this->assertEquals(2, $pagePropsQA['stats']['cr_total_count']);
        $this->assertCount(2, $pagePropsQA['recentCr']);
    }

    public function test_pic_can_see_and_submit_their_assessment()
    {
        $initiator1 = User::factory()->create(['role' => 'initiator']);
        $initiator2 = User::factory()->create(['role' => 'initiator']);

        // Create Change Request with initiator2 assigned in assessments
        $cr = \App\Models\ChangeRequest::create([
            'cr_number' => 'CR/2026/0001',
            'type' => 'CRA',
            'sifat_perubahan' => 'Formula',
            'department' => 'Produksi',
            'status' => 'OPEN',
            'initiator_id' => $initiator1->id,
            'qa_verification_data' => [
                'qa_1' => [
                    'submitted' => true,
                    'assessments' => [
                        [
                            'user_id' => $initiator2->id,
                            'name' => $initiator2->name,
                            'tanggal' => null,
                            'kajian' => '',
                            'paraf' => false
                        ]
                    ]
                ]
            ]
        ]);

        // Initiator 2 should see it on their dashboard
        $responseDashboard = $this->actingAs($initiator2)->get(route('dashboard'));
        $responseDashboard->assertStatus(200);
        $pageProps = $responseDashboard->original->getData()['page']['props'];
        $this->assertEquals(1, $pageProps['stats']['cr_total_count']);
        $this->assertCount(1, $pageProps['recentCr']);

        // Initiator 2 should be authorized to view the show page
        $responseShow = $this->actingAs($initiator2)->get(route('change-requests.show', $cr->id));
        $responseShow->assertStatus(200);

        // Initiator 2 submits their assessment
        $responseSubmit = $this->actingAs($initiator2)->post(route('change-requests.submit-assessment', $cr->id), [
            'kajian' => 'Kajian teknis oleh PIC terkait.',
            'tanggal' => '2026-07-10',
            'paraf' => true
        ]);

        $responseSubmit->assertRedirect();
        
        $cr->refresh();
        $this->assertEquals('Kajian teknis oleh PIC terkait.', $cr->qa_verification_data['qa_1']['assessments'][0]['kajian']);
        $this->assertEquals('2026-07-10', $cr->qa_verification_data['qa_1']['assessments'][0]['tanggal']);
        $this->assertTrue($cr->qa_verification_data['qa_1']['assessments'][0]['paraf']);
    }
}
