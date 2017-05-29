<script>
import {Bar} from 'vue-chartjs'

export default Bar.extend({
  name: 'BarChart',
  
  props: {
    barOne: {
      type: Array,
      required: true,
    },

    barLabels: {
      type: Array,
      required: true,
    },

    barX: {
      type: Array,
      required: true,
    }
  },

  computed: {
    data () {
      return {
        labels: this.barX,
        datasets: [
          {
            label: this.barLabels[0],
            backgroundColor: '#1EBC30',
            data: this.barOne
          }
        ]
      }
    },
  },
  
  mounted () {
    this.renderChart(this.data, {responsive: true, maintainAspectRatio: false})
  },

  watch: {
    data: function () {
      this._chart.destroy()
      this.renderChart(this.data, {responsive: true, maintainAspectRatio: false})
    }
  },
})
</script>
