<template>
  <div>
    <h1 class="h4">
      Show Investors Confirmation
    </h1>
    <SlfiInvestorsRecordList :rows="gridRows" />
    <button type="button" class="btn btn-secondary mt-3" @click="goBack">
      Return to upload
    </button>
  </div>
</template>

<script>
import SlfiInvestorsRecordList from '../shared/SlfiInvestorsRecordList.vue';

export default {
  name: 'SlfiProcessShowInvestorsConfirmation',
  components: { SlfiInvestorsRecordList },
  props: {
    data: {
      type: Object,
      default: () => ({}),
    },
    submit: {
      type: Function,
      default: null,
    },
  },
  computed: {
    gridRows() {
      return this.data?.SLFI_INVESTORS_GRID ?? [];
    },
  },
  methods: {
    goBack() {
      this.$emit('input', { ...this.data, SLFI_BACK_FORM: '' });
      if (typeof this.submit === 'function') {
        this.submit();
      }
    },
  },
};
</script>
