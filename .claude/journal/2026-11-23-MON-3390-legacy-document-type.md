# A stored document-type value renamed without a migration breaks old stores

**Symptom:** automatic document creation failed for a store whose `document_type` setting predated a rename, with a misleading error (the log showed an unrelated last request). The document-type dropdowns (Settings, order view, pending orders) silently fell back to "Fatura" instead of the stored value.

**Cause:** the Invoice-Receipt value was renamed `invoiceReceipts` (v3.0.23) → `invoiceReceipt` (v4.0.05) and the stored setting was never migrated. `createDocument()`'s `switch` had no `default`, so an unmatched value fell through silently instead of failing loudly. Careful: the older `invoiceAndReceipt` (≤ v3.0.22) is **not** the same document — it issued an Invoice plus a related Receipt — so it must not be aliased to `invoiceReceipt`; it now fails with "Unknown document type" until the merchant re-picks a type.

**Rule:** before renaming a stored enum value, add the old value to `DocumentTypes::LEGACY_ALIASES` (read back through `Models\Settings::loadToContext()` and `DocumentTypes::normalize()`), and only alias values that produce the *same* document. A `switch` over a stored/user-controlled value needs a `default` that throws with the actual value.
