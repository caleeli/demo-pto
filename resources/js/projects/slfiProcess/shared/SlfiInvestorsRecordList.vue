<template>
  <div class="slfi-investors-grid">
    <table class="table table-sm table-striped">
      <thead>
        <tr>
          <th v-for="column in columns" :key="column">
            {{ column }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in rows" :key="index">
          <td v-for="column in columns" :key="column">
            {{ row[column] }}
          </td>
        </tr>
      </tbody>
    </table>
    <p v-if="!rows.length" class="text-muted mb-0">
      No investor rows loaded.
    </p>
  </div>
</template>

<script>
export default {
  name: 'SlfiInvestorsRecordList',
  props: {
    rows: {
      type: Array,
      default: () => [],
    },
    columns: {
      type: Array,
      default: () => ['SID_EMAIL', 'SID_DISPLAY_NAME', 'SID_INVESTOR_NAME', 'SID_STATUS'],
    },
  },
};
</script>
