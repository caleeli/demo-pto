import SlfiProcessReviewInvestorsList from '../../../resources/js/projects/slfiProcess/reviewInvestorsList/SlfiProcessReviewInvestorsList.vue';

export default {
  title: 'Projects/SlfiProcess/ReviewInvestorsList',
  component: SlfiProcessReviewInvestorsList,
};

export const Default = () => ({
  components: { SlfiProcessReviewInvestorsList },
  template: '<SlfiProcessReviewInvestorsList :data="data" />',
  data() {
    return {
      data: {
        SLFI_FLAG_SUBMIT: 'YES',
        SLFI_INVESTORS_GRID: [
          { SID_EMAIL: 'a@example.com', SID_DISPLAY_NAME: 'A', SID_INVESTOR_NAME: 'Fund A', SID_STATUS: 'Pending' },
        ],
      },
    };
  },
});

export const SubmitHidden = () => ({
  components: { SlfiProcessReviewInvestorsList },
  template: '<SlfiProcessReviewInvestorsList :data="data" />',
  data() {
    return {
      data: {
        SLFI_FLAG_SUBMIT: 'NO',
        SLFI_INVESTORS_GRID: [],
      },
    };
  },
});
