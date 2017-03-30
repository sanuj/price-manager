import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

const dashboard = {
  name: 'dashboard',
  path: '/dashboard',
  component: () => import('../components/Dashboard.vue')
}

const listings = {
  name: 'listings',
  path: '/listings',
  component: {}
}

const activity = {
  name: 'activity',
  path: '/activity',
  component: {}
}

const upload = {
  name: 'upload',
  path: '/upload',
  component: {}
}

const reports = {
  name: 'reports',
  path: '/reports',
  component: {}
}

const strategies = {
  name: 'strategies',
  path: '/strategies',
  component: {}
}

const marketplaces = {
  name: 'marketplaces',
  path: '/marketplaces',
  component: {}
}

export default new Router({
  mode: 'history',
  routes: [dashboard, listings, activity, upload, reports, strategies, marketplaces]
})
