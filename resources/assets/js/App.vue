<template lang="html">
<div class="app">
  <nav class="navbar navbar-toggleable-md sticky-top navbar-light bg-brand bordered">
    <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse"
            data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"/>
    </button>
    <a class="navbar-brand text-white" href="/">
      <!--<img src="./assets/logo.svg" alt="Exponent" height="33">-->
      Exponent
    </a>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav flex-auto">
        <li class="nav-item">
          <router-link to="/dashboard" class="nav-link text-white" active-class="active">Dashboard</router-link>
        </li>

        <li class="nav-item">
          <router-link to="/listings" class="nav-link text-white" active-class="active">Listing</router-link>
        </li>

        <li class="nav-item">
          <router-link to="/activity" class="nav-link text-white" active-class="active">Activity</router-link>
        </li>

        <li class="nav-item">
          <router-link to="/upload" class="nav-link text-white" active-class="active">Upload</router-link>
        </li>

        <li class="nav-item flex-auto text-right">
          <transition name="grow">
            <Typeahead v-if="searching"
                       v-model="search"
                       v-clickaway="() => !search && (searching = false)"
                       :suggestions="options"
                       class="ml-auto global-search" autofocus/>
          </transition>
          <InputButton v-if="!searching" theme="link" class="global-search-toggle text-white"
                       @click.native="searching = true">
            <icon type="search"/>
          </InputButton>
        </li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <li class="dropdown nav-item" :class="{ show }" v-clickaway="() => show = false">
          <a href="#" class="nav-link dropdown-toggle text-white"
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

<script>
import { directive as clickaway } from 'vue-clickaway'
import { mapGetters } from 'vuex'

export default {
  data: () => ({ show: false, search: '', searching: false }),

  computed: {

    options () {
      return []
    },

    ... mapGetters(['user', 'errors', 'csrfToken'])
  },

  directives: { clickaway }
}
</script>

<style lang="scss">
@import "../sass/variables";
@import "~bootstrap/scss/bootstrap";
@import "~font-awesome/scss/font-awesome";
@import "~bootstrap-for-vue/dist/scss/bootstrap-for-vue";

html {
  font-size: 14px;
}

.bg-brand {
  background: #d13e00;
}

.navbar {
  border-bottom: 1px solid $border-color;
  height: 40px;
}

.flex-auto {
  flex: 1;
}

// Local Styles
.grow {
  &-enter-active {
    animation: grow .2s;
    transform-origin: right;
  }

  &-leave-active {
    animation: shrink .2s;
  }
}

@keyframes grow {
  from {
    width: 0;
  }

  to {
    width: 100%;
  }
}

@keyframes shrink {
  from {
    width: 100%;
  }

  to {
    width: 0;
  }
}

.global-search + .global-search-toggle {
  display: none;
}
</style>
