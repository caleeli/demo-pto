# Wire a Send Email node

Needs `processmaker/connector-send-email` installed and a working outbound email server. Do not start a request unless the user asked.

## Path A — Designer

1. Modeler → **Send Email** (Communication category) → place on diagram.
2. **Configuration**: To/Cc/Bcc, Email Server, Subject, Body (Plain Text or Display Screen).
3. Optional: Attachments.
4. **Save + Publish** the process.

## Path B — BPMN API

When editing process BPMN via API ([pm4-process](../pm4-process/create-edit.md)):

1. Look up email server index and EMAIL screen id if needed ([lookup.md](lookup.md)).
2. Set `pm:config` on the service task — [config-schema.md](config-schema.md).
3. Use examples: [examples/pm-config-text.json](examples/pm-config-text.json), [examples/send-email-text.bpmn.xml](examples/send-email-text.bpmn.xml).
4. PUT process BPMN; Publish in Designer if required.

There is no separate REST asset for Send Email — config lives entirely on the BPMN node (unlike Data Connectors or FlowGenies).

## EMAIL screen body

1. Create EMAIL screen ([pm4-screen](../pm4-screen/SKILL.md), [examples/email-minimal.json](../pm4-screen/examples/email-minimal.json)).
2. Publish the screen.
3. Set `type: "screen"` and `screenRef` to the screen id.

## Plain text body

Set `type: "text"` and `textBody` with Mustache. Example:

```json
{
  "emailServer": "",
  "type": "text",
  "subject": "Request {{_request_id}} update",
  "textBody": "Hello {{requester_name}},\n\nYour request was updated.",
  "screenRef": null,
  "toRecipients": [{ "type": "requester" }],
  "ccRecipients": [],
  "bccRecipients": [],
  "attachments": []
}
```

## Attachments example

Request variable from File Upload:

```json
"attachments": [{ "source": "variable", "value": "contract_pdf" }]
```

## Editing an existing node

1. GET process BPMN; find the service task by `implementation`.
2. Parse `pm:config`; update recipients, subject, or body.
3. Save + Publish.
4. Static check [verify.md](verify.md).
