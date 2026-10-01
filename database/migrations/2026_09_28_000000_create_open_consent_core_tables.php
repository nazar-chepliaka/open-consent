<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaults', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['owner_id', 'name'], 'vaults_owner_name_unique');
        });

        Schema::create('vault_members', function (Blueprint $table) {
            $table->foreignUuid('vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32);
            $table->timestamps();

            $table->primary(['vault_id', 'user_id']);
        });

        Schema::create('storage_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_id')->nullable()->constrained('vaults')->cascadeOnDelete();
            $table->string('driver', 32);
            $table->string('name');
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->unique(['vault_id', 'name'], 'storage_profiles_vault_name_unique');
        });

        Schema::create('stored_objects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->foreignUuid('storage_profile_id')->nullable()->constrained('storage_profiles')->nullOnDelete();
            $table->string('object_key')->unique();
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type')->nullable();
            $table->string('content_hash_algorithm', 16)->default('sha256');
            $table->string('content_hash', 64);
            $table->timestamps();

            $table->index(['vault_id', 'content_hash'], 'stored_objects_vault_hash_index');
            $table->index(['vault_id', 'storage_profile_id'], 'stored_objects_vault_profile_index');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_vault_id')->nullable()->constrained('vaults')->cascadeOnDelete();
            $table->string('type', 48);
            $table->string('title');
            $table->string('visibility', 24);
            $table->timestamps();

            $table->index(['owner_vault_id', 'visibility'], 'documents_owner_visibility_index');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('original_object_id')->nullable()->constrained('stored_objects')->restrictOnDelete();
            $table->string('version_label')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version_label'], 'document_versions_document_label_unique');
            $table->index(['document_id', 'captured_at'], 'document_versions_document_captured_index');
        });

        Schema::create('document_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_version_id')->constrained('document_versions')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('document_sections')->cascadeOnDelete();
            $table->string('section_key')->nullable();
            $table->string('heading')->nullable();
            $table->longText('text')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['document_version_id', 'section_key'], 'document_sections_version_key_unique');
            $table->index(['document_version_id', 'parent_id'], 'document_sections_version_parent_index');
        });

        Schema::create('document_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_version_id')->constrained('document_versions')->cascadeOnDelete();
            $table->text('source_url')->nullable();
            $table->string('publisher')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->string('verification_status', 32)->default('unverified');
            $table->timestamps();

        });

        Schema::create('archive_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->foreignUuid('document_version_id')->constrained('document_versions')->cascadeOnDelete();
            $table->string('title');
            $table->timestamp('added_at');
            $table->timestamps();

            $table->unique(['vault_id', 'document_version_id'], 'archive_entries_vault_version_unique');
            $table->index(['vault_id', 'added_at'], 'archive_entries_vault_added_index');
        });

        Schema::create('jurisdictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable()->constrained('jurisdictions')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('legal_instruments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('jurisdiction_id')->constrained('jurisdictions')->restrictOnDelete();
            $table->string('instrument_type', 48);
            $table->string('official_number')->nullable();
            $table->timestamps();

            $table->unique(['jurisdiction_id', 'instrument_type', 'official_number'], 'legal_instruments_unique_identity');
        });

        Schema::create('legal_provisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instrument_id')->constrained('legal_instruments')->cascadeOnDelete();
            $table->foreignUuid('document_section_id')->constrained('document_sections')->cascadeOnDelete();
            $table->string('provision_key');
            $table->timestamps();

            $table->unique(['instrument_id', 'document_section_id'], 'legal_provisions_instrument_section_unique');
            $table->unique(['instrument_id', 'provision_key'], 'legal_provisions_instrument_key_unique');
        });

        Schema::create('parties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('display_name');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['vault_id', 'display_name'], 'parties_vault_name_index');
        });

        Schema::create('relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->string('type', 48);
            $table->string('status', 32)->default('asserted');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('source_note')->nullable();
            $table->timestamps();

            $table->index(['vault_id', 'type', 'status'], 'relationships_vault_type_status_index');
        });

        Schema::create('relationship_parties', function (Blueprint $table) {
            $table->foreignUuid('relationship_id')->constrained('relationships')->cascadeOnDelete();
            $table->foreignUuid('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('role', 64);
            $table->timestamps();

            $table->primary(['relationship_id', 'party_id', 'role']);
        });

        Schema::create('relationship_documents', function (Blueprint $table) {
            $table->foreignUuid('relationship_id')->constrained('relationships')->cascadeOnDelete();
            $table->foreignUuid('document_version_id')->constrained('document_versions')->cascadeOnDelete();
            $table->string('role', 64);
            $table->timestamp('applicable_from')->nullable();
            $table->timestamp('applicable_to')->nullable();
            $table->timestamps();

            $table->primary(['relationship_id', 'document_version_id', 'role']);
        });

        Schema::create('consent_grants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->foreignUuid('relationship_id')->nullable()->constrained('relationships')->nullOnDelete();
            $table->string('consent_type', 48);
            $table->foreignUuid('grantor_party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignUuid('data_subject_party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignUuid('recipient_party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignUuid('document_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->text('purpose');
            $table->json('scope')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['vault_id', 'consent_type'], 'consent_grants_vault_type_index');
        });

        Schema::create('consent_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->foreignUuid('consent_grant_id')->constrained('consent_grants')->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->foreignUuid('actor_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('recorded_at');
            $table->string('source_type', 32);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['vault_id', 'consent_grant_id', 'recorded_at'], 'consent_events_vault_grant_recorded_index');
        });

        Schema::create('evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vault_id')->constrained('vaults')->cascadeOnDelete();
            $table->string('type', 48);
            $table->foreignUuid('stored_object_id')->nullable()->constrained('stored_objects')->restrictOnDelete();
            $table->foreignUuid('source_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['vault_id', 'type'], 'evidence_vault_type_index');
        });

        Schema::create('consent_event_evidence', function (Blueprint $table) {
            $table->foreignUuid('consent_event_id')->constrained('consent_events')->cascadeOnDelete();
            $table->foreignUuid('evidence_id')->constrained('evidence')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['consent_event_id', 'evidence_id']);
        });

        $this->addCheckConstraints();
    }

    private function addCheckConstraints(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->addCheck('vault_members', 'vault_members_role_check', "role in ('owner', 'admin', 'editor', 'viewer')");
        $this->addCheck('storage_profiles', 'storage_profiles_driver_check', "driver in ('local', 's3')");
        $this->addCheck('stored_objects', 'stored_objects_hash_algorithm_check', "content_hash_algorithm = 'sha256'");
        $this->addCheck('stored_objects', 'stored_objects_size_check', 'size_bytes >= 0');
        $this->addCheck('documents', 'documents_visibility_check', "visibility in ('private', 'public')");
        $this->addCheck('documents', 'documents_visibility_owner_check', "(visibility = 'public' and owner_vault_id is null) or (visibility = 'private' and owner_vault_id is not null)");
        $this->addCheck('document_versions', 'document_versions_effective_range_check', 'effective_to is null or effective_from is null or effective_to > effective_from');
        $this->addCheck('document_sources', 'document_sources_verification_status_check', "verification_status in ('unverified', 'claimed', 'verified', 'rejected')");
        $this->addCheck('parties', 'parties_type_check', "type in ('person', 'organization', 'public_authority', 'service', 'unknown')");
        $this->addCheck('relationships', 'relationships_status_check', "status in ('asserted', 'active', 'ended', 'disputed', 'unknown')");
        $this->addCheck('relationships', 'relationships_date_range_check', 'ended_at is null or started_at is null or ended_at > started_at');
        $this->addCheck('relationship_documents', 'relationship_documents_date_range_check', 'applicable_to is null or applicable_from is null or applicable_to > applicable_from');
        $this->addCheck('consent_grants', 'consent_grants_date_range_check', 'expires_at is null or granted_at is null or expires_at > granted_at');
        $this->addCheck('consent_events', 'consent_events_event_type_check', "event_type in ('granted', 'withdrawal_requested', 'withdrawal_acknowledged', 'recipient_reported_completion', 'expired', 'corrected', 'other')");
        $this->addCheck('consent_events', 'consent_events_source_type_check', "source_type in ('manual', 'document', 'email', 'api', 'import', 'system')");
        $this->addCheck('evidence', 'evidence_type_check', "type in ('screenshot', 'email', 'receipt', 'document', 'api_response', 'manual_note', 'other')");
    }

    private function addCheck(string $table, string $name, string $expression): void
    {
        DB::statement("alter table {$table} add constraint {$name} check ({$expression})");
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_event_evidence');
        Schema::dropIfExists('evidence');
        Schema::dropIfExists('consent_events');
        Schema::dropIfExists('consent_grants');
        Schema::dropIfExists('relationship_documents');
        Schema::dropIfExists('relationship_parties');
        Schema::dropIfExists('relationships');
        Schema::dropIfExists('parties');
        Schema::dropIfExists('legal_provisions');
        Schema::dropIfExists('legal_instruments');
        Schema::dropIfExists('jurisdictions');
        Schema::dropIfExists('archive_entries');
        Schema::dropIfExists('document_sources');
        Schema::dropIfExists('document_sections');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('stored_objects');
        Schema::dropIfExists('storage_profiles');
        Schema::dropIfExists('vault_members');
        Schema::dropIfExists('vaults');
    }
};
