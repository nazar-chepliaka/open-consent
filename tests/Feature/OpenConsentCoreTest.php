<?php

use App\Models\ConsentEvent;
use App\Models\ConsentGrant;
use App\Models\ArchiveEntry;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Evidence;
use App\Models\Jurisdiction;
use App\Models\LegalInstrument;
use App\Models\Party;
use App\Models\StoredObject;
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

test('a document can be created without a generic type', function () {
    $vault = app(VaultService::class)->createForUser(User::factory()->create(), 'Archive');

    $document = Document::create([
        'owner_vault_id' => $vault->id,
        'title' => 'Untyped logical document',
        'visibility' => 'private',
    ]);

    expect($document->refresh()->title)->toBe('Untyped logical document');
});

test('initial upload creates the document chain without type or version label', function () {
    Storage::fake('local');
    Storage::fake('public');
    $owner = User::factory()->create();
    $vault = app(VaultService::class)->createForUser($owner, 'Archive');

    $response = $this->actingAs($owner)
        ->post(route('vaults.documents.store', $vault), [
            'title' => 'Private contract',
            'file' => fakeTextFile('contract.txt', 'private terms'),
        ]);

    $document = Document::query()->where('title', 'Private contract')->firstOrFail();
    $version = DocumentVersion::query()->where('document_id', $document->id)->firstOrFail();
    $object = StoredObject::query()->whereKey($version->original_object_id)->firstOrFail();

    $response->assertRedirect(route('documents.show', $document, absolute: false));

    expect(Document::query()->whereKey($document->id)->exists())->toBeTrue()
        ->and($version->version_label)->toBeNull()
        ->and($object->content_hash_algorithm)->toBe('sha256')
        ->and($object->content_hash)->toBe(hash('sha256', 'private terms'))
        ->and(ArchiveEntry::query()->where('document_version_id', $version->id)->where('vault_id', $vault->id)->exists())->toBeTrue()
        ->and(Storage::disk('local')->exists($object->object_key))->toBeTrue()
        ->and(Storage::disk('public')->exists($object->object_key))->toBeFalse();
});

test('unauthorized user cannot upload into another users vault', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $vault = app(VaultService::class)->createForUser($owner, 'Archive');

    $this->actingAs($stranger)
        ->post(route('vaults.documents.store', $vault), [
            'title' => 'Intrusion',
            'file' => fakeTextFile('intrusion.txt', 'nope'),
        ])
        ->assertForbidden();

    expect(Document::query()->where('title', 'Intrusion')->exists())->toBeFalse();
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

test('owner can access their vault over http', function () {
    $owner = User::factory()->create();
    $vault = app(VaultService::class)->createForUser($owner, 'Archive');

    $this->actingAs($owner)
        ->get(route('vaults.show', $vault))
        ->assertOk()
        ->assertSee('Archive');
});

test('another user cannot access a private vault over http', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $vault = app(VaultService::class)->createForUser($owner, 'Archive');

    $this->actingAs($stranger)
        ->get(route('vaults.show', $vault))
        ->assertForbidden();
});

test('owner can perform implemented authorized vault document actions', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $vault = app(VaultService::class)->createForUser($owner, 'Archive');

    $this->actingAs($owner)
        ->get(route('vaults.documents.create', $vault))
        ->assertOk();

    $response = $this->actingAs($owner)
        ->post(route('vaults.documents.store', $vault), [
            'title' => 'Private contract',
            'file' => fakeTextFile('contract.txt', 'private terms'),
        ]);

    $document = Document::query()->where('title', 'Private contract')->firstOrFail();
    $response->assertRedirect(route('documents.show', $document, absolute: false));

    $this->actingAs($owner)
        ->get(route('documents.show', $document))
        ->assertOk()
        ->assertSee('Private contract');

    $this->actingAs($owner)
        ->post(route('documents.versions.store', $document), [
            'version_label' => 'second',
            'file' => fakeTextFile('contract-v2.txt', 'updated terms'),
        ])
        ->assertRedirect(route('documents.show', $document, absolute: false));

    expect($document->versions()->count())->toBe(2);
});

test('authorization failure returns forbidden instead of missing controller authorize method', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $vault = app(VaultService::class)->createForUser($owner, 'Archive');
    $version = app(DocumentService::class)->uploadPrivateDocument($vault, fakeTextFile('private.txt', 'secret'), [
        'title' => 'Private contract',
    ]);

    $this->actingAs($stranger)
        ->get(route('vaults.documents.create', $vault))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->get(route('documents.show', $version->document))
        ->assertForbidden();
});

test('one public legal document can be attached to several vaults without duplication', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $firstVault = app(VaultService::class)->createForUser($firstUser, 'First');
    $secondVault = app(VaultService::class)->createForUser($secondUser, 'Second');

    $document = Document::create([
        'owner_vault_id' => null,
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
