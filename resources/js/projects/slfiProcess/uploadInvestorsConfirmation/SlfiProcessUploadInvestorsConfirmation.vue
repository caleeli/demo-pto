<template>
  <div class="slfi-upload-form">
    <h1 class="h4">
      Submit Investors Confirmation Form
    </h1>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Date</label>
        <input v-model="localData.SLFI_CURRENT_DATE" class="form-control" type="text" readonly>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Expiration Date</label>
        <input v-model="localData.SLFI_EXPIRATION_DATE" class="form-control" type="datetime-local">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Name</label>
        <input v-model="localData.SLFI_SUBMITTER" class="form-control" type="text" readonly>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Investor type</label>
      <select v-model="localData.SLFI_INVESTOR_TYPE" class="form-control">
        <option value="">
          Select…
        </option>
        <option value="LP">
          Limited partner
        </option>
        <option value="GP">
          General partner
        </option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Investors file (CSV)</label>
      <input
        class="form-control"
        type="file"
        accept=".csv"
        @change="onFileChange"
      >
    </div>
    <button type="button" class="btn btn-primary" @click="onSubmit">
      Submit
    </button>
  </div>
</template>

<script>
export default {
  name: 'SlfiProcessUploadInvestorsConfirmation',
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
  data() {
    return {
      localData: { ...this.data },
    };
  },
  watch: {
    data: {
      deep: true,
      handler(next) {
        this.localData = { ...(next || {}) };
      },
    },
    localData: {
      deep: true,
      handler(next) {
        this.$emit('input', next);
      },
    },
  },
  methods: {
    onFileChange(event) {
      const file = event.target.files?.[0];
      if (!file) {
        return;
      }
      this.$set(this.localData, 'SLFI_UPLOAD_INVESTORS', [{ name: file.name }]);
    },
    onSubmit() {
      if (typeof this.submit === 'function') {
        this.submit();
      }
    },
  },
};
</script>
