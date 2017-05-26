<template lang="html">
<div>
<line-chart v-bind="{ lines, labels, xlabels }" :width="400" :height="180"></line-chart>
</div>
</template>

<script>
import LineChart from './LineChart.vue'

export default {
  name: 'Dashboard',

  data: () => ({
    selected_listing_id: null, // Selected listing (ASIN/UID)
    listings: [], // List of watched listings

    lines: [
        [1,2,3,4,5,6,7,8],
        [2,14,6,8,1,12,5,16],
        [20,50,40]
    ], // Data points (prices)

    labels: ['one', 'two', 'three'], // Corresponding labels

    xlabels: [1,2,3,4,5,6,7,8],

  }),

  watch: {
    selected_listing_id (id) {
      // TODO: Fetch Snapshots/PriceHistory and update lines & labels.
    }
  },

  components: {LineChart}
}
</script>
