# Open Consent Stage 1 Data Model

This implementation starts Open Consent as a server-first Laravel monolith. It uses Laravel migrations, Eloquent models, policies, Blade views, the local filesystem disk, and database queues from the stock framework stack.

## Implemented Tables

- Identity and archive: `users`, `vaults`, `vault_members`, `archive_entries`.
- Documents: `documents`, `document_versions`, `document_sections`, `document_sources`.
- Storage: `storage_profiles`, `stored_objects`.
- Public legal registry: `jurisdictions`, `legal_instruments`, `legal_provisions`.
- Relationships: `parties`, `relationships`, `relationship_parties`, `relationship_documents`.
- Consents and evidence: `consent_grants`, `consent_events`, `evidence`, `consent_event_evidence`.

Primary domain entities use UUIDs. Private archive data carries a `vault_id` or, for documents, `owner_vault_id`. Public registry documents use `documents.visibility = public` and `owner_vault_id = null`, so the same public `document_versions.id` can be added to several vaults through `archive_entries` without duplicating the logical document.

## Key Invariants

- Private stored objects are scoped to one vault and use object keys under `vaults/{vault_id}/objects/...`.
- `document_versions.original_object_id` points to `stored_objects`; document versions and evidence do not duplicate SHA-256 values.
- `stored_objects.content_hash` stores SHA-256 of the original bytes. It is indexed for duplicate detection but not unique.
- Adding a document version creates a new `document_versions` row and does not mutate earlier versions.
- Consent history is event-based. `consent_events.occurred_at` and `recorded_at` are separate fields.
- Evidence links to consent events through `consent_event_evidence`, allowing many-to-many support.
- Services reject cross-vault links that are not expressible as portable database checks, such as attaching another vault's stored object to a private document or another vault's evidence to a consent event.

## Implemented Services

- `VaultService`: creates a vault and owner membership in one transaction.
- `StoredObjectService`: stores bytes/uploads through Laravel Storage, records SHA-256, verifies integrity, and moves an object key without changing logical document IDs.
- `DocumentService`: uploads private documents, adds private versions, creates public versions, and attaches public versions to vault archives.
- `ConsentIntegrityService`: attaches evidence to consent events only inside the same vault.

## Implemented Policies

- `VaultPolicy`: membership-based view and editor/admin/owner write checks.
- `DocumentPolicy`: public documents are viewable; private documents require vault membership.
- `StoredObjectPolicy`: private file access requires vault membership.

## Deferred Decisions

- PostgreSQL-specific row-level security is not enabled yet.
- Export/import format is not implemented in this stage.
- AI analysis, browser extensions, local offline client, and synchronization are intentionally out of scope.
- Digital signatures, trusted timestamping, and independent authenticity verification remain future work; SHA-256 is only an integrity check.
