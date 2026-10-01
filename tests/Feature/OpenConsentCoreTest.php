<?php

use App\Models\ConsentEvent;
use App\Models\ConsentGrant;
use App\Models\Document;
use App\Models\Evidence;
use App\Models\Jurisdiction;
use App\Models\LegalInstrument;
use App\Models\Party;
use App\Models\User;
use App\Services\ConsentIntegrityService;
use App\Services\DocumentService;
use App\Services\StoredObjectService;
use App\Services\VaultService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

function fakeTextFile(string $name, string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

test('a user can create a vault with owner membership', function () {
    $user = User::factory()->create();

    $vault = app(VaultService::class)->createForUser($user, 'Personal legal archive');

    expect($vault->owner_id)->toBe($user->id)
        ->and($vault->members()->whereKey($user->id)->wherePivot('role', 'owner')->exists())->toBeTrue();
});

test('private document upload stores immutable versions and original sha256', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $vault = app(VaultService::class)->createForUser($user, 'Archive');
    $documents = app(DocumentService::class);

    $first = $documents->uploadPrivateDocument($vault, fakeTextFile('terms.txt', 'version one'), [
        'title' => 'Service terms',
        'version_label' => 'v1',
    ]);

    $second = $documents->addPrivateVersion($first->document, fakeTextFile('terms.txt', 'version two'), [
        'version_label' => 'v2',
    ]);

    expect($first->document->versions()->count())->toBe(2)
        ->and($first->refresh()->originalObject->content_hash)->toBe(hash('sha256', 'version one'))
        ->and($second->originalObject->content_hash)->toBe(hash('sha256', 'version two'))
        ->and($first->id)->not->toBe($second->id);
});

test('another user cannot access a private document or its stored object', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $vault = app(VaultService::class)->createForUser($owner, 'Archive');

    $version = app(DocumentService::class)->uploadPrivateDocument($vault, fakeTextFile('private.txt', 'secret'), [
        'title' => 'Private contract',
    ]);

    expect(Gate::forUser($stranger)->allows('view', $version->document))->toBeFalse()
        ->and(Gate::forUser($stranger)->allows('view', $version->originalObject))->toBeFalse();
});

test('one public legal document can be attached to several vaults without duplication', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $firstVault = app(VaultService::class)->createForUser($firstUser, 'First');
    $secondVault = app(VaultService::class)->createForUser($secondUser, 'Second');

    $document = Document::create([
        'owner_vault_id' => null,
        'type' => 'law',
        'title' => 'Public act',
        'visibility' => 'public',
    ]);
    $version = app(DocumentService::class)->createVersion($document, null, ['version_label' => '2026']);
    $jurisdiction = Jurisdiction::create(['code' => 'UA', 'name' => 'Ukraine']);
    LegalInstrument::create([
        'document_id' => $document->id,
        'jurisdiction_id' => $jurisdiction->id,
        'instrument_type' => 'law',
        'official_number' => '1',
    ]);

    app(DocumentService::class)->attachPublicVersionToVault($firstVault, $version);
    app(DocumentService::class)->attachPublicVersionToVault($secondVault, $version);

    expect(Document::count())->toBe(1)
        ->and($firstVault->archiveEntries()->count())->toBe(1)
        ->and($secondVault->archiveEntries()->count())->toBe(1);
});

test('the system rejects linking a private stored object to another vault document', function () {
    Storage::fake('local');
    $firstVault = app(VaultService::class)->createForUser(User::factory()->create(), 'First');
    $secondVault = app(VaultService::class)->createForUser(User::factory()->create(), 'Second');
    $document = Document::create([
        'owner_vault_id' => $firstVault->id,
        'type' => 'contract',
        'title' => 'Contract',
        'visibility' => 'private',
    ]);
    $foreignObject = app(StoredObjectService::class)->storeBytes($secondVault, 'foreign bytes', 'text/plain', 'txt');

    app(DocumentService::class)->createVersion($document, $foreignObject, ['version_label' => 'bad']);
})->throws(RuntimeException::class, 'Stored object does not belong to the document vault.');

test('moving a stored file keeps the logical document id and sha256 verification detects tampering', function () {
    Storage::fake('local');
    $vault = app(VaultService::class)->createForUser(User::factory()->create(), 'Archive');
    $version = app(DocumentService::class)->uploadPrivateDocument($vault, fakeTextFile('proof.txt', 'stable bytes'), [
        'title' => 'Proof',
    ]);
    $documentId = $version->document_id;
    $object = $version->originalObject;

    $moved = app(StoredObjectService::class)->moveWithinConfiguredStorage($object);

    expect($version->refresh()->document_id)->toBe($documentId)
        ->and($version->document->id)->toBe($documentId)
        ->and(app(StoredObjectService::class)->verifyIntegrity($moved))->toBeTrue();

    Storage::disk('local')->put($moved->object_key, 'changed bytes');

    expect(app(StoredObjectService::class)->verifyIntegrity($moved))->toBeFalse();
});

test('consent event evidence cannot cross vault boundaries', function () {
    $firstVault = app(VaultService::class)->createForUser(User::factory()->create(), 'First');
    $secondVault = app(VaultService::class)->createForUser(User::factory()->create(), 'Second');
    $grantor = Party::create(['vault_id' => $firstVault->id, 'type' => 'person', 'display_name' => 'Grantor']);
    $grant = ConsentGrant::create([
        'vault_id' => $firstVault->id,
        'consent_type' => 'personal_data',
        'grantor_party_id' => $grantor->id,
        'purpose' => 'Account operation',
    ]);
    $event = ConsentEvent::create([
        'vault_id' => $firstVault->id,
        'consent_grant_id' => $grant->id,
        'event_type' => 'withdrawal_requested',
        'recorded_at' => now(),
        'source_type' => 'manual',
    ]);
    $evidence = Evidence::create([
        'vault_id' => $secondVault->id,
        'type' => 'manual_note',
        'description' => 'Wrong archive',
        'recorded_at' => now(),
    ]);

    app(ConsentIntegrityService::class)->attachEvidence($event, $evidence);
})->throws(RuntimeException::class, 'Evidence and consent event must belong to the same vault.');
