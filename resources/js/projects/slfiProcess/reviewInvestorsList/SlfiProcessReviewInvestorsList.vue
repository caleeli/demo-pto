<template>
  <div>
    <h1 class="h4">
      Review Investors List
    </h1>
    <SlfiInvestorsRecordList :rows="gridRows" />
    <div class="d-flex gap-2 mt-3">
      <button type="button" class="btn btn-outline-danger" @click="setFlag('CANCEL')">
        Cancel case
      </button>
      <button type="button" class="btn btn-outline-warning" @click="setFlag('RETURN')">
        Return
      </button>
      <button
        v-if="showSubmit"
        type="button"
        class="btn btn-primary"
        @click="setFlag('')"
      >
        Submit to tracking
      </button>
    </div>
  </div>
</template>

<script>
import SlfiInvestorsRecordList from '../shared/SlfiInvestorsRecordList.vue';

export default {
  name: 'SlfiProcessReviewInvestorsList',
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
    showSubmit() {
      return (this.data?.SLFI_FLAG_SUBMIT ?? 'YES') === 'YES';
    },
  },
  methods: {
    setFlag(flag) {
      const next = {
        ...this.data,
        SLFI_FLAG_CANCEL_CASE: flag,
      };
      this.$emit('input', next);
      if (typeof this.submit === 'function') {
        this.submit();
      }
    },
  },
};
</script>
