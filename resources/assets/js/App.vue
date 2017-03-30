<template lang="html">
<div class="app">
  <nav class="navbar navbar-toggleable-md sticky-top navbar-light bg-white bordered">
    <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse"
            data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"/>
    </button>
    <a class="navbar-brand" href="/">
      <!--<img src="./assets/logo.svg" alt="Exponent" height="33">-->
      Exponent
    </a>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav">
        <li class="nav-item">
          <router-link to="/dashboard" class="nav-link" active-class="active">Dashboard</router-link>
        </li>

        <li class="nav-item">
          <router-link to="/listings" class="nav-link" active-class="active">Listing</router-link>
        </li>

        <li class="nav-item">
          <router-link to="/activity" class="nav-link" active-class="active">Activity</router-link>
        </li>

        <li class="nav-item">
          <router-link to="/upload" class="nav-link" active-class="active">Upload</router-link>
        </li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <li class="dropdown nav-item" :class="{ show }" v-clickaway="() => show = false">
          <a href="#" class="nav-link dropdown-toggle"
             role="button" @click.prevent="show = !show">
            {{ user.name }} <span class="caret"/>
          </a>

          <div class="dropdown-menu dropdown-menu-right">
            <router-link class="dropdown-item" to="/reports">Reports</router-link>
            <router-link class="dropdown-item" to="/strategies">Strategies</router-link>
            <router-link class="dropdown-item" to="/marketplaces">Marketplaces</router-link>

            <div class="dropdown-divider"></div>

            <a class="dropdown-item" href="/logout" @click.prevent="$refs.logout.submit();">
              Logout
            </a>

            <form ref="logout" action="/logout" method="POST" style="display: none;">
              <input type="hidden" name="_token" :value="csrfToken">
            </form>
          </div>
        </li>
      </ul>
    </div>
  </nav>

  <router-view/>

  <div class="errors-container">
    <alert v-for="(error, index) in errors" :key="index"
           :type="error.type" class="mb-1"
           @dismiss="dismissError(index)">
      <span class="mr-2" v-html="error.message"/>
    </alert>
  </div>
</div>
</template>

<script lang="babel">
import { directive as clickaway } from 'vue-clickaway'
import { mapGetters } from 'vuex'

export default {
  data: () => ({ show: false }),

  computed: mapGetters(['user', 'errors', 'csrfToken']),

  directives: { clickaway }
}
</script>

<style lang="scss">
@import "../sass/variables";
@import "~bootstrap/scss/bootstrap";
@import "~bootstrap-for-vue/dist/scss/bootstrap-for-vue";

html {
  font-size: 14px;
}

.bg-white {
  background: white;
}

.navbar {
  border-bottom: 1px solid $border-color;
}
</style>
