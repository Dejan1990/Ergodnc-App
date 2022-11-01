import {createRouter, createWebHistory} from "vue-router";
import IndexPage from '../pages/IndexPage.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';

const routes = [
    {
        path: '/',
        redirect: '/',
        component: DefaultLayout,
        children: [
            {path: '/', name: 'Index', component: IndexPage},
        ]
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router;