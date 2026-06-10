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
            'sifat_perubahan' => 'Permanen',
            'department' => 'Produksi',
            'risk_identification' => 'Potensi kegagalan mesin tablet',
            'potential_cause' => 'Kurang pemeliharaan berkala',
            'severity' => 6,
            'occurrence' => 4,
            'detection' => 3,
            'risk_control' => 'Inspeksi manual harian',
            'action' => 'Jadwalkan PM bulanan',
            'submit_type' => 'submit',
        ]);

        $response->assertRedirect(route('change-requests.index'));
        $this->assertDatabaseHas('change_requests', [
            'type' => 'CRA',
            'severity' => 6,
            'occurrence' => 4,
            'detection' => 3,
            'rpn' => 72, // 6 * 4 * 3
            'status' => 'OPEN',
        ]);
    }

    public function test_rpn_is_null_for_crb()
    {
        $user = User::factory()->create(['role' => 'initiator']);

        $response = $this->actingAs($user)->post(route('change-requests.store'), [
            'type' => 'CRB',
            'sifat_perubahan' => 'Sementara',
            'department' => 'HRD',
            'submit_type' => 'submit',
        ]);

        $response->assertRedirect(route('change-requests.index'));
        $this->assertDatabaseHas('change_requests', [
            'type' => 'CRB',
            'rpn' => null,
            'status' => 'OPEN',
        ]);
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
            'sifat_perubahan' => 'Permanen',
            'department' => 'QA',
            'status' => 'REJECT',
            'initiator_id' => $initiator->id,
        ]);

        // Resubmit the rejected CR
        $response = $this->actingAs($initiator)->post(route('change-requests.update', $cr->id), [
            'type' => 'CRB',
            'sifat_perubahan' => 'Sementara',
            'department' => 'QC',
            'submit_type' => 'submit',
        ]);

        $response->assertRedirect(route('change-requests.index'));
        $this->assertDatabaseHas('change_requests', [
            'id' => $cr->id,
            'sifat_perubahan' => 'Sementara',
            'department' => 'QC',
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
        ]);

        // Resubmit the rejected deviation
        $response = $this->actingAs($initiator)->post(route('deviations.update', $deviation->id), [
            'department' => 'QA',
            'description' => 'Revised description with detail',
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
}
