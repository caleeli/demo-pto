import SlfiProcessUploadInvestorsConfirmation from '../../../resources/js/projects/slfiProcess/uploadInvestorsConfirmation/SlfiProcessUploadInvestorsConfirmation.vue';

export default {
  title: 'Projects/SlfiProcess/UploadInvestorsConfirmation',
  component: SlfiProcessUploadInvestorsConfirmation,
};

export const Default = () => ({
  components: { SlfiProcessUploadInvestorsConfirmation },
  template: '<SlfiProcessUploadInvestorsConfirmation :data="data" />',
  data() {
    return {
      data: {
        SLFI_CURRENT_DATE: '2026-11-13 10:00:00',
        SLFI_EXPIRATION_DATE: '2026-12-13 10:00:00',
        SLFI_SUBMITTER: 'Demo User',
        SLFI_INVESTOR_TYPE: '',
      },
    };
  },
});

export const Empty = () => ({
  components: { SlfiProcessUploadInvestorsConfirmation },
  template: '<SlfiProcessUploadInvestorsConfirmation :data="{}" />',
});
