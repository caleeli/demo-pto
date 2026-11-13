# Lookup instance ids (do not invent)

Connector BPMN stores the **numeric** data source id. Resource names are the `endpoints` keys. Guessing `1` or copying ids from another environment breaks the call.

Auth: session cookie or `Authorization: Bearer <token>`. Base URL: local site host (see [setup-processmaker](../setup-processmaker/SKILL.md)). Query contracts: [pm-api-contracts](../pm-api-contracts/SKILL.md).

## Data Connector category (new connector)

```
GET /api/1.0/data_source_categories?per_page=15
```

Use an `ACTIVE` category `id` on `POST /api/1.0/data_sources`. Do not assume `1`. A seeded category named `Uncategorized` may exist; still use its looked-up id.

## Existing connector

```
GET /api/1.0/data_sources?filter=List%20Universities&per_page=15
```

Pick the row whose **name** matches. Use that `id` as `pm:config.dataSource`.

`GET /api/1.0/data_sources/{id}` returns `endpoints`. The resource name is each object key (`purpose`).

## Collection connector

Created with the Collection, in the category named **Generated**, `authtype` `OAUTH2_BEARER`. Same name as the Collection. Resources:

| purpose | Method |
|---------|--------|
| ListAll | GET |
| GetRecord | GET |
| CreateRecord | POST |
| UpdateRecord | PUT |
| DeleteRecord | DELETE |
| TruncateCollection | DELETE |

Look it up. Do not POST another connector for that Collection.

It is created synchronously when the Collection is created and `package-data-sources` is installed. Search by the Collection name (it may have a `_N` suffix). If it is missing, create one with a `ListAll` resource whose `url` is `{{__api_base_url__}}/api/1.0/collections/{id}/records`. Use a non-system category from the category lookup above. If the list is empty, create a category in Designer first.
