import "../css/app.css"
import "./bootstrap"

import { createInertiaApp } from "@inertiajs/vue3"
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers"
import { createApp, h } from "vue"

const appName = import.meta.env.VITE_APP_NAME || "Plantform"

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => {
    return resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob("./Pages/**/*.vue")
    )
  },
  setup({ el, App, props, plugin }) {
    const vueApp = createApp({ 
      render: () => h(App, props) 
    })
    
    vueApp.use(plugin)
    
    // Если нужно глобально использовать route()
    if (window.route) {
      vueApp.config.globalProperties.$route = window.route
    }
    
    return vueApp.mount(el)
  },
  progress: {
    color: "#2E7D32",
  },
})