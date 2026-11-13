# SLFI — frontend

Domain folder: `resources/js/projects/slfiProcess/` (camelCase).

| Storybook title | Vue root | Notes |
| --- | --- | --- |
| `Projects/SlfiProcess/UploadInvestorsConfirmation` | `SlfiProcessUploadInvestorsConfirmation.vue` | File upload, expiration date, investor type |
| `Projects/SlfiProcess/ShowInvestorsConfirmation` | `SlfiProcessShowInvestorsConfirmation.vue` | Grid preview after upload |
| `Projects/SlfiProcess/ReviewInvestorsList` | `SlfiProcessReviewInvestorsList.vue` | Editable grid, cancel/return/submit |
| `Projects/SlfiProcess/TrackingForm` | `SlfiProcessTrackingForm.vue` | Tracking grid, reminder / zip actions |

Child: `SlfiInvestorsRecordList.vue` (shared column layout).

Catalog: `resources/js/designer/public-screen-components.json` — each root lists nested `SlfiInvestorsRecordList` where used.

After Vue/catalog changes: `npm run prod` and `php artisan vendor:publish --tag=demo-pto --force`.
