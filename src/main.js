import { createApp } from 'vue'
import { MotionPlugin } from 'motion-v'
import App from './App.vue'
import router from './router'
import { reveal } from './composables/useReveal'
import './assets/css/main.css'

const app = createApp(App)
app.use(router)
app.use(MotionPlugin)
app.directive('reveal', reveal)
app.mount('#app')
