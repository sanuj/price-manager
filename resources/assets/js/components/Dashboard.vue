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

    lines: [], // Data points (prices)
    labels: [], // Corresponding labels
  }),

  watch: {
    selected_listing_id (id) {
      // TODO: Fetch Snapshots/PriceHistory and update lines & labels.
    }
  },

  components: {LineChart}
}
</script>
