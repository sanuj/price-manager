<template>
  <div class="custom-container">
    <h2>Price History and BuyBox Share</h2>
    <div class="container">
      <div class="row">
        <div class="col-2">
          Enter ASIN / SKU:
          <input v-model="sku_uid_id" placeholder="ASIN / SKU">
        </div>
        <div class="col-2">Start Date:<datepicker v-model="start_date" placeholder="Start Date"></datepicker></div>
        <div class="col-2">End Date:<datepicker v-model="end_date" placeholder="End Date"></datepicker></div>
        <div class="col-2"><button v-on:click="plot" class="btn btn-outline-primary plot-btn">Plot</button></div>
        <div class="col">
          <label class="custom-control custom-checkbox show-competitors">
            <input type="checkbox" class="custom-control-input" v-model="show_competitors">
            <span class="custom-control-indicator"></span>
            <span class="custom-control-description">Show Competitors</span>
          </label>
        </div>
      </div>
    </div>
    <line-chart v-bind="{ lines, lineLabels, lineX, linesBorderColors, linesBorderWidth, linesBackgroundColor }" :height="250"></line-chart>
    <bar-chart v-bind="{ barOne, barLabels, barX }" :height="250"></bar-chart>
  </div>
</template>

<script>
import LineChart from './LineChart.vue'
import BarChart from './BarChart.vue'
import axios from 'axios'
import moment from 'moment'

import Datepicker from 'vuejs-datepicker'

const randomColor = () => '#' + Math.floor(Math.random() * 16777215).toString(16)
const keys = obj => Object.keys(obj)
const values = obj => Object.values(obj)
const padWithZero = array => {
  let maxLen = Math.max(...array.map(row => row.length))
  return array.map(row => row.concat(Array(maxLen-row.length).fill(0)))
}
const transpose = array => array.reduce((p, n) => n.map((_, i) => [...(p[i] || []), n[i]]), [])

const getMyPriceSnaps =
  snap => snap['offers'].length ? snap['offers'][0]['price'] : null

const getCompPriceSnaps =
  snap => snap['competitors'].map(competitor => competitor['price'])

const getBuyBoxSnaps =
  snap => snap['offers'].length ? snap['offers'][0]['has_buy_box'] : null

const filterBuyBoxSnaps =
  bbThisDay => bbThisDay.filter(hasBB => hasBB === true || hasBB === false).map(hasBB => hasBB ? 1: 0)

const avgPrices =
  priceArray => {
    priceArray = priceArray.map(prices => prices.filter(x => x > 0))
    return priceArray.map(prices => prices.length ? Math.round(prices.reduce((x, y) => x+y)/prices.length) : 0)
  }

const avgBuyBoxShares =
  buyBoxSnaps => buyBoxSnaps.map(
    bbThisDay => bbThisDay.length ? Math.round(bbThisDay.reduce((x,y) => x+y) / bbThisDay.length * 100) : 0)

const processSnap =
  (snapsArray, f) => values(snapsArray).map(snaps => snaps.map(f))

export default {
  name: 'Dashboard',

  data: () => ({
    sku_uid_id: null, // Selected listing (ASIN/UID)
    show_competitors: null,
    start_date: null,
    end_date: null,
    
    lines: [[40, 39, 10, 40, 39, 80, 40], [60, 55, 32, 10, 2, 12, 53]],
    lineX: ['01-05', '02-05', '03-05', '04-05', '05-05', '06-05', '07-05'],
    lineLabels: ['Your Price', 'Average Price'],
    linesBorderColors: ['#2ECC40', '#05CBE1'],
    linesBorderWidth: [3, 1],
    linesBackgroundColor: ['transparent', 'transparent'],

    barOne: [10, 20, 50, 30, 40, 100, 80],
    barLabels: ['BuyBox Share'],
    barX: ['01-05', '02-05', '03-05', '04-05', '05-05', '06-05', '07-05'],

    snapsArray: null,
  }),

  watch: {
    show_competitors (state) {
      if(this.snapsArray)
        this.updatePriceChart()
    },
  },

  methods: {
    updatePriceChart (snapsArray = this.snapsArray) {
      let myPriceSnaps = processSnap(snapsArray, getMyPriceSnaps)
      let compPriceSnaps = processSnap(snapsArray, getCompPriceSnaps)

      let lines = [avgPrices(myPriceSnaps)]
      if(this.show_competitors)
        lines = lines.concat(transpose(padWithZero(compPriceSnaps.map(padWithZero).map(transpose).map(avgPrices))))
      else
        lines = lines.concat([avgPrices(compPriceSnaps.map(avgPrices))])
      
      this.lineX = keys(snapsArray).map(dateTime => moment(dateTime).format('DD-MM'))
      this.lines = lines
      this.linesBorderColors = ['#2ECC40', '#05CBE1'].concat(this.lines.map(() => randomColor()))
        .slice(0, this.lines.length)
      this.linesBorderWidth = [3].concat(Array(this.lines.length-1).fill(1))
      this.linesBackgroundColor = Array(this.lines.length).fill('transparent')
      this.lineLabels = ['Your Price'].concat(Array(this.lines.length-1).fill('Comp ').map((label, i) => label+(i+1)))
    },

    updateBuyBoxChart (snapsArray = this.snapsArray) {
      let buyBoxSnaps = processSnap(snapsArray, getBuyBoxSnaps).map(filterBuyBoxSnaps)
      this.barX = this.lineX
      this.barOne = avgBuyBoxShares(buyBoxSnaps)
    },

    plot () {
      axios.get('/analytics/snapshots', {
        params: {
          sku_uid_id: this.sku_uid_id,
          // start_date: '2017-05-01',
          start_date: moment(this.start_date).format('YYYY-MM-DD'),
          end_date: moment(this.end_date).format('YYYY-MM-DD'),
          // end_date: '2017-05-20',
        }
      }).then(response => response.data).then (snapsArray => {
        this.snapsArray = snapsArray
        this.updatePriceChart()
      this.updateBuyBoxChart()
      })
    },
  },

  components: {
    LineChart,
    BarChart,
    Datepicker
  }
}
</script>

<style>
  .custom-container {
    /*max-width: 1400px;*/
    /*margin:  100px;*/
    padding: 10px 50px 50px 50px;
  }
  .inline datepicker {
    display: inline;
  }
  .container {
    margin: 20px 0 25px 0;
  }
  .plot-btn {
    margin: 10px 0 0 30px;
  }
  .show-competitors {
    margin: 15px 0 0 20px;
  }
</style>
