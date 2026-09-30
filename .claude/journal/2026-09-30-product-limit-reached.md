# Product create fails once the plan's product limit is reached

**Symptom:** with a full Moloni ON plan, every product create failed with a generic error. This covered product save with sync on, the product export tool, the manual "create Moloni product" tool, and missing order-line, shipping or fee products while generating a document. The logs showed "Error creating product in Moloni ON (ref)" or "Oops, an error was encountered...". The export retried every remaining product and filed each one as an error.

**Cause:** Moloni ON limits products per plan. `productCreate` then returns `errors: [{ msg: "Number of items is over the allowed limit.", field: "*" }]`, and `Curl::simple` rethrows it as a generic `APIExeption`, with the real message only in `getData()['received']`. The same count is available on the company query (`limits { resource remaining }`, the entry with `resource: "products"`). The plugin only selected `moduleId`/`active`, and `Context\Company` dropped every entry outside its module allow-list.

**Rule / what changed:**
- `Context\Company::canCreateProducts()` reads the `products` entry (`remaining > 0`). If there's no entry, it doesn't block and Moloni ON decides.
- `Exceptions\ProductsLimitReachedException` (a `ServiceException`, so existing catches still apply) holds the message plus `isApiError()`, which matches Moloni ON's exact message in an `APIExeption`'s data.
- Every create checks first and maps the API error: `MoloniProductSyncAbstract::insert()` (product save, export, manual tool, order lines), and `OrderShipping` / `OrderFees`, which throw a `DocumentError` with the same message.
- Product save, export and the manual tool log it at **warning** level. The export still tries each product on its own: nothing is remembered for the rest of the run, to keep it simple. Document generation still fails, now with the clear message. Using a generic product instead would change what gets invoiced.
- Translations: the two new strings were added to the `.pot` and `.po`, and to the `.l10n.php` and `.mo`. The `.mo` was rebuilt from its own catalog plus the new entries, because `msgfmt` wasn't available. Next time Loco regenerates these files, check that the new strings are still there.

**Not verified end to end** against a company at its product limit (no local plan in that state).
