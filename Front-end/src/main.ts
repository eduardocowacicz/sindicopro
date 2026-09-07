import { VueQueryPlugin } from '@tanstack/vue-query'
import { createApp } from 'vue'

import App from '@/app/App.vue'
import { pinia } from '@/app/pinia'
import '@/assets/styles/app.css'
import { router } from '@/router'

createApp(App).use(pinia).use(VueQueryPlugin).use(router).mount('#app')
