<template>
  <div class="custom-container">
    <h2>Price History and BuyBox Share</h2>
    <p>
      Enter listing id:
      <input v-model="selected_listing_id">
      <button v-on:click="plot">Plot</button>
      <input type="checkbox" id="show-competitors" v-model="show_competitors">
      <label for="show-competitors">Show Competitors</label>
    </p>
    <line-chart v-bind="{ lines, lineLabels, lineX, linesBorderColors, linesBorderWidth, linesBackgroundColor }"></line-chart>
    <bar-chart v-bind="{ barOne, barLabels, barX }"></bar-chart>
  </div>
</template>

<script>
import LineChart from './LineChart.vue'
import BarChart from './BarChart.vue'
import axios from 'axios'

const randomColor = () => '#' + Math.floor(Math.random() * 16777215).toString(16)
const keys = obj => Object.keys(obj)
const values = obj => Object.values(obj)
const removeTimestampFromDate = dateTime => dateTime.split(' ')[0]
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
    selected_listing_id: null, // Selected listing (ASIN/UID)
    show_competitors: null,
    listings: [], // List of watched listings
    
    lines: [[40, 39, 10, 40, 39, 80, 40],[60, 55, 32, 10, 2, 12, 53]],
    lineX: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
    lineLabels: ['Your Price', 'Average Price'],
    linesBorderColors: ['#FC2525', '#05CBE1'],
    linesBorderWidth: [3, 1],
    linesBackgroundColor: ['transparent', 'transparent'],

    barOne: [10, 20, 50, 30, 40, 100, 80],
    barLabels: ['BuyBox Share'],
    barX: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],

    snapsArray: null,
  }),

  watch: {
    show_competitors (state) {
      this.updatePriceChart()
    }
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
      
      this.lineX = keys(snapsArray).map(removeTimestampFromDate)
      this.lines = lines
      this.linesBorderColors = ['#FC2525', '#05CBE1'].concat(this.lines.map(() => randomColor()))
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
          marketplace_listing_id: this.selected_listing_id,
          start_date: '2017-05-01',
          end_date: '2017-05-20',
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
    BarChart
  }
}
</script>

<style>
  .custom-container {
    max-width: 1400px;
    margin:  0 auto;
  }
</style>