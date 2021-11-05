const routes = [
    {
        path: "/", component: httpVueLoader('components/annotate.vue'),
        children: [
            {
                path: 'annotations',
                component: httpVueLoader('components/annotations.vue')
            },
            {
                path: 'options',
                component: httpVueLoader('components/options.vue')
            },
            {
                path: '',
                component: httpVueLoader('components/form-competences.vue')
            }
        ]
    }
];

const router = new VueRouter({
    routes: routes,
    linkActiveClass: "active"
});

const app = new Vue({
    router: router,
    data: {

    },
    mounted: function() {

    },
    methods: {

    }
}).$mount("#app");

