<script>
import { Line } from 'vue-chartjs'

import { zip } from '../utils'

const randomColor = () => '#' + Math.floor(Math.random() * 16777215).toString(16)

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

    xlabels: {
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
        labels: this.xlabels,
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
