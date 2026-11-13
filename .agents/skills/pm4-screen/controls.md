# Controls

A control is one item in a page's `items` ([config-schema.md](config-schema.md#items)). Its `component` decides how it renders. `editor-component` and `editor-control` decide how Screen Builder edits it. **Clone controls from a screen saved by Screen Builder on the same instance** ([screen-config.md](screen-config.md)). The tables below are for reading and editing `config`, not for typing items from scratch.

Sources: screen-builder `src/form-builder-controls.js` (3.8.43); the package `screen-builder-{type}-components.js` files; a scan of all 235 screens on the check instance.

## Find what exists before cloning

1. **Is the control available for this type?** Each type only gets some controls ([screen-types.md](screen-types.md#how-a-type-gets-its-controls)). Package controls also need their package installed.
2. **Is there a source item to clone?** Page through `GET /api/1.0/screens?per_page=100&page=N` (the list includes `config`) and walk the items recursively ([config-schema.md](config-schema.md#containers)) looking for the `component`. Clone the first match.
3. **No match:** add the control once in Screen Builder, save, then clone from that screen. Do not hand-write it.

## Core controls

`component` / `editor-component` / `editor-control` are the values Screen Builder saves.

| Screen Builder label (group) | `component` | `editor-component` | `editor-control` | Main `config` keys |
|------------------------------|-------------|--------------------|------------------|--------------------|
| Line Input (Input Fields) | `FormInput` | `FormInput` | `FormInput` | `name`, `label`, `dataFormat`, `validation`, `placeholder`, `helper`, `readonly`, `defaultValue` |
| Textarea | `FormTextArea` | `FormTextArea` | `FormTextArea` | `name`, `label`, `rows`, `richtext`, `validation` |
| Select List | `FormSelectList` | `FormSelectList` | `FormSelectList` | `name`, `label`, `options` ([below](#select-list-options)), `validation` |
| Checkbox | `FormCheckbox` | `FormCheckbox` | `FormCheckbox` | `name`, `label`, `initiallyChecked`, `toggle` |
| Date Picker | `FormDatePicker` | `FormDatePicker` | `FormDatePicker` | `name`, `label`, `dataFormat` (`date` or `datetime`), `minDate`, `maxDate` |
| Submit Button | `FormButton` | `FormButton` | **`FormSubmit`** | `label`, `event`, `name`, `fieldValue`, `variant`, `defaultSubmit`, `handler`, `variablesToSubmit` ([buttons](#buttons)) |
| Page Navigation (Navigation) | `FormButton` | `FormButton` | `PageNavigation` | `label`, `event: "pageNavigate"`, `eventData` (target page index) |
| Rich Text (Content Fields) | `FormHtmlViewer` | `FormHtmlEditor` | `FormHtmlEditor` | `content` (HTML + Mustache), `renderVarHtml` |
| Multicolumn / Table | `FormMultiColumn` | `MultiColumn` | `MultiColumn` | `options` (column widths); `items` is an array of columns |
| Loop | `FormLoop` | `Loop` | `Loop` | `name`, `settings` |
| Dynamic Panel | `FormDynamicPanel` | `DynamicPanel` | `DynamicPanel` | `name`, `settings` |
| Record List | `FormRecordList` | `FormText` | `FormRecordList` | `name`, `label`, `fields`, `editable`, `form` (screen id of the add/edit form), `source` |
| Image | `FormImage` | `FormImage` | `FormImage` | `name`, `image` (uploaded image), `renderImage` (take the image from the variable `name`), `height`, `width` |
| Nested Screen | `FormNestedScreen` | `FormNestedScreen` | `FormNestedScreen` | `screen` (child id) |
| File Upload (Files) | `FileUpload` | `FileUpload` | `FileUpload` | `name`, `label`, `multipleUpload`, `accept`, `validation` |
| File Download | `FileDownload` | `FormText` | `FileDownload` | `name`, `label` |
| Bootstrap Component (Advanced) | `BFormComponent` | `BFormComponent` | `BFormComponent` | `bootstrapComponent`, `bootstrapConfig` |
| Bootstrap Wrapper | `BWrapperComponent` | `BWrapperComponent` | `BWrapperComponent` | `bootstrapComponent`, `bootstrapConfig`, `form` |
| Link URL (Content Fields) | `LinkButton` | | | `label`, `linkUrl`, `variant`, `variantStyle` |
| Collection Record Control | `FormCollectionRecordControl` | same | same | `collection.collectionId`, `record`, `collectionmode` |
| Collection Record View | `FormCollectionViewControl` | same | same | `collection.collectionId`, `record` |
| User Avatar (Dashboards) | `FormAvatar` | `FormAvatar` | `FormAvatar` | `height`, `width` |
| List Table | `FormListTable` | | | `listOption` |
| Analytics Chart | `FormAnalyticsChart` | | | `listChartOption` |
| Case Progress Bar | `CaseProgressBar` | same | same | |

Every control also has the global properties (Visibility Rule, Device Visibility, CSS Selector Name, …) in [config-schema.md](config-schema.md#properties-every-control-has).

`dataFormat` (inspector: Data Type) on Line Input:

- Allowed values: `string` (Text), `int` (Integer), `currency`, `percentage`, `float` (Decimal), `datetime`, `date`, `password`.
- It sets the input mask and the type written to request data.
- Values seen on the instance: `string` 417, `int` 76, `currency` 1.

## Buttons

Three buttons share `component: FormButton`. They differ in `editor-control` and `event`:

| Button | `editor-control` | `event` | Click does |
|--------|------------------|---------|------------|
| Submit Button, type "Submit Button" | `FormSubmit` | `submit` | Sets `name` = `fieldValue` (if `name` is set), runs `handler`, then submits the task |
| Submit Button, type "Regular Button" | `FormSubmit` | `script` | Toggles `name` between `fieldValue` (default `"1"`) and `null`, runs `handler`. Does not submit; watchers can react to the change |
| Page Navigation | `PageNavigation` | `pageNavigate` | Runs `handler`, opens page `config[eventData]` |

- **On the check instance, all 1,487 Submit Button items have `editor-control: "FormSubmit"`**: 161 with `submit`, 1,326 with `script`.
- `handler` (Click Handler) is JavaScript run in the browser on click. `handlerSecurityEnabled` (default `true` when missing) runs it in a sandboxed worker. Set it to `false` only if the handler needs full page access.
- A FORM that completes a task needs a button with `event: "submit"`.
- `variablesToSubmit` restricts the submitted data to the listed variables. When it is empty, all data is submitted.

## Select List options

`config.options.dataSource` selects where the options come from:

| `dataSource` | Screen Builder label | Keys that matter |
|--------------|----------------------|------------------|
| `provideData` | Provide Values | `optionsList: [{"value", "content"}]` |
| `dataObject` | Request Data | `dataName` (path to the array in request data), `key`, `value` (Mustache template for the label) |
| `dataConnector` | Data Connector | `selectedDataSource` (**Data Connector id**), `selectedEndPoint` (endpoint name), `dataName` (e.g. `response.data`), `key`, `value`, `pmqlQuery` |
| `collection` | Collection | `collectionOptions.collectionId`, `pmqlQuery` |

Common keys:

- `renderAs`: `dropdown` or `checkbox`.
- `allowMultiSelect`.
- `valueTypeReturned`: `single` (the key value) or `object` (the whole option).

With `dataConnector`, `selectedDataSource` must be a connector id that exists on this instance. The data-sources exporter reads it from `config.options.selectedDataSource`.

## Validation rules

`config.validation` is an **array** of rule objects (some old screens store a string such as `"required"`). The value uses Laravel rule syntax and is checked in the browser:

```json
"validation": [
  { "value": "required", "content": "Required", "helper": "…" },
  { "value": "between:0,10", "field": "between:", "content": "Between Min & Max", "configs": [ … ] }
]
```

Rules offered by Screen Builder: `accepted`, `alpha`, `alpha_num`, `between:min,max`, `email`, `in:a,b`, `max:n`, `min:n`, `not_in:a,b`, `required`, `required_if:field,value`, `required_unless:field,value`, `same:field`, `url`, `custom_date`, `after:date`, `after_or_equal:date`, `before:date`, `before_or_equal:date`, `regex:pattern`.

- Clone a rule object from an existing item: rules with parameters carry `field` and `configs`.
- Validation runs in the browser renderer. Core does not re-check screen rules when a task is completed through the API, so data written by the API or by scripts is not validated against them.

## Visibility rules

- `config.conditionalHide` holds an expr-eval expression. The control is **shown when it is true**. An empty value means always shown.
- `config.deviceVisibility` hides the control on desktop or on mobile.
- Syntax: [config-schema.md](config-schema.md#properties-every-control-has).

## Package controls

Present only when the package is installed **on the instance that edits and renders the screen**. Moving a screen to an instance without the package leaves those items unrendered.

| Package | `component` (label) | Types | Notes |
|---------|---------------------|-------|-------|
| `package-files` | `Viewer` (File Preview) | FORM, DISPLAY | `name`, file name |
| `package-photo-video` | `PhotoVideo` (Photo/Video) | FORM | Camera capture |
| `package-webentry` | `Captcha` | FORM | Google reCAPTCHA site key; for Web Entry |
| `package-ai` | `AiSection` (AI Generated) | FORM | Prompt-driven section |
| `package-savedsearch` | `SavedSearchChart` (Saved Search Chart) | FORM (Advanced), DISPLAY (Dashboards) | Points at a saved search chart |
| `package-conversational-forms` | `FormInlineAction`, `FormBreak`, `FormJump` | CONVERSATIONAL | [config-schema.md](config-schema.md#conversational) |
| `connector-send-email` | none new | EMAIL | Limits EMAIL to Rich Text, Record List, Link URL, Submit ([screen-types.md](screen-types.md#email)) |
| `package-collections` | none new | | Supplies data for the core Collection controls and for `collection` Select Lists, and exports the collections they use |
| `package-versions` | none new | all | Replaces Save with Publish / draft buttons in Screen Builder |
| `package-vocabularies` | none new | all | Adds a vocabulary validator to the preview |
| `package-plg` | `VueComponentRenderTemplate` (Vue component Editor), `UploadFile` (TCE File Upload) | FORM, DISPLAY | `contentVue` / `handlerVue`; see the package's `create-process-screen` skill |

`package-pm-blocks` and `package-decision-engine` add no screen controls. `package-data-sources` adds no control; it powers `dataConnector` Select Lists and data source watchers ([watchers.md](watchers.md)).

## References that travel on export

Controls that point at other assets ([architecture.md](architecture.md#export-and-import)):

| Control | Path in item | Points to | Exported by |
|---------|--------------|-----------|-------------|
| `FormNestedScreen` | `config.screen` | Screen | core (pages, tables, and loops are searched) |
| `FormRecordList` | `config.form` | Screen (add/edit form) | **not exported**; check it exists on the target and fix the id |
| `FormSelectList` | `config.options.selectedDataSource` | Data Connector | `package-data-sources` |
| `FormSelectList` | `config.options.collectionOptions.collectionId` | Collection | `package-collections` |
| `FormCollectionRecordControl`, `FormCollectionViewControl` | `config.collection.collectionId` | Collection | `package-collections` |
| `FormRecordList` | `config.source.collectionFields.collectionId` | Collection | `package-collections` |

On import, a `FormNestedScreen` whose child was not in the package gets `config.screen: null` (`ScreenExporter::associateNestedScreens()`). After an import, check that no nested screen is `null`.
