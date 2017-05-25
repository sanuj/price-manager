<template lang="html">
<div>
<LineChart v-bind="{ lines, labels }"></LineChart>
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
        [2,4,6,8,10,12,14,16],
    ], // Data points (prices)
    labels: [
        'one',
        'tow'
    ], // Corresponding labels
  }),

  watch: {
    selected_listing_id (id) {
      // TODO: Fetch Snapshots/PriceHistory and update lines & labels.
    }
  },

  components: {LineChart}
}
</script>
