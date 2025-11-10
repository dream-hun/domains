<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\Domains\TransferDomainAction;
use App\Models\Domain;
use App\Models\User;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TransferDomainActionTest extends TestCase
{
    use RefreshDatabase;

    private TransferDomainAction $action;

    private Domain $domain;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->domain = Domain::factory()->create(['name' => 'example.com']);

        $eppService = $this->createMock(EppDomainService::class);
        $namecheapService = $this->createMock(NamecheapDomainService::class);

        $this->action = new TransferDomainAction($eppService, $namecheapService);
    }

    public function test_handle_transfer_for_local_domain(): void
    {
        $this->actingAs($this->user);

        $domain = Domain::factory()->rwDomain()->for($this->user, 'owner')->create();
        $transferData = [
            'auth_code' => 'test-auth-code',
            'registrant_contact_id' => 1,
        ];

        $eppService = $this->createMock(EppDomainService::class);
        $eppService->expects($this->once())
            ->method('transferDomainRegistration')
            ->willReturn(['success' => true, 'message' => 'Transfer initiated']);

        $namecheapService = $this->createMock(NamecheapDomainService::class);
        $namecheapService->expects($this->never())
            ->method('transferDomainRegistration');

        $action = new TransferDomainAction($eppService, $namecheapService);
        $result = $action->handle($domain, $transferData);

        expect($result['success'])->toBeTrue();
    }

    public function test_handle_transfer_for_international_domain(): void
    {
        $this->actingAs($this->user);

        $domain = Domain::factory()->comDomain()->for($this->user, 'owner')->create();
        $transferData = [
            'auth_code' => 'test-auth-code',
            'registrant_contact_id' => 1,
        ];

        $eppService = $this->createMock(EppDomainService::class);
        $eppService->expects($this->never())
            ->method('transferDomainRegistration');

        $namecheapService = $this->createMock(NamecheapDomainService::class);
        $namecheapService->expects($this->once())
            ->method('transferDomainRegistration')
            ->willReturn(['success' => true, 'message' => 'Transfer initiated']);

        $action = new TransferDomainAction($eppService, $namecheapService);
        $result = $action->handle($domain, $transferData);

        expect($result['success'])->toBeTrue();
    }
}
