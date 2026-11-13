<template>
  <div>
    <h1 class="h4">
      Investors Confirmation Tracking
    </h1>
    <SlfiInvestorsRecordList :rows="gridRows" :columns="trackingColumns" />
    <div class="d-flex gap-2 mt-3">
      <button type="button" class="btn btn-primary" :disabled="zipDisabled">
        Send completed investors PDFs
      </button>
      <button type="button" class="btn btn-secondary">
        Send reminder
      </button>
    </div>
  </div>
</template>

<script>
import SlfiInvestorsRecordList from '../shared/SlfiInvestorsRecordList.vue';

export default {
  name: 'SlfiProcessTrackingForm',
  components: { SlfiInvestorsRecordList },
  props: {
    data: {
      type: Object,
      default: () => ({}),
    },
  },
  computed: {
    gridRows() {
      return this.data?.SLFI_INVESTORS_GRID_TRACKING ?? [];
    },
    trackingColumns() {
      return [
        'SLFI_GRID_INVESTORS_EMAIL',
        'SLFI_GRID_INVESTORS_DISPLAY_NAME',
        'SLFI_GRID_INVESTORS_INVESTOR_NAME',
        'SLFI_GRID_INVESTORS_STATUS',
      ];
    },
    zipDisabled() {
      return Number(this.data?.SLFI_DISABLED_BUTTON_INVESTORS_ZIP ?? 0) === 1;
    },
  },
};
</script>
