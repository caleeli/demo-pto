# SLFI — database

Prefix: `slfi_`. Maps PM3 workspace tables from triggers and plugin code.

| PM3 table | PM4 table | Purpose |
| --- | --- | --- |
| `PMT_SLFI_EXTERNAL_FORM_VARIABLES` | `slfi_external_form_variables` | Key/value config (e.g. `SLFI_CONTACT_INFORMATION`) |
| `PMT_SLFI_MIN_EXPIRATION_DATE` | `slfi_min_expiration_date` | Active row: days until expiration default |
| `PMT_SLFI_INVESTORS_DATA` | `slfi_investors_data` | Investor rows per case (`app_uid`, email, status, …) |
| `PMT_SLFI_INVESTOR_EMAILS` | `slfi_investor_emails` | Outbound email queue (cron in PM3) |
| `PMT_SLFI_INVESTORS_ZIP_MESSAGE_HISTORY` | `slfi_investors_zip_message_history` | Zip send throttle |

Script tasks use `InvestorRepositoryPort` + `ConfigurationRepositoryPort` (RedBean adapters), not Laravel models.

JSON schemas: `src/Projects/SlfiProcess/Domain/Schemas/`.
