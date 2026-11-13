# Lookup instance ids (do not invent)

Send Email BPMN stores **numeric** screen ids and **integer** email server indices. Guessing breaks send at runtime.

Auth: session cookie or `Authorization: Bearer <token>`. Base URL: local site host ([setup-processmaker](../setup-processmaker/SKILL.md)). Query contracts: [pm-api-contracts](../pm-api-contracts/SKILL.md).

## Email servers

Modeler uses:

```
GET /api/1.0/plugins/email/servers
```

Returns options for the **Email Server** dropdown. Value `""` or `0` = default mailer. Other values are server indices configured in Admin → Settings → Email.

Confirm a server works with **Test Email** in Settings before debugging process nodes.

## EMAIL screen (body type = screen)

```
GET /api/1.0/screens?filter=Invoice%20Notification&per_page=15
GET /api/1.0/screens/{id}
```

Pick a row with `type: "EMAIL"`. Use that `id` as `pm:config.screenRef`.

Example fixture: [pm4-screen/examples/email-minimal.json](../pm4-screen/examples/email-minimal.json).

## Users and groups (recipients)

```
GET /api/1.0/users?filter=Jane&per_page=15
GET /api/1.0/groups?filter=Finance&per_page=15
```

Use ids in `usersAndGroups` recipients. Look up on this instance; do not copy from another environment.

## Process (when creating a process with Send Email via API)

```
GET /api/1.0/process_categories?per_page=15
```

See [pm4-process](../pm4-process/SKILL.md).
