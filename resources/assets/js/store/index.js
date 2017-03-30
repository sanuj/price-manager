import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store({
  state: {
    user: window.Laravel.user,
    errors: [],
    csrfToken: window.Laravel.csrfToken,
  },

  getters: {
    user: state => state.user,
    errors: state => state.errors,
    csrfToken: state => state.csrfToken,
  }
})