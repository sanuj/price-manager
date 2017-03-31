import Vue from 'vue'
import './bootstrap'

import App from './App.vue'
import store from './store'
import router from './router'

const app = new Vue({
  name: 'Exponent',
  router,
  store,
  ...App
}).$mount('#app')
