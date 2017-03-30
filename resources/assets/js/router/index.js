import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

const dashboard = {
  name: 'dashboard',
  path: '/dashboard',
  component: () => import('../components/Dashboard.vue')
}

export default new Router({
  mode: 'history',
  routes: [dashboard]
})
