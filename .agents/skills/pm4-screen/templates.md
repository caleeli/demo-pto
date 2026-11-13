# Screen Templates

A **screen template** is a row in `screen_templates` holding a full v2 export manifest of a screen (`manifest`) plus its CSS (`screen_custom_css`). Use it to create new screens, or to add a template's CSS, layout, or fields to an existing screen.

The AI Assistant screen generator is not covered.

Endpoints are under `/api/1.0`. Everything below was run on the check instance with an admin token (core `2026.15.3`), unless marked as code reading. Test templates and screens were deleted afterwards. User docs: [Screen Templates](https://docs.processmaker.com/docs/screen-templates).

## Model

| Field | Notes |
|-------|-------|
| `name` | `alpha_spaces`; unique per owner among private templates, and unique among public ones |
| `description`, `version` | `version` matches `^[0-9.]+$` |
| `screen_type` | FORM, DISPLAY, CONVERSATIONAL, EMAIL |
| `is_public` | `0` = private ("My Templates"), `1` = shared ("Shared Templates") |
| `is_default_template` | At most one per `screen_type` + `is_public`; preselected when creating a screen in Designer |
| `user_id` | Owner |
| `manifest` | v2 export of the source screen **and its dependents** (nested screens, watcher scripts, categories) |
| `screen_custom_css` | Source screen CSS |
| `editing_screen_uuid` | Hidden screen used to edit the template ([Edit](#edit-a-template)) |
| `media_collection` | `st-{uuid}-media`; thumbnails |

## List

```
GET /templates/screen?is_public=0&screen_type=FORM&filter=Invoice&per_page=10
```

`is_public=0` lists **my** private templates. `is_public=1` lists shared ones. `pmql` is supported.

## Save a screen as a template

```
POST /template/screen/{screenId}
{
  "asset_id": 123,
  "name": "Invoice Form Template",
  "description": "Header, two-column body, Submit",
  "version": "1.0",
  "screen_type": "FORM",
  "is_public": false,
  "screen_category_id": "41",
  "thumbnails": "[]"
}
```

- 200 `{model: {...}}`.
- The manifest is exported **now**. Later changes to the source screen do not reach the template.
- Same name again (same owner if private, any owner if public): **409** `{id, templateName, owner_id}`. Designer then offers "Update Template" or "Save as New".

## Create a screen from a template

```
POST /template/create/screen/{templateId}
{
  "templateId": 14,
  "title": "Approve Invoice Form",
  "description": "Approver reviews the invoice",
  "type": "FORM",
  "screen_category_id": "41",
  "is_public": false,
  "templateOptions": "[\"CSS\",\"Layout\",\"Fields\"]"
}
```

- 200 `{id, title}`. The new screen is published.
- **`templateOptions` must be a JSON *string*.** A JSON array returns **500** (`json_decode(): Argument #1 must be of type string`). Worse, the screen has already been imported by then, so an **orphan screen** named after the template's source screen (e.g. `Invoice Form 2`) is left behind. Delete it.
- Omitting `templateOptions` keeps everything.
- Options you leave out are removed from the new screen:

| Option | Keeps |
|--------|-------|
| `CSS` | `custom_css` (otherwise `null`) |
| `Layout` | `FormMultiColumn` and `b-table` containers. Without it, their fields are moved up onto the page |
| `Fields` | The field controls listed in `ProcessMaker/Templates/ScreenComponents.php` |

- The whole manifest is imported in `copy` mode. Nested screens and watcher scripts in the template become **new copies**; categories that already exist (same uuid) are reused.
- Send `existingAssets: [{uuid, mode}]` (with the body under `request`) to reuse or update existing copies instead.
- `defaultTemplateId` in the body also makes that template the default for `type` + `is_public`.

## Apply a template to an existing screen

```
POST /template/screen/{templateId}/apply
{ "screenId": 123, "currentScreenPage": 0, "templateOptions": ["CSS", "Fields"] }
```

Here `templateOptions` is an array.

- `currentScreenPage` is the page **array index**. A missing page returns 400.
- **Only the template's first page** is used.
- `Layout` / `Fields` **append** the template's items to the end of that page. Nothing is replaced.
  - With both, all items of the template's first page are appended.
  - With `Fields` only, containers are dropped and their fields appended.
- **`uuid`s are not regenerated.** Applying the same template twice (verified: 15 duplicated uuids) or applying a template made from this same screen puts duplicate `uuid`s on the page. Regenerate them afterwards.
- **Apply saves the screen, which publishes it** ([architecture.md](architecture.md#versions-and-drafts)). With `package-versions` it starts from the newest `screen_versions` row, which can be an unpublished draft.
- Internally the template is imported as a temporary screen, which is deleted afterwards. Code reading: dependents copied by that import (nested screens, scripts) are not deleted. The test template had none, so this was not observed.

### The CSS option rewrites the target's own CSS

`CSS` parses **both** the screen's CSS and the template's with a regex (`ScreenTemplateHelper::parseCss`), merges by selector (template properties win), and writes the result back. It damages CSS that was fine before:

| Input | Result |
|-------|--------|
| `[selector='x'] { margin: 0; color: red; }` | `color: red;` only. **Properties whose value is `0` are dropped** (verified on the instance) |
| `background: url(data:image/png;base64,AA);` | Cut at the first `;`: `url(data:image/png` |
| `@media (max-width: 600px) { .a { … } }` | Closing brace lost. Every later rule ends up inside the media query |
| `/* comment */ .a { … }` | The comment becomes part of the selector, so `.a` from the template is added as a second rule |

Avoid the `CSS` option on screens whose CSS has zeros, `@media`, data URIs, or comments. Merge CSS by hand instead ([config-schema.md](config-schema.md#custom-css-custom_css)).

## Edit a template

1. `GET /screen-builder/screen/{templateId}` imports the manifest as a hidden screen: `is_template = 1`, `asset_type = SCREEN_TEMPLATE`. The previous editing copy is deleted, and `editing_screen_uuid` points at the new one.
   - Screen Builder's template editor calls this.
   - Hidden screens are excluded from `GET /screens` (`nonSystem` scope).
2. Change that screen with the draft flow ([architecture.md](architecture.md#updating-config)).
3. `PUT /screens/{editingId}` publishes it. Core re-exports it and writes the new `manifest` and `screen_custom_css` into the template (`ScreenController::updateScreenTemplate`).

Name, description, category, visibility, and thumbnails: the `/screen-template/{id}/edit` settings page, or `PUT /template/settings/screen/{id}`.

## Publish, default, delete

| Action | Call | Effect |
|--------|------|--------|
| Share | `POST /template/screen/{id}/publish` | `is_public = 1`, `is_default_template = 0`. Needs `publish-screen-templates` |
| Make default | `defaultTemplateId` on create-from-template | Clears the old default for that type + visibility |
| Clear default | `POST /screens` with `"defaultTemplateId": null` and `is_public` | Clears the default for `type` + `is_public` |
| Delete | `DELETE /template/screen/{id}` | Owner or admin only (403 otherwise). Also deletes the editing screen. Screens created from the template are untouched |

## Export and import

- Export and import go through the pages `/screen-template/{id}/export` and `/screen-template/import`, or `POST /template/screen/do-import` with the file.
- `ScreenTemplatesExporter` sets the importing user as owner and `is_default_template = 0`. A name clash gets an incremented name (`handleDuplicatesByIncrementing`).
- A template's manifest carries its own copies of dependents, so importing a template does not need the original screens.

## Permissions

- Template routes go through `template-authorization`. It attaches `view|edit|archive|create|import|export-screen-templates` and checks ownership on delete.
- Core seeds only `publish-screen-templates` and `sync-screen-templates`. Everything here was tested as admin. Check a non-admin user's permissions before relying on these routes.

## Checklist

```
- [ ] Template lookup by name and is_public; never invent ids
- [ ] Create: templateOptions sent as a JSON string; on 500, delete the orphan screen
- [ ] Apply: page index exists; regenerate duplicate uuids; skip CSS if the target CSS has 0 values, @media, data URIs, comments
- [ ] Remember apply publishes the screen
- [ ] After create/apply, open Screen Builder and check the page renders
```
