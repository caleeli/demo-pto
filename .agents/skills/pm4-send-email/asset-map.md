# Where things live

Paths relative to `connector-send-email` and ProcessMaker core.

## Package

| Item | Value |
|------|-------|
| Composer | `processmaker/connector-send-email` |
| Short name | `connector-send-email` |
| BPMN implementation | `connector-send-email/processmaker-communication-email-send` |
| Modeler node id | `processmaker-communication-email-send` |

## Runtime

| Path | What |
|------|------|
| `src/PluginServiceProvider.php` | Registers service implementation |
| `src/EmailSendImplementation.php` | `run()` entry from workflow engine |
| `src/Jobs/SendEmail.php` | Mustache, recipients, body render, dispatch mail |
| `src/RequestRecipients.php` | Resolves recipient types to addresses |
| `src/EmailBuilder.php` | Builds Symfony Email; `Screen::findOrFail` for screen body |
| `resources/js/connectors/email/send/index.js` | Modeler node definition and default config |
| `resources/js/connectors/email/send/EmailOptions.vue` | Inspector: recipients, server, subject, body |
| `src/ImportExport/ProcessExporterExtension.php` | Export/import screens + rewrite `screenRef`, `emailServer` |

## API used by Modeler

| Method + path | Purpose |
|---------------|---------|
| `GET /api/1.0/plugins/email/servers` | Email Server dropdown |

No CRUD API for the connector itself — config is BPMN-only.

## Admin (not this skill)

Outbound SMTP/Gmail/SES: Admin → Settings → Email. Package adds the settings UI addon (`email::settings.email-connector`).

## Related packages

| Package | Role |
|---------|------|
| `connector-send-email` | Send Email node + EMAIL screen type |
| `package-actions-by-email` | Actions by Email (wait for reply) — [pm4-action-by-email](../pm4-action-by-email/SKILL.md) |
| `package-savedsearch` | Can also use EMAIL screens for reports |

## Core

Form Task notifications reuse Send Email rendering for EMAIL `screenRef` values in `pm:config.email_notifications` on `bpmn:task`.
