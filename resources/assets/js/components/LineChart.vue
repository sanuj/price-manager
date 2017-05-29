<script>
import {Line} from 'vue-chartjs'
import { zip } from '../utils'

const randomColor = () => '#' + Math.floor(Math.random() * 16777215).toString(16)

export default Line.extend({
  name: 'LineChart',

  props: {
  	lines: {
      type: Array,
      required: true,
    },

    linesBorderColors: {
    	type: Array,
    	default () {
    		return this.lines.map(() => randomColor())
    	}
    },

    linesBorderWidth: {
    	type: Array,
    	default() {
    		return Array(this.lines.length-1).fill(1)
    	}
    },

    linesBackgroundColor: {
    	type: Array,
    	default() {
    		return Array(this.lines.length).fill('transparent')
    	}
    },

    lineLabels: {
    	type: Array,
    	required: true,
    },

    lineX: {
    	type: Array,
    	required: true,
    }
  },

  computed: {
  	data () {
      return {
        labels: this.lineX,
        datasets: zip(this.lines, this.lineLabels, this.linesBorderWidth, this.linesBorderColors, this.linesBackgroundColor).
      	  map((data, label, borderWidth, borderColor, backgroundColor) =>
      	  	({data, label, borderWidth, borderColor, backgroundColor}))
      }
	},

	options () {
		return {responsive: true, maintainAspectRatio: false}
	}
  },

  mounted () {
    this.renderChart(this.data, this.options)
  },

  watch: {
    data: function () {
      this._chart.destroy()
      this.renderChart(this.data, this.options)
    }
  }
})
</script>
