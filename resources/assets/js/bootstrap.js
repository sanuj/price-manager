import Vue from 'vue'
import Bootstrap from 'bootstrap-for-vue'

import axios from 'axios'

axios.defaults.headers.common = {
  'X-CSRF-TOKEN': window.Laravel.csrfToken,
  'X-Requested-With': 'XMLHttpRequest'
};

Vue.use(Bootstrap)