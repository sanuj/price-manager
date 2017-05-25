<script>
import { Line } from 'vue-chartjs'

import { zip } from '../utils'

const randomColor = () => `#${'00000' + Math.floor(Math.random() * 16777215).toString(16)}`.slice(-6)

export default Line.extend({
  name: 'LineChart',

  props: {
    lines: {
      type: Array,
      required: true,
    },

    labels: {
      type: Array,
      required: true,
    },

    colors: {
      type: Array,
      default () {
        return this.lines.map(() => randomColor())
      },
    },
  },

  computed: {
    data () {
      return {
        datasets: zip(this.lines, this.labels, this.colors)
            .map((data, label, backgroundColor) => ({ data, label, backgroundColor }))
      }
    },

    options () {
      return {
        responsive: true,
        maintainAspectRatio: true,
      }
    }
  },

  mounted () {
    this.renderChart(this.data, this.options)
  }
})
</script>
